<?php

declare(strict_types=1);

/** Prepara arquivos Composer de runtime, sem instalar ou publicar pacotes. */
function executarDistribuicao(array $comando, string $diretorio): void
{
    $processo = proc_open($comando, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $canais, $diretorio);
    if (!is_resource($processo)) {
        throw new RuntimeException('Não foi possível iniciar o empacotamento.');
    }
    fclose($canais[0]);
    stream_get_contents($canais[1]);
    stream_get_contents($canais[2]);
    fclose($canais[1]);
    fclose($canais[2]);
    if (proc_close($processo) !== 0) {
        throw new RuntimeException('O empacotamento Composer falhou.');
    }
}

function copiarDistribuicao(string $origem, string $destino): void
{
    if (is_link($origem)) {
        throw new RuntimeException('A origem do pacote não pode conter links simbólicos.');
    }
    if (is_file($origem)) {
        if (!copy($origem, $destino)) {
            throw new RuntimeException('Não foi possível copiar uma fonte do pacote.');
        }
        return;
    }
    if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
        throw new RuntimeException('Não foi possível criar o diretório do pacote.');
    }
    foreach (new DirectoryIterator($origem) as $item) {
        $nome = $item->getFilename();
        if ($item->isDot() || in_array($nome, ['vendor', '.git', '.github', 'test', 'tests', 'testes'], true)
            || str_starts_with($nome, '.env') || $nome === 'auth.json') {
            continue;
        }
        copiarDistribuicao($item->getPathname(), $destino.'/'.$nome);
    }
}

function limparDistribuicao(string $diretorio): void
{
    if (!is_dir($diretorio)) {
        return;
    }
    foreach (new FilesystemIterator($diretorio) as $item) {
        if ($item->isDir() && !$item->isLink()) {
            limparDistribuicao($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($diretorio);
}

$temporario = sys_get_temp_dir().'/mailcore-distribuicao-'.bin2hex(random_bytes(12));
$codigoSaida = 0;
try {
    $raiz = dirname(__DIR__);
    $destino = $argv[1] ?? '/pacotes';
    if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
        throw new RuntimeException('Não foi possível criar o destino de distribuição.');
    }
    $destino = realpath($destino);
    if ($destino === false || !mkdir($temporario, 0700)) {
        throw new RuntimeException('Destino de distribuição inválido.');
    }
    $manifesto = json_decode(file_get_contents($raiz.'/composer.json'), true, 128, JSON_THROW_ON_ERROR);
    $lock = json_decode(file_get_contents($raiz.'/composer.lock'), true, 128, JSON_THROW_ON_ERROR);
    $pacotes = [];
    $fontes = [[$manifesto, $raiz, true]];
    foreach ($lock['packages'] as $pacote) {
        $fontes[] = [$pacote, $raiz.'/vendor/'.$pacote['name'], false];
    }
    foreach ($fontes as [$metadados, $origem, $proprio]) {
        $nome = $metadados['name'];
        $versao = $metadados['version'];
        $identidade = ($proprio ? 'mailcore-php' : str_replace('/', '-', $nome)).'-'.$versao;
        if (!preg_match('/\A[a-zA-Z0-9_.-]+\z/D', $identidade)) {
            throw new RuntimeException('Identidade de pacote inválida.');
        }
        $pasta = $temporario.'/'.$identidade;
        mkdir($pasta, 0755);
        if ($proprio) {
            copiarDistribuicao($origem.'/src', $pasta.'/src');
            foreach (['LICENSE', 'README.md', 'CHANGELOG.md', 'SECURITY.md', 'CONTRIBUTING.md'] as $arquivo) {
                if (is_file($origem.'/'.$arquivo)) {
                    copiarDistribuicao($origem.'/'.$arquivo, $pasta.'/'.$arquivo);
                }
            }
            foreach (glob($origem.'/docs/*.md') ?: [] as $documento) {
                if (!is_dir($pasta.'/docs')) {
                    mkdir($pasta.'/docs', 0755);
                }
                copiarDistribuicao($documento, $pasta.'/docs/'.basename($documento));
            }
            if (is_dir($origem.'/exemplos/Symfony')) {
                copiarDistribuicao($origem.'/exemplos/Symfony', $pasta.'/exemplos/Symfony');
            }
        } else {
            copiarDistribuicao($origem, $pasta);
        }
        $composerOrigem = $proprio ? $manifesto : json_decode(file_get_contents($origem.'/composer.json'), true, 128, JSON_THROW_ON_ERROR);
        $composer = array_intersect_key($composerOrigem, array_flip(['name', 'description', 'type', 'license', 'require', 'autoload', 'suggest', 'provide', 'replace', 'conflict']));
        $composer['version'] = $versao;
        file_put_contents($pasta.'/composer.json', json_encode($composer, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        executarDistribuicao(['composer', '--no-interaction', '--no-plugins', '--no-scripts', 'archive', '--format=zip', '--dir='.$destino, '--file='.$identidade], $pasta);
        $arquivo = $identidade.'.zip';
        $zip = $destino.'/'.$arquivo;
        if (!is_file($zip)) {
            throw new RuntimeException('O arquivo Composer esperado não foi criado.');
        }
        $pacotes[] = ['nome' => $nome, 'versao' => $versao, 'arquivo' => $arquivo,
            'sha256' => hash_file('sha256', $zip), 'sha1' => hash_file('sha1', $zip), 'composer' => $composer];
    }
    $resultado = ['resultado' => 'aprovado', 'pacote' => $manifesto['name'], 'versao' => $manifesto['version'], 'publicado' => false, 'pacotes' => $pacotes];
    file_put_contents($destino.'/manifesto.json', json_encode($resultado, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    echo json_encode(['resultado' => 'aprovado', 'arquivos' => count($pacotes), 'publicado' => false], JSON_THROW_ON_ERROR)."\n";
} catch (Throwable) {
    fwrite(STDERR, "Não foi possível preparar os arquivos de distribuição.\n");
    $codigoSaida = 1;
} finally {
    limparDistribuicao($temporario);
}
exit($codigoSaida);

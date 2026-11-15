<?php

declare(strict_types=1);

/** Verifica somente instalação e carregamento; não usa rede nem uma API. */
function exigirInstalacao(bool $condicao): void
{
    if (!$condicao) {
        throw new RuntimeException('Contrato de instalação não satisfeito.');
    }
}

function executarInstalacao(array $comando, string $diretorio, array $ambiente): string
{
    $processo = proc_open($comando, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $canais, $diretorio, $ambiente);
    exigirInstalacao(is_resource($processo));
    fclose($canais[0]);
    $saida = stream_get_contents($canais[1]);
    stream_get_contents($canais[2]);
    fclose($canais[1]);
    fclose($canais[2]);
    exigirInstalacao(proc_close($processo) === 0 && is_string($saida));
    return $saida;
}

function limparInstalacao(string $pasta): void
{
    if (!is_dir($pasta)) {
        return;
    }
    foreach (new FilesystemIterator($pasta) as $item) {
        if ($item->isDir() && !$item->isLink()) {
            limparInstalacao($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($pasta);
}

function gravarInstalacao(array $resultado, ?string $destino): void
{
    $json = json_encode($resultado, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    if ($destino !== null) {
        exigirInstalacao(file_put_contents($destino, $json) !== false);
    }
    echo $json;
}

function agregarInstalacoes(string $pasta): array
{
    $resultados = [];
    foreach (glob(rtrim($pasta, '/').'/instalacao-php-*.json') ?: [] as $arquivo) {
        $resultado = json_decode(file_get_contents($arquivo), true, 128, JSON_THROW_ON_ERROR);
        exigirInstalacao(($resultado['resultado'] ?? null) === 'aprovado');
        $versao = $resultado['php_principal'] ?? '';
        exigirInstalacao(in_array($versao, ['8.2', '8.3', '8.4', '8.5'], true) && !isset($resultados[$versao]));
        $resultados[$versao] = $resultado;
    }
    ksort($resultados);
    exigirInstalacao(array_keys($resultados) === ['8.2', '8.3', '8.4', '8.5']);
    exigirInstalacao(count(array_unique(array_column($resultados, 'pacote_sha256'))) === 1);
    return ['resultado' => 'aprovado', 'instalacao_offline' => true, 'rede_utilizada' => false,
        'publicado' => false, 'verificacoes' => array_values($resultados)];
}

$opcoes = getopt('', ['artefatos:', 'relatorio:', 'agregar:']);
$temporario = sys_get_temp_dir().'/mailcore-consumidor-'.bin2hex(random_bytes(12));
$etapa = 'configuracao';
$codigoSaida = 0;
try {
    if (isset($opcoes['agregar'])) {
        gravarInstalacao(agregarInstalacoes($opcoes['agregar']), $opcoes['relatorio'] ?? rtrim($opcoes['agregar'], '/').'/instalacao-local.json');
        exit(0);
    }
    $artefatos = realpath($opcoes['artefatos'] ?? '/pacotes');
    exigirInstalacao(is_string($artefatos));
    $manifesto = json_decode(file_get_contents($artefatos.'/manifesto.json'), true, 128, JSON_THROW_ON_ERROR);
    exigirInstalacao(($manifesto['resultado'] ?? null) === 'aprovado' && count($manifesto['pacotes']) === 5);
    $repositorio = [];
    $sdkZip = null;
    foreach ($manifesto['pacotes'] as $pacote) {
        exigirInstalacao(basename($pacote['arquivo']) === $pacote['arquivo']);
        $arquivo = $artefatos.'/'.$pacote['arquivo'];
        exigirInstalacao(is_file($arquivo) && hash_file('sha256', $arquivo) === $pacote['sha256']);
        exigirInstalacao(!isset($pacote['composer']['require-dev']) && !isset($pacote['composer']['autoload-dev']));
        $repositorio[] = [...$pacote['composer'], 'dist' => ['type' => 'zip', 'url' => 'file://'.$arquivo, 'shasum' => $pacote['sha1']]];
        if ($pacote['nome'] === $manifesto['pacote']) {
            $sdkZip = $arquivo;
        }
    }
    exigirInstalacao(is_string($sdkZip) && mkdir($temporario, 0700));
    $ambiente = getenv();
    $ambiente['COMPOSER_HOME'] = $temporario.'/composer-home';
    $ambiente['COMPOSER_CACHE_DIR'] = $temporario.'/composer-cache';
    $ambiente['COMPOSER_DISABLE_NETWORK'] = '1';
    $ambiente['COMPOSER_ALLOW_SUPERUSER'] = '1';
    unset($ambiente['COMPOSER'], $ambiente['COMPOSER_AUTH']);
    $etapa = 'inspecionar_zip';
    $entradas = executarInstalacao(['unzip', '-Z1', $sdkZip], $temporario, $ambiente);
    foreach (preg_split('/\r?\n/', trim($entradas)) as $entrada) {
        exigirInstalacao(!preg_match('~(?:\A|/)(?:vendor|testes|scripts|\.git|\.env[^/]*|auth\.json)(?:/|\z)~', $entrada));
        exigirInstalacao(!preg_match('~\Adocs/.*\.json\z~i', $entrada));
    }
    $manifestoZip = json_decode(executarInstalacao(['unzip', '-p', $sdkZip, 'composer.json'], $temporario, $ambiente), true, 128, JSON_THROW_ON_ERROR);
    exigirInstalacao(!isset($manifestoZip['require-dev']) && !isset($manifestoZip['autoload-dev']));
    $consumidor = ['name' => 'mailcore/consumidor-local', 'require' => [$manifesto['pacote'] => $manifesto['versao']],
        'repositories' => [['type' => 'package', 'package' => $repositorio], ['packagist.org' => false]],
        'config' => ['allow-plugins' => false, 'secure-http' => true], 'minimum-stability' => 'alpha', 'prefer-stable' => true];
    file_put_contents($temporario.'/composer.json', json_encode($consumidor, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    $etapa = 'instalar_consumidor';
    executarInstalacao(['composer', 'install', '--no-dev', '--no-plugins', '--no-scripts', '--no-interaction', '--prefer-dist', '--no-progress'], $temporario, $ambiente);
    $etapa = 'plataforma';
    $plataforma = json_decode(executarInstalacao(['composer', 'check-platform-reqs', '--no-dev', '--format=json'], $temporario, $ambiente), true, 128, JSON_THROW_ON_ERROR);
    exigirInstalacao($plataforma !== []);
    foreach ($plataforma as $requisito) {
        exigirInstalacao($requisito['status'] === 'success');
    }
    $instalados = json_decode(file_get_contents($temporario.'/vendor/composer/installed.json'), true, 128, JSON_THROW_ON_ERROR);
    exigirInstalacao(count($instalados['packages']) === 5 && ($instalados['dev-package-names'] ?? []) === []);
    require $temporario.'/vendor/autoload.php';
    $etapa = 'autoload_psr4';
    $arquivoClasse = (new ReflectionClass(Mailcore\Cliente::class))->getFileName();
    exigirInstalacao(is_string($arquivoClasse) && str_starts_with(realpath($arquivoClasse), $temporario.'/vendor/mailcore/mailcore-php/src/'));
    $fabrica = new Nyholm\Psr7\Factory\Psr17Factory();
    $http = new class ($fabrica) implements Psr\Http\Client\ClientInterface {
        public int $chamadas = 0;
        public function __construct(private Nyholm\Psr7\Factory\Psr17Factory $fabrica)
        {
        }
        public function sendRequest(Psr\Http\Message\RequestInterface $pedido): Psr\Http\Message\ResponseInterface
        {
            ++$this->chamadas;
            exigirInstalacao($pedido->getHeaderLine('Authorization') === 'Bearer mc_instalacao_sintetica');
            exigirInstalacao($pedido->getUri()->getScheme() === 'https' && $pedido->getUri()->getHost() === 'api-mailcore.example.test');
            exigirInstalacao(str_starts_with($pedido->getUri()->getPath(), '/v1/'));
            exigirInstalacao($pedido->getHeaderLine('Cookie') === '' && $pedido->getHeaderLine('Idempotency-Key') === 'instalacao-local');
            if ($pedido->getHeaderLine('Content-Type') === 'application/json') {
                $json = json_decode((string) $pedido->getBody(), false, 128, JSON_THROW_ON_ERROR);
                exigirInstalacao($json instanceof stdClass || is_array($json));
            }
            $aceito = $pedido->getHeaderLine('Accept');
            $corpo = $aceito === 'application/octet-stream' ? "parte\x00\xff\r\n" : ($aceito === 'text/html' ? '<p>prévia</p>' : '{"ok":true}');
            return $this->fabrica->createResponse(200)->withHeader('Content-Type', $aceito)->withHeader('Content-Length', (string) strlen($corpo))->withBody($this->fabrica->createStream($corpo));
        }
    };
    $cliente = new Mailcore\Cliente('mc_instalacao_sintetica', ['urlBase' => 'https://api-mailcore.example.test/v1', 'clienteHttp' => $http]);
    $etapa = 'operacoes_instaladas';
    $objetos = array_values(get_object_vars($cliente));
    $classes = 0;
    $metodos = 0;
    $callbacks = 0;
    while ($objetos !== []) {
        $recurso = array_shift($objetos);
        exigirInstalacao(is_object($recurso) && str_starts_with($recurso::class, 'Mailcore\\Recursos\\'));
        ++$classes;
        array_push($objetos, ...array_values(get_object_vars($recurso)));
        $reflexao = new ReflectionClass($recurso);
        foreach ($reflexao->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
            if ($metodo->getName() === '__construct' || $metodo->getDeclaringClass()->getName() !== $reflexao->getName()) {
                continue;
            }
            $argumentos = [];
            foreach ($metodo->getParameters() as $parametro) {
                $nome = $parametro->getName();
                $argumentos[] = match ($nome) {
                    'pedido' => ['texto' => 'sintético', 'opcional' => null, 'ativo' => false],
                    'dados' => $recurso instanceof Mailcore\Recursos\Lotes
                        ? [['remetente' => 'equipe@example.test', 'destinatarios' => ['destino@example.test'], 'texto' => 'sintético']]
                        : ['texto' => 'sintético', 'opcional' => null, 'ativo' => false],
                    'pedidos' => [['remetente' => 'equipe@example.test', 'destinatarios' => ['destino@example.test'], 'texto' => 'sintético']],
                    'filtros', 'paginacao' => ['limite' => 2, 'opcional' => null, 'ativo' => false],
                    'opcoes' => ['chaveIdempotencia' => 'instalacao-local'],
                    'indice' => 0,
                    'topicos' => [['id' => 'topico', 'assinatura' => 'opt_in']],
                    'ids' => ['etiqueta'],
                    'consumir' => static function (string $parte) use (&$callbacks): void {
                        exigirInstalacao($parte !== '');
                        ++$callbacks;
                    },
                    default => 'id/ação+?@example.test',
                };
            }
            $resposta = $metodo->invokeArgs($recurso, $argumentos);
            exigirInstalacao($resposta instanceof Mailcore\Resposta && $resposta->status === 200);
            ++$metodos;
        }
    }
    $cliente->fechar();
    exigirInstalacao($classes === 30 && $metodos === 151 && $http->chamadas === 151 && $callbacks === 3);
    $resultado = ['resultado' => 'aprovado', 'php' => PHP_VERSION, 'php_principal' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
        'pacote' => $manifesto['pacote'], 'versao' => $manifesto['versao'], 'pacote_sha256' => hash_file('sha256', $sdkZip),
        'pacotes_runtime_instalados' => count($instalados['packages']), 'dependencias_dev_instaladas' => 0,
        'classes_recursos' => $classes, 'metodos_http' => $metodos, 'chamadas_psr18_falso' => $http->chamadas,
        'callbacks_fluxo' => $callbacks, 'check_platform_reqs' => 'aprovado', 'instalacao_offline' => true,
        'rede_utilizada' => false, 'publicado' => false];
    gravarInstalacao($resultado, $opcoes['relatorio'] ?? null);
} catch (Throwable) {
    gravarInstalacao(['resultado' => 'falhou', 'etapa' => $etapa, 'php' => PHP_VERSION, 'falha' => 'Verificação de instalação não concluída; detalhes omitidos.'], $opcoes['relatorio'] ?? null);
    $codigoSaida = 1;
} finally {
    limparInstalacao($temporario);
}
exit($codigoSaida);

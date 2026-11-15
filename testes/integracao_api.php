<?php

declare(strict_types=1);

// Cliente do laboratório Docker: o relatório não contém credenciais ou mensagens.
use Mailcore\Cliente;
use Mailcore\Excecoes\Erro;
use Mailcore\Excecoes\ErroApi;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class FalhaIntegracao extends RuntimeException
{
}

final class IntegracaoApi
{
    private string $etapa = 'prontidao';
    private array $cenarios = [];
    private array $clientes = [];

    public function __construct(private stdClass $fixture, private string $certificado)
    {
    }

    private static function exigir(bool $condicao): void
    {
        if (!$condicao) {
            throw new FalhaIntegracao();
        }
    }

    private static function esperarErro(int $status, callable $executar): void
    {
        try {
            $executar();
        } catch (ErroApi $erro) {
            self::exigir($erro->status === $status);
            return;
        }
        throw new FalhaIntegracao();
    }

    private function concluir(): void
    {
        $this->cenarios[] = $this->etapa;
    }

    private function opcoes(): array
    {
        return ['urlBase' => 'https://mailcore-api:3443/v1', 'certificadoCa' => $this->certificado, 'tempoLimite' => 10];
    }

    private function cliente(string $chave, bool $psr18 = false): Cliente
    {
        $opcoes = $this->opcoes();
        if ($psr18) {
            // A política é do provedor: PSR-18 não padroniza TLS, prazo ou buffering.
            $http = HttpClient::create([
                'cafile' => $this->certificado, 'verify_peer' => true, 'verify_host' => true,
                'timeout' => 10.0, 'max_duration' => 10.0, 'max_redirects' => 0,
                'proxy' => '', 'no_proxy' => '*', 'buffer' => false,
            ]);
            $fabrica = new Psr17Factory();
            $opcoes['clienteHttp'] = new Psr18Client($http, $fabrica, $fabrica);
            $opcoes['fabricaRequisicoes'] = $fabrica;
            $opcoes['fabricaFluxos'] = $fabrica;
        }
        $cliente = new Cliente($chave, $opcoes);
        $this->clientes[] = $cliente;
        return $cliente;
    }

    /** Sessão e callback pertencem somente à preparação da fixture, fora do SDK. */
    private function http(string $caminho, string $metodo = 'GET', array $cabecalhos = [], ?string $corpo = null): array
    {
        $handle = curl_init('https://mailcore-api:3443'.$caminho);
        self::exigir($handle !== false);
        try {
            $opcoes = [
                CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROXY => '',
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => $this->certificado, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT_MS => 2000, CURLOPT_TIMEOUT_MS => 3000,
                CURLOPT_HTTPHEADER => array_map(static fn (string $nome, string $valor): string => $nome.': '.$valor, array_keys($cabecalhos), array_values($cabecalhos)),
            ];
            if ($corpo !== null) {
                $opcoes[CURLOPT_POSTFIELDS] = $corpo;
            }
            self::exigir(curl_setopt_array($handle, $opcoes));
            $resposta = curl_exec($handle);
            self::exigir(is_string($resposta));
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            return ['status' => $status, 'dados' => $resposta === '' ? null : json_decode($resposta, false, 128, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING)];
        } finally {
            curl_close($handle);
        }
    }

    private static function agendamento(): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', time() + 3600);
    }

    private function executarCenarios(): array
    {
        $fim = hrtime(true) + 30_000_000_000;
        $pronta = false;
        while (!$pronta && hrtime(true) < $fim) {
            try {
                $pronta = $this->http('/health/ready')['status'] === 200;
            } catch (Throwable) {
                // A sonda não aceita uma intenção de envio.
            }
            if (!$pronta) {
                usleep(200_000);
            }
        }
        self::exigir($pronta);

        $this->etapa = 'criar_full_sessao_sintetica';
        $criacao = $this->http('/v1/api-keys', 'POST', [
            'Content-Type' => 'application/json', 'Origin' => 'https://localhost:17443',
            'Cookie' => '__Host-mailcore='.$this->fixture->token, 'X-CSRF-Token' => $this->fixture->csrf,
        ], json_encode(['nome' => 'Fixture PHP Full', 'permissao' => 'full_access'], JSON_THROW_ON_ERROR));
        unset($this->fixture->token, $this->fixture->csrf);
        self::exigir($criacao['status'] === 201);
        $full = $criacao['dados'];
        self::exigir($full instanceof stdClass);
        $sdk = $this->cliente($full->chave);
        $sdkPsr = $this->cliente($full->chave, true);
        $remetente = 'avisos@'.$this->fixture->nome_dominio;

        $this->etapa = 'chaves_full_sending_escopo_dump';
        $chaveEnvio = $sdk->chavesApi->criar(['nome' => 'Fixture PHP Sending', 'permissao' => 'sending_access', 'dominio_id' => $this->fixture->dominio])->dados;
        $envio = $this->cliente($chaveEnvio->chave);
        self::esperarErro(403, static fn () => $envio->dominios->listar());
        ob_start();
        var_dump($sdk);
        $dump = ob_get_clean();
        self::exigir(is_string($dump) && !str_contains($dump, $full->chave));
        self::exigir(count(array_filter($sdk->chavesApi->listar()->dados->dados, static fn (stdClass $chave): bool => $chave->id === $chaveEnvio->id)) === 1);
        $this->concluir();

        $pedido = [
            'remetente' => 'Equipe PHP <'.$remetente.'>', 'destinatarios' => ['principal@example.test'],
            'cc' => ['copia@example.test'], 'cco' => ['oculto@example.test'], 'assunto' => 'Validação PHP',
            'texto' => 'Dados sintéticos', 'html' => '<p>Dados sintéticos<img src="cid:marca@example.test"></p>',
            'cabecalhos' => (object) ['X-Teste-SDK' => 'sintetico'], 'agendada_em' => self::agendamento(),
            'anexos' => [['nome' => 'marca.png', 'tipo_conteudo' => 'image/png', 'conteudo_base64' => base64_encode("\x89PNG"), 'id_conteudo' => 'marca@example.test']],
        ];
        $this->etapa = 'envio_composto_idempotencia_409_download_cancelamento';
        $aceita = $envio->emails->enviar($pedido, ['chaveIdempotencia' => 'php-composto'])->dados;
        self::exigir($aceita instanceof stdClass && $aceita->estado === 'agendada');
        self::exigir($envio->emails->enviar($pedido, ['chaveIdempotencia' => 'php-composto'])->dados == $aceita);
        self::esperarErro(409, static fn () => $envio->emails->enviar([...$pedido, 'assunto' => 'Outra intenção'], ['chaveIdempotencia' => 'php-composto']));
        self::exigir($sdk->emails->obterPorIdempotencia('php-composto')['id'] === $aceita->id);
        self::exigir(count($sdk->emails->obter($aceita->id)->dados->destinatarios) === 3);
        self::exigir($sdk->emails->baixarAnexo($aceita->id, 0)->dados === "\x89PNG");
        self::exigir($sdk->emails->cancelar($aceita->id)->dados->estado === 'cancelada');
        $this->concluir();

        $this->etapa = 'lote_atomico_repeticao_conflito';
        $item = ['remetente' => $remetente, 'destinatarios' => ['lote@example.test'], 'assunto' => 'Lote PHP', 'texto' => 'Dados sintéticos', 'agendada_em' => self::agendamento()];
        $antes = array_map(static fn (stdClass $email): string => $email->id, $sdk->emails->listar(['limite' => 100])->dados->dados);
        sort($antes, SORT_STRING);
        self::esperarErro(422, static fn () => $envio->lotes->enviar([$item, [...$item, 'destinatarios' => ['endereco-invalido']]], ['chaveIdempotencia' => 'php-lote-invalido']));
        $depois = array_map(static fn (stdClass $email): string => $email->id, $sdk->emails->listar(['limite' => 100])->dados->dados);
        sort($depois, SORT_STRING);
        self::exigir($antes === $depois);
        $lote = [$item, [...$item, 'assunto' => 'Segundo item PHP']];
        $aceitaLote = $envio->lotes->enviar($lote, ['chaveIdempotencia' => 'php-lote'])->dados;
        self::exigir(count($aceitaLote->dados) === 2);
        self::exigir($envio->lotes->enviar($lote, ['chaveIdempotencia' => 'php-lote'])->dados == $aceitaLote);
        self::exigir($sdk->lotes->obterPorIdempotencia('php-lote')->dados == $aceitaLote);
        self::esperarErro(409, static fn () => $envio->lotes->enviar([[...$item, 'assunto' => 'Divergente'], $lote[1]], ['chaveIdempotencia' => 'php-lote']));
        self::exigir(count($sdk->emails->listar(['limite' => 100])->dados->dados) === count($antes) + 2);
        foreach ($aceitaLote->dados as $email) {
            $sdk->emails->cancelar($email->id);
        }
        $this->concluir();

        $this->etapa = 'dominios_capacidades_tracking_crud';
        $dominio = $sdk->dominios->atualizar($this->fixture->dominio, ['capacidades' => ['envio' => 'enabled', 'recebimento' => 'enabled'], 'rastreamento_aberturas' => true, 'rastreamento_cliques' => true])->dados;
        self::exigir($dominio->capacidades->recebimento === 'enabled' && $dominio->rastreamento_aberturas === true);
        $novo = $sdk->dominios->criar(['nome' => 'php-'.str_replace('-', '', $this->fixture->organizacao).'.example.com', 'capacidades' => ['envio' => 'enabled', 'recebimento' => 'disabled']])->dados;
        self::exigir($sdk->dominios->remover($novo->id)['estado'] === 'suspenso');
        $this->concluir();

        $this->etapa = 'contatos_objetos_listas_null_false_importacao_csv_fluxos';
        $segmento = $sdk->segmentos->criar(['nome' => 'Segmento PHP'])->dados;
        $topico = $sdk->topicos->criar(['nome' => 'Tópico PHP', 'visibilidade' => 'publico', 'assinatura_padrao' => 'opt_in'])->dados;
        $contato = $sdk->contatos->criar(['email' => 'contato@example.test', 'primeiro_nome' => 'Sintético', 'descadastrado' => false, 'segmentos' => [$segmento->id], 'topicos' => [['id' => $topico->id, 'assinatura' => 'opt_in']]])->dados;
        self::exigir($sdk->contatos->obter($contato->id)['id'] === $contato->id && $contato->descadastrado === false);
        $importacao = $sdk->contatos->importacoes->criar([
            'arquivo_nome' => 'fixture.csv', 'conteudo' => "email,primeiro_nome\ncontato@example.test,Importado\n",
            'mapeamento' => (object) ['0' => 'email', '1' => 'primeiro_nome'],
        ], ['chaveIdempotencia' => 'php-importacao'])->dados;
        // Importações ficam na fila; este ensaio não inicia o worker.
        self::exigir($importacao->estado === 'pendente' && $importacao->total === 1 && $importacao->processados === 0);
        self::exigir($sdk->contatos->atualizar($contato->id, ['primeiro_nome' => null])->dados->primeiro_nome === null);
        $exportacao = $sdk->contatos->exportacoes->criar([], ['chaveIdempotencia' => 'php-exportacao'])->dados;
        self::exigir($exportacao->estado === 'concluida' && $exportacao->total === 1);
        $csv = '';
        $csvResposta = $sdk->contatos->exportacoes->baixarFluxo($exportacao->id, static function (string $parte) use (&$csv): void {
            $csv .= $parte;
        });
        self::exigir(str_contains($csv, 'contato@example.test') && $csvResposta->dados === strlen($csv));
        $csvPsr = '';
        $psrResposta = $sdkPsr->contatos->exportacoes->baixarFluxo($exportacao->id, static function (string $parte) use (&$csvPsr): void {
            $csvPsr .= $parte;
        });
        self::exigir($csvPsr === $csv && $psrResposta->dados === strlen($csvPsr));
        self::exigir(count(array_filter($sdk->contatos->exportacoes->listar()->dados->dados, static fn (stdClass $item): bool => $item->id === $exportacao->id)) === 1);
        $this->concluir();

        $this->etapa = 'modelos_pastas_versoes_previa_publicacao_cas';
        $pasta = $sdk->modelos->pastas->criar(['nome' => 'Pasta PHP'])->dados;
        $modelo = $sdk->modelos->criar(['nome' => 'Modelo PHP', 'html' => '<p>Olá, {{{NOME}}}.</p>', 'pasta_id' => $pasta->id, 'remetente' => $remetente, 'assunto' => 'Modelo sintético', 'variaveis' => [['chave' => 'NOME', 'tipo' => 'string']]])->dados;
        $versao = $sdk->modelos->versoes->listar($modelo->id)->dados->dados[0];
        $versaoObtida = $sdk->modelos->versoes->obter($modelo->id, $versao->id)->dados;
        self::exigir($versaoObtida->versao_id === $versao->id && $versaoObtida->id === $modelo->id);
        $previa = $sdk->modelos->prever($modelo->id, ['revisao' => $modelo->revisao, 'variaveis' => (object) ['NOME' => 'PHP']])->dados;
        self::exigir(str_contains($previa->html, 'PHP'));
        $previaId = basename((string) parse_url($previa->url, PHP_URL_PATH));
        self::exigir(str_contains($sdk->modelos->obterPrevia($this->fixture->organizacao, $previaId)->dados, 'PHP'));
        $antiga = $modelo->revisao;
        $modelo = $sdk->modelos->atualizar($modelo->id, ['revisao' => $antiga, 'nome' => 'Modelo editado'])->dados;
        self::esperarErro(409, static fn () => $sdk->modelos->atualizar($modelo->id, ['revisao' => $antiga, 'nome' => 'Conflito']));
        $modelo = $sdk->modelos->publicar($modelo->id, ['revisao' => $modelo->revisao])->dados;
        self::exigir($modelo->status === 'published');
        $modelo = $sdk->modelos->versoes->restaurar($modelo->id, $versao->id, ['revisao' => $modelo->revisao])->dados;
        $this->concluir();

        $this->etapa = 'marketing_objeto_vazio_crud_sem_disparo';
        $campanha = $sdk->campanhas->criar(['nome' => 'Campanha PHP'])->dados;
        $campanha = $sdk->campanhas->atualizar($campanha->id, ['revisao' => $campanha->revisao, 'nome' => 'Campanha editada'])->dados;
        self::exigir($sdk->campanhas->obter($campanha->id)['id'] === $campanha->id);
        $sdk->campanhas->remover($campanha->id);
        $evento = $sdk->eventos->criar(['nome' => 'php.qa', 'esquema' => new stdClass()])->dados;
        self::exigir($evento->esquema instanceof stdClass && get_object_vars($evento->esquema) === []);
        self::exigir($sdk->eventos->atualizar($evento->id, ['esquema' => null])->dados->esquema === null);
        $automacao = $sdk->automacoes->criar(['nome' => 'Automação PHP', 'estado' => 'inativa'])->dados;
        $automacao = $sdk->automacoes->atualizar($automacao->id, ['revisao' => $automacao->revisao, 'nome' => 'Automação editada'])->dados;
        self::exigir($sdk->automacoes->obter($automacao->id)['id'] === $automacao->id);
        $sdk->automacoes->remover($automacao->id);
        $sdk->eventos->remover($evento->id);
        $this->concluir();

        $this->etapa = 'caixas_etiquetas_rascunhos_cas_sem_envio';
        $caixa = $sdk->caixas->criar(['endereco' => 'suporte@'.$this->fixture->nome_dominio, 'nome' => 'Suporte PHP'])->dados;
        self::exigir($sdk->caixas->obter($caixa->id)['id'] === $caixa->id);
        $etiqueta = $sdk->caixas->etiquetas->criar($caixa->id, ['nome' => 'Pedidos', 'cor' => '#224466'])->dados;
        $rascunho = $sdk->caixas->rascunhos->criar($caixa->id, ['para' => ['cliente@example.test'], 'assunto' => 'Pedido', 'texto' => 'Olá'])->dados;
        $antiga = $rascunho->revisao;
        $rascunho = $sdk->caixas->rascunhos->atualizar($caixa->id, $rascunho->id, ['revisao' => $antiga, 'texto' => 'Atualizado'])->dados;
        self::esperarErro(409, static fn () => $sdk->caixas->rascunhos->atualizar($caixa->id, $rascunho->id, ['revisao' => $antiga, 'texto' => 'Conflito']));
        self::exigir($sdk->caixas->rascunhos->obter($caixa->id, $rascunho->id)->dados->html === null);
        $sdk->caixas->rascunhos->remover($caixa->id, $rascunho->id);
        $sdk->caixas->etiquetas->remover($caixa->id, $etiqueta->id);
        $sdk->caixas->remover($caixa->id);
        $this->concluir();

        $this->etapa = 'recebimento_mime_real_pendente_bytes_fluxos_curl_psr18';
        self::exigir($sdk->recebimento->listar()->dados->dados === []);
        $caminho = '/interno/postal/recebidos/'.$this->fixture->rota;
        $cabecalhos = ['Content-Type' => 'application/json', 'X-Postal-Signature-256' => $this->fixture->callback_assinatura];
        $callback = $this->http($caminho, 'POST', $cabecalhos, $this->fixture->callback_corpo);
        self::exigir($callback['status'] === 200);
        $recebido = $callback['dados']->id;
        $repetida = $this->http($caminho, 'POST', $cabecalhos, $this->fixture->callback_corpo);
        self::exigir($repetida['status'] === 200 && $repetida['dados']->id === $recebido);
        $mime = base64_decode($this->fixture->mime_esperado, true);
        self::exigir(is_string($mime));
        $detalhe = $sdk->recebimento->obter($recebido, ['formato_html' => 'cid'])->dados;
        self::exigir($detalhe->estado_processamento === 'pendente' && $detalhe->para === ['real@'.$this->fixture->nome_dominio]);
        $original = $sdk->recebimento->baixarOriginal($recebido);
        self::exigir($original->status === 200 && $original->cabecalhos['content-type'] === 'message/rfc822' && $original->dados === $mime);
        $bruto = '';
        $fluxo = $sdk->recebimento->baixarOriginalFluxo($recebido, static function (string $parte) use (&$bruto): void {
            $bruto .= $parte;
        });
        self::exigir($bruto === $mime && $fluxo->dados === strlen($mime));
        $brutoPsr = '';
        $fluxoPsr = $sdkPsr->recebimento->baixarOriginalFluxo($recebido, static function (string $parte) use (&$brutoPsr): void {
            $brutoPsr .= $parte;
        });
        self::exigir($brutoPsr === $mime && $fluxoPsr->dados === strlen($mime) && $fluxoPsr->cabecalhos['content-type'] === 'message/rfc822');
        self::exigir(count($sdk->recebimento->listar()->dados->dados) === 1);
        self::esperarErro(403, static fn () => $envio->recebimento->listar());
        $this->concluir();

        $this->etapa = 'cli_revogacao_e_limpeza_crud';
        self::exigir($sdk->cli->concessoes->listar(['limite' => 1])->dados->dados === []);
        self::esperarErro(403, static fn () => $envio->cli->concessoes->listar());
        $respostas = [$sdk->contatos->remover($contato->id), $sdk->segmentos->remover($segmento->id), $sdk->topicos->remover($topico->id), $sdk->modelos->remover($modelo->id), $sdk->modelos->pastas->remover($pasta->id), $sdk->chavesApi->revogar($chaveEnvio->id)];
        foreach ($respostas as $resposta) {
            self::exigir($resposta->status >= 200 && $resposta->status <= 299);
        }
        self::esperarErro(401, static fn () => $envio->emails->enviar($pedido, ['chaveIdempotencia' => 'php-revogada']));
        $this->concluir();
        return ['resultado' => 'aprovado', 'php' => PHP_VERSION, 'cenarios' => $this->cenarios, 'recebimento_processado_por_worker' => false, 'importacao_processada_por_worker' => false, 'callbacks_fluxo_curl' => 2, 'callbacks_fluxo_psr18' => 2, 'psr18_provedor' => 'symfony-http-client', 'politica_psr18' => 'tls_ca_explicita_sem_proxy_redirect_retry_timeout_provedor'];
    }

    public function executar(): array
    {
        try {
            return $this->executarCenarios();
        } catch (Throwable $erro) {
            return ['resultado' => 'falhou', 'php' => PHP_VERSION, 'etapa' => $this->etapa, 'cenarios' => $this->cenarios, 'erro_http' => $erro instanceof Erro ? ['codigo' => $erro->codigo, 'status' => $erro->status] : null, 'falha' => 'Contrato de integração não satisfeito; detalhes privados omitidos.'];
        } finally {
            foreach (array_reverse($this->clientes) as $cliente) {
                $cliente->fechar();
            }
        }
    }
}

$certificado = null;
try {
    require '/sdk/vendor/autoload.php';
    $fixture = json_decode(stream_get_contents(STDIN), false, 128, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
    if (!$fixture instanceof stdClass) {
        throw new FalhaIntegracao();
    }
    $certificado = tempnam(sys_get_temp_dir(), 'mailcore-php-ca-');
    if (!is_string($certificado) || file_put_contents($certificado, $fixture->certificado_ca) === false || !chmod($certificado, 0600)) {
        throw new FalhaIntegracao();
    }
    unset($fixture->certificado_ca);
    $resultado = (new IntegracaoApi($fixture, $certificado))->executar();
} catch (Throwable) {
    $resultado = ['resultado' => 'falhou', 'etapa' => 'configuracao', 'falha' => 'Não foi possível iniciar o cliente sintético.'];
} finally {
    if (is_string($certificado) && is_file($certificado)) {
        unlink($certificado);
    }
}
echo json_encode($resultado, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n";
exit($resultado['resultado'] === 'aprovado' ? 0 : 1);

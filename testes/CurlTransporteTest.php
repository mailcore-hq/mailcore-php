<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Closure;
use Mailcore\Cliente;
use Mailcore\Excecoes\ErroApi;
use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Excecoes\ErroResposta;
use Mailcore\Excecoes\ErroTransporte;
use Mailcore\Transporte;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Throwable;

final class CurlTransporteTest extends TestCase
{
    private const CHAVE = 'mc_sintetica_123';
    private static ServidorCurlTeste $servidor;
    private array $transportes = [];

    public static function setUpBeforeClass(): void
    {
        self::$servidor = new ServidorCurlTeste();
    }

    public static function tearDownAfterClass(): void
    {
        self::$servidor->encerrar();
    }

    protected function setUp(): void
    {
        self::$servidor->configurar([]);
    }

    protected function tearDown(): void
    {
        foreach ($this->transportes as $transporte) {
            $transporte->fechar();
        }
    }

    private function transporte(array $configuracao = [], string|Closure|null $chave = self::CHAVE): Transporte
    {
        $transporte = new Transporte($chave, array_replace([
            'urlBase' => self::$servidor->info->base,
            'certificadoCa' => self::$servidor->info->ca,
            'tempoLimite' => 2,
            'tempoConexao' => 1,
        ], $configuracao));
        $this->transportes[] = $transporte;
        return $transporte;
    }

    #[DataProvider('metodos')]
    public function testQuedaDepoisDeConexaoAquecidaRegistraTentativasReais(string $metodo): void
    {
        self::$servidor->configurar(['modo' => 'quedaAquecida']);
        $transporte = $this->transporte();
        $transporte->executar('GET', '/aquecer');
        try {
            $transporte->executar($metodo, '/queda', corpo: $metodo === 'POST' ? (object) ['texto' => 'sintetico'] : null);
            self::fail('A queda deveria interromper a operação.');
        } catch (ErroTransporte $erro) {
            self::assertSame('falha_de_rede', $erro->codigo);
        }
        $registros = array_values(array_filter(self::$servidor->registros(), static fn (stdClass $registro): bool => $registro->alvo === '/v1/queda'));
        // libcurl pode recuperar GET numa conexão reutilizada. Mutações usam conexão nova.
        self::assertCount($metodo === 'GET' ? 2 : 1, $registros);
    }

    public static function metodos(): iterable
    {
        yield 'GET' => ['GET'];
        yield 'POST' => ['POST'];
    }

    public function testJsonObjetosListasMapasNumericosNullFalseEUnicodeNoFio(): void
    {
        $cliente = new Cliente(self::CHAVE, ['urlBase' => self::$servidor->info->base, 'certificadoCa' => self::$servidor->info->ca]);
        $dados = [
            'evento' => 'compra.concluida',
            'email' => 'usuario@example.test',
            'dados' => (object) [
                'texto' => 'Ação e revisão', 'nulo' => null, 'ativo' => false, 'preco' => 1.0,
                'objetoVazio' => new stdClass(), 'listaVazia' => [],
                'mapaNumerico' => (object) ['0' => 'email', '1' => 'primeiro_nome'],
            ],
        ];
        $copia = json_encode($dados, JSON_THROW_ON_ERROR);
        try {
            $resposta = $cliente->eventos->enviar($dados, ['chaveIdempotencia' => 'pedido-123', 'cabecalhos' => ['X-Correlacao' => 'sintetica']]);
            self::assertSame('email-sintetico', $resposta['id']);
            self::assertSame(200, $resposta->status);
            $cliente->lotes->enviar([['remetente' => 'origem@example.test', 'destinatarios' => ['destino@example.test'], 'assunto' => 'Olá', 'texto' => 'Mensagem']]);
        } finally {
            $cliente->fechar();
        }
        $registros = self::$servidor->registros();
        self::assertCount(2, $registros);
        $pedido = $registros[0];
        self::assertSame('POST', $pedido->metodo);
        self::assertSame('/v1/events/send', $pedido->alvo);
        self::assertSame('Bearer '.self::CHAVE, $pedido->cabecalhos->authorization);
        self::assertSame('pedido-123', $pedido->cabecalhos->{'idempotency-key'});
        self::assertSame('sintetica', $pedido->cabecalhos->{'x-correlacao'});
        self::assertSame('identity', $pedido->cabecalhos->{'accept-encoding'});
        self::assertSame('application/json', $pedido->cabecalhos->{'content-type'});
        self::assertStringStartsWith('mailcore-php/', $pedido->cabecalhos->{'user-agent'});
        self::assertNull($pedido->jsonCorpo->dados->nulo);
        self::assertFalse($pedido->jsonCorpo->dados->ativo);
        self::assertSame(1.0, $pedido->jsonCorpo->dados->preco);
        self::assertSame('Ação e revisão', $pedido->jsonCorpo->dados->texto);
        self::assertInstanceOf(stdClass::class, $pedido->jsonCorpo->dados->objetoVazio);
        self::assertSame([], $pedido->jsonCorpo->dados->listaVazia);
        self::assertInstanceOf(stdClass::class, $pedido->jsonCorpo->dados->mapaNumerico);
        self::assertSame('email', $pedido->jsonCorpo->dados->mapaNumerico->{'0'});
        self::assertIsArray($registros[1]->jsonCorpo);
        self::assertInstanceOf(stdClass::class, $registros[1]->jsonCorpo[0]);
        self::assertSame($copia, json_encode($dados, JSON_THROW_ON_ERROR));
    }

    public function testFiltrosEIdentificadoresSaoCodificadosSemAlterarOrigem(): void
    {
        $transporte = $this->transporte();
        (new \Mailcore\Recursos\Emails($transporte))->obter('parte/ação+?@example.test');
        $transporte->executar('GET', '/receiving', consulta: ['busca' => 'ação+teste & assunto=?', 'ativo' => false, 'limite' => 2, 'omitido' => null]);
        $registros = self::$servidor->registros();
        self::assertSame('/v1/emails/parte%2Fa%C3%A7%C3%A3o%2B%3F%40example.test', $registros[0]->alvo);
        parse_str(parse_url($registros[1]->alvo, PHP_URL_QUERY), $consulta);
        self::assertSame(['busca' => 'ação+teste & assunto=?', 'ativo' => 'false', 'limite' => '2'], $consulta);
    }

    public function testChaveDinamicaResolvidaPorOperacaoSemRetencaoDoPedidoAnterior(): void
    {
        $chave = 'mc_primeira';
        $resolucoes = 0;
        $transporte = $this->transporte([], static function () use (&$chave, &$resolucoes): string {
            ++$resolucoes;
            return $chave;
        });
        $transporte->executar('GET', '/domains');
        $chave = 'mc_segunda';
        $transporte->executar('GET', '/domains');
        $registros = self::$servidor->registros();
        self::assertSame(2, $resolucoes);
        self::assertSame('Bearer mc_primeira', $registros[0]->cabecalhos->authorization);
        self::assertSame('Bearer mc_segunda', $registros[1]->cabecalhos->authorization);
    }

    public function testFalhaDeChaveETraceDeTransporteNaoExibemCredencial(): void
    {
        $segredo = 'mc_sintetica_trace_protegida';
        $antigo = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');
        try {
            $transporte = $this->transporte(['urlBase' => 'https://127.0.0.1:1/v1'], $segredo);
            try {
                $transporte->executar('GET', '/domains');
                self::fail('A conexão inexistente deveria falhar.');
            } catch (ErroTransporte $erro) {
                self::assertFalse(str_contains(json_encode($erro->getTrace(), JSON_THROW_ON_ERROR), $segredo), 'O trace não deve incluir a chave de API.');
                self::assertNull($erro->getPrevious());
            }
            ob_start();
            var_dump($transporte);
            $representacao = ob_get_clean();
            self::assertFalse(str_contains($representacao, $segredo));
            $invalido = $this->transporte([], static function () use ($segredo): never {
                throw new RuntimeException($segredo);
            });
            try {
                $invalido->executar('GET', '/domains');
                self::fail('A chave não resolvida deveria ser recusada.');
            } catch (ErroConfiguracao $erro) {
                self::assertFalse(str_contains($erro->getMessage(), $segredo));
                self::assertNull($erro->getPrevious());
            }
        } finally {
            ini_set('zend.exception_ignore_args', $antigo);
        }
        self::assertCount(0, self::$servidor->registros());
    }

    public function testHttpsExigeCaConfiavelENomeCorreto(): void
    {
        foreach ([
            ['certificadoCa' => null],
            ['certificadoCa' => self::$servidor->info->outraCa],
            ['urlBase' => str_replace('127.0.0.1', 'localhost', self::$servidor->info->base)],
        ] as $configuracao) {
            try {
                $this->transporte($configuracao)->executar('GET', '/domains');
                self::fail('A validação TLS deveria recusar a conexão.');
            } catch (ErroTransporte $erro) {
                self::assertSame('falha_de_rede', $erro->codigo);
                self::assertNull($erro->getPrevious());
            }
        }
        self::assertCount(0, self::$servidor->registros());
        self::assertSame(200, $this->transporte()->executar('GET', '/domains')->status);
    }

    public function testHttpLocalExigeOptInEUrlNaoAceitaSegredosEmbuidos(): void
    {
        foreach ([
            ['urlBase' => self::$servidor->info->http],
            ['urlBase' => 'http://api.example.test/v1', 'permitirHttpLocal' => true],
            ['urlBase' => 'ftp://example.test/v1'],
            ['urlBase' => 'https://usuario:senha@example.test/v1'],
            ['urlBase' => 'https://example.test/v1?chave=valor'],
            ['urlBase' => 'https://example.test/v1#segredo'],
        ] as $configuracao) {
            try {
                $this->transporte($configuracao);
                self::fail('A configuração de URL deveria ser recusada.');
            } catch (ErroConfiguracao) {
                self::assertCount(0, self::$servidor->registros());
            }
        }
        $resposta = $this->transporte(['urlBase' => self::$servidor->info->http, 'permitirHttpLocal' => true])->executar('GET', '/domains');
        self::assertSame(200, $resposta->status);
    }

    public function testProxyDoAmbienteNaoRecebeCredencial(): void
    {
        $nomes = ['http_proxy', 'https_proxy', 'HTTP_PROXY', 'HTTPS_PROXY', 'all_proxy', 'ALL_PROXY'];
        $antigos = [];
        foreach ($nomes as $nome) {
            $antigos[$nome] = getenv($nome);
            putenv($nome.'=http://127.0.0.1:1');
        }
        try {
            self::assertSame(200, $this->transporte()->executar('GET', '/domains')->status);
            self::assertCount(1, self::$servidor->registros());
        } finally {
            foreach ($antigos as $nome => $valor) {
                putenv($valor === false ? $nome : $nome.'='.$valor);
            }
        }
    }

    public function testCabecalhosReservadosIdempotenciaEOpcoesInvalidasNaoEnviamPedido(): void
    {
        $transporte = $this->transporte();
        foreach (['Authorization', 'Proxy-Authorization', 'Host', 'Cookie', 'Content-Length', 'Content-Type', 'Idempotency-Key', 'Accept', 'Accept-Encoding', 'User-Agent'] as $nome) {
            try {
                $transporte->executar('GET', '/domains', opcoes: ['cabecalhos' => [$nome => 'valor']]);
                self::fail('Cabeçalho reservado deveria ser recusado.');
            } catch (ErroConfiguracao) {
                self::assertCount(0, self::$servidor->registros());
            }
        }
        foreach ([
            ['cabecalhos' => ['X-Teste' => "linha\r\nAuthorization: teste"]],
            ['cabecalhos' => ["X\nTeste" => 'valor']],
            ['tempoLimite' => true], ['tempoLimite' => false],
            ['limiteRespostaBytes' => true], ['limiteRespostaBytes' => false],
            ['chaveIdempotencia' => ''], ['chaveIdempotencia' => 'com espaco'],
            ['chaveIdempotencia' => "quebra\nlinha"], ['chaveIdempotencia' => str_repeat('a', 257)],
        ] as $opcoes) {
            try {
                $transporte->executar('GET', '/domains', opcoes: $opcoes);
                self::fail('A opção inválida deveria ser recusada.');
            } catch (ErroConfiguracao) {
                self::assertCount(0, self::$servidor->registros());
            }
        }
    }

    public function testCookiesNaoSaoReutilizadosEMetadadosPrivadosSaoOmitidos(): void
    {
        self::$servidor->configurar(['modo' => 'cookies']);
        $transporte = $this->transporte();
        for ($indice = 0; $indice < 2; ++$indice) {
            $resposta = $transporte->executar('GET', '/domains');
            self::assertSame('requisicao-sintetica', $resposta->cabecalhos['x-request-id']);
            foreach (['set-cookie', 'authorization', 'proxy-authorization'] as $nome) {
                self::assertArrayNotHasKey($nome, $resposta->cabecalhos);
            }
        }
        foreach (self::$servidor->registros() as $registro) {
            self::assertObjectNotHasProperty('cookie', $registro->cabecalhos);
        }
    }

    public function testLeiturasHttp11ReutilizamConexaoDoCliente(): void
    {
        $transporte = $this->transporte();
        for ($indice = 0; $indice < 3; ++$indice) {
            self::assertSame(200, $transporte->executar('GET', '/domains')->status);
        }
        $registros = self::$servidor->registros();
        self::assertCount(3, $registros);
        self::assertCount(1, array_unique(array_map(static fn (stdClass $registro): int => $registro->conexao, $registros)));
    }

    #[DataProvider('statusHttp')]
    public function testStatusJsonEHtmlPreservamErroPublicoSemRepetir(int $status, bool $json): void
    {
        self::$servidor->configurar(['modo' => 'erro', 'status' => $status, 'json' => $json]);
        try {
            $this->transporte()->executar('POST', '/emails', corpo: (object) ['texto' => 'sintetico']);
            self::fail('A resposta HTTP deveria ser um erro.');
        } catch (ErroApi $erro) {
            self::assertSame($status, $erro->status);
            self::assertSame($json ? 'recusada' : 'erro_http', $erro->codigo);
            self::assertSame('7', $erro->cabecalhos['retry-after']);
            self::assertSame('requisicao-sintetica', $erro->cabecalhos['x-request-id']);
            self::assertStringNotContainsString('detalhe-privado-sintetico', $erro->getMessage());
            self::assertArrayNotHasKey('set-cookie', $erro->cabecalhos);
            self::assertArrayNotHasKey('authorization', $erro->cabecalhos);
            self::assertArrayNotHasKey('proxy-authorization', $erro->cabecalhos);
        }
        self::assertCount(1, self::$servidor->registros());
    }

    public static function statusHttp(): iterable
    {
        foreach ([401, 403, 409, 422, 429, 500, 503] as $status) {
            yield $status.' JSON' => [$status, true];
            yield $status.' HTML' => [$status, false];
        }
    }

    #[DataProvider('codigosRedirecionamento')]
    public function testRedirecionamentoNaoEseguidoNemRecebeCredencial(int $status): void
    {
        self::$servidor->configurar(['modo' => 'redirecionamento', 'status' => $status]);
        try {
            $this->transporte()->executar('POST', '/emails', corpo: (object) ['texto' => 'sintetico']);
            self::fail('O redirecionamento deveria ser recusado.');
        } catch (ErroApi $erro) {
            self::assertSame('redirecionamento_recusado', $erro->codigo);
            self::assertSame($status, $erro->status);
        }
        $registros = self::$servidor->registros();
        self::assertCount(1, $registros);
        self::assertSame('/v1/emails', $registros[0]->alvo);
    }

    public static function codigosRedirecionamento(): iterable
    {
        foreach ([301, 302, 307, 308] as $codigo) {
            yield (string) $codigo => [$codigo];
        }
    }

    #[DataProvider('metodos')]
    public function testQuedaEmConexaoNovaNaoRepete(string $metodo): void
    {
        self::$servidor->configurar(['modo' => 'queda']);
        try {
            $this->transporte()->executar($metodo, '/queda', corpo: $metodo === 'POST' ? (object) ['texto' => 'sintetico'] : null);
            self::fail('A queda deveria interromper a operação.');
        } catch (ErroTransporte $erro) {
            self::assertSame('falha_de_rede', $erro->codigo);
        }
        self::assertCount(1, self::$servidor->registros());
    }

    public function testRespostasVaziasJsonMalformadoETextoBinario(): void
    {
        $transporte = $this->transporte();
        self::$servidor->configurar(['modo' => 'vazio']);
        $resposta = $transporte->executar('DELETE', '/sem-conteudo');
        self::assertSame(204, $resposta->status);
        self::assertNull($resposta->dados);
        try {
            $transporte->executar('GET', '/vazio');
            self::fail('JSON vazio 200 deveria ser recusado.');
        } catch (ErroResposta $erro) {
            self::assertSame('resposta_invalida', $erro->codigo);
        }
        self::assertNull($transporte->executar('POST', '/vazio', aceitarVazio: true)->dados);
        foreach (['{"id":', '{"numero":NaN}', "\xff"] as $dados) {
            self::$servidor->configurar(['modo' => 'resposta', 'corpo' => base64_encode($dados)]);
            try {
                $transporte->executar('GET', '/emails');
                self::fail('JSON inválido deveria ser recusado.');
            } catch (ErroResposta $erro) {
                self::assertSame('resposta_invalida', $erro->codigo);
            }
        }
        $mime = "From: origem@example.test\r\nSubject: sintetico\r\n\r\n\x00\xff\xfe\r\n";
        self::$servidor->configurar(['modo' => 'resposta', 'corpo' => base64_encode($mime), 'cabecalhos' => ['Content-Type' => 'message/rfc822', 'Content-Disposition' => 'attachment; filename="mensagem.eml"']]);
        $resposta = $transporte->executar('GET', '/receiving/email/raw', formato: 'bytes');
        self::assertSame($mime, $resposta->dados);
        self::assertSame('message/rfc822', $resposta->cabecalhos['content-type']);
        self::assertSame('attachment; filename="mensagem.eml"', $resposta->cabecalhos['content-disposition']);
        self::$servidor->configurar(['modo' => 'resposta', 'corpo' => base64_encode('<p>Ação e revisão</p>')]);
        self::assertSame('<p>Ação e revisão</p>', $transporte->executar('GET', '/previa', formato: 'texto')->dados);
        self::$servidor->configurar(['modo' => 'resposta', 'corpo' => base64_encode("\xff")]);
        try {
            $transporte->executar('GET', '/previa', formato: 'texto');
            self::fail('Texto UTF-8 inválido deveria ser recusado.');
        } catch (ErroResposta $erro) {
            self::assertSame('resposta_invalida', $erro->codigo);
        }
    }

    public function testContentEncodingNaoNegociadoECabecalhosMaioresQue64KibSaoRecusados(): void
    {
        $transporte = $this->transporte();
        foreach ([
            ['modo' => 'resposta', 'corpo' => base64_encode(gzencode('sintetico')), 'cabecalhos' => ['Content-Encoding' => 'gzip']],
            ['modo' => 'cabecalhosGrandes'],
        ] as $indice => $configuracao) {
            self::$servidor->configurar($configuracao);
            try {
                $transporte->executar('GET', '/receiving/email/raw', formato: 'bytes');
                self::fail('A resposta deveria ser recusada.');
            } catch (ErroResposta $erro) {
                self::assertSame($indice === 0 ? 'codificacao_inesperada' : 'resposta_excedida', $erro->codigo);
            }
            self::assertCount(1, self::$servidor->registros());
        }
    }

    public function testPrazoTotalCurlLimitaRespostaAtivaEmPartes(): void
    {
        self::$servidor->configurar(['modo' => 'fluxo', 'partes' => array_fill(0, 20, base64_encode('parte')), 'pausa' => 0.025]);
        $inicio = microtime(true);
        try {
            $this->transporte()->executar('GET', '/lento', formato: 'bytes', opcoes: ['tempoLimite' => 0.15]);
            self::fail('O prazo total deveria limitar a transferência.');
        } catch (ErroTransporte $erro) {
            self::assertSame('prazo_excedido', $erro->codigo);
        }
        self::assertLessThan(1.2, microtime(true) - $inicio);
        self::assertCount(1, self::$servidor->registros());
    }

    public function testPrazoDeConexaoLimitaHandshakeTlsParado(): void
    {
        $inicio = microtime(true);
        try {
            $this->transporte(['urlBase' => self::$servidor->info->tlsParado, 'tempoConexao' => 0.1, 'tempoLimite' => 1])->executar('GET', '/domains');
            self::fail('O prazo de conexão deveria limitar o handshake TLS.');
        } catch (ErroTransporte $erro) {
            self::assertSame('prazo_excedido', $erro->codigo);
        }
        self::assertLessThan(0.5, microtime(true) - $inicio);
        self::assertCount(0, self::$servidor->registros());
    }

    public function testLimiteAnunciadoERealDoFluxoProtegemConsumidor(): void
    {
        $transporte = $this->transporte();
        self::$servidor->configurar(['modo' => 'resposta', 'corpo' => base64_encode(str_repeat('a', 128))]);
        $partes = [];
        try {
            $transporte->executar('GET', '/grande', formato: 'fluxo', opcoes: ['limiteRespostaBytes' => 16], consumidor: static function (string $parte) use (&$partes): void {
                $partes[] = $parte;
            });
            self::fail('Content-Length deveria exceder o teto antes do consumo.');
        } catch (ErroResposta $erro) {
            self::assertSame('resposta_excedida', $erro->codigo);
        }
        self::assertSame([], $partes);
        self::$servidor->configurar(['modo' => 'fluxo', 'partes' => array_fill(0, 3, base64_encode('12345'))]);
        try {
            $transporte->executar('GET', '/fluxo', formato: 'fluxo', opcoes: ['limiteRespostaBytes' => 10], consumidor: static function (string $parte) use (&$partes): void {
                $partes[] = $parte;
            });
            self::fail('O teto real deveria interromper o consumo.');
        } catch (ErroResposta $erro) {
            self::assertSame('resposta_excedida', $erro->codigo);
        }
        self::assertLessThanOrEqual(10, strlen(implode('', $partes)));
    }

    public function testCallbackRecebePrimeiraParteAntesDoFimDaResposta(): void
    {
        self::$servidor->configurar(['modo' => 'fluxo', 'partes' => [base64_encode('primeira'), base64_encode('segunda')], 'esperarConsumidor' => true]);
        $partes = [];
        $resposta = $this->transporte()->executar('GET', '/fluxo', formato: 'fluxo', consumidor: static function (string $parte) use (&$partes): void {
            $partes[] = $parte;
            if (count($partes) === 1) {
                self::$servidor->liberar();
            }
        });
        self::assertSame('primeirasegunda', implode('', $partes));
        self::assertGreaterThanOrEqual(2, count($partes));
        self::assertSame(15, $resposta->dados);
    }

    public function testFluxo8MibUsaMemoriaLimitadaEDevolveContagem(): void
    {
        self::$servidor->configurar(['modo' => 'fluxoGrande']);
        $transporte = $this->transporte();
        $hash = hash_init('sha256');
        $total = 0;
        $chamadas = 0;
        memory_reset_peak_usage();
        $antes = memory_get_usage(true);
        $resposta = $transporte->executar('GET', '/fluxo', formato: 'fluxo', consumidor: static function (string $parte) use ($hash, &$total, &$chamadas): void {
            $total += strlen($parte);
            ++$chamadas;
            hash_update($hash, $parte);
        });
        $acrescimo = memory_get_peak_usage(true) - $antes;
        self::assertSame(8 * 1024 * 1024, $total);
        self::assertSame($total, $resposta->dados);
        self::assertGreaterThan(1, $chamadas);
        self::assertLessThan(4 * 1024 * 1024, $acrescimo);
        self::assertSame(hash('sha256', str_repeat('a', 8 * 1024 * 1024)), hash_final($hash));
    }

    public function testErroOriginalDoConsumidorAbortandoFluxoELiberacaoDoCliente(): void
    {
        self::$servidor->configurar(['modo' => 'fluxo', 'partes' => array_fill(0, 20, base64_encode('parte')), 'pausa' => 0.005]);
        $transporte = $this->transporte();
        $original = new RuntimeException('cancelamento sintético do consumidor');
        $chamadas = 0;
        try {
            $transporte->executar('GET', '/fluxo', formato: 'fluxo', consumidor: static function (string $_parte) use ($original, &$chamadas): never {
                ++$chamadas;
                throw $original;
            });
            self::fail('O consumidor deveria cancelar a transferência.');
        } catch (RuntimeException $erro) {
            self::assertSame($original, $erro);
        }
        self::assertSame(1, $chamadas);
        self::assertCount(1, self::$servidor->registros());
        self::assertSame(200, $transporte->executar('GET', '/ok')->status);
        self::assertCount(2, self::$servidor->registros());
    }

    public function testReentranciaEFechamentoDuranteCallbackSaoRecusados(): void
    {
        self::$servidor->configurar(['modo' => 'fluxo', 'esperarConsumidor' => true]);
        $transporte = $this->transporte();
        $recusadas = 0;
        $primeira = true;
        $transporte->executar('GET', '/fluxo', formato: 'fluxo', consumidor: static function (string $_parte) use ($transporte, &$recusadas, &$primeira): void {
            if (!$primeira) {
                return;
            }
            $primeira = false;
            foreach ([static fn () => $transporte->executar('GET', '/ok'), static fn () => $transporte->fechar()] as $operacao) {
                try {
                    $operacao();
                    self::fail('Operação reentrante deveria ser recusada.');
                } catch (ErroConfiguracao) {
                    ++$recusadas;
                }
            }
            self::$servidor->liberar();
        });
        self::assertSame(2, $recusadas);
        self::assertCount(1, self::$servidor->registros());
        self::assertSame(200, $transporte->executar('GET', '/ok')->status);
    }

    #[DataProvider('respostasTruncadas')]
    public function testMimeCsvEJsonTruncadosNaoSaoAnunciadosCompletos(string $formato, string $moldura, string $corpo): void
    {
        self::$servidor->configurar(['modo' => 'truncada', 'moldura' => $moldura, 'corpo' => base64_encode($corpo)]);
        try {
            $this->transporte()->executar('GET', '/truncada', formato: $formato, consumidor: $formato === 'fluxo' ? static function (string $_parte): void {
            } : null);
            self::fail('A resposta truncada deveria falhar.');
        } catch (ErroTransporte $erro) {
            self::assertContains($erro->codigo, ['resposta_incompleta', 'falha_de_rede']);
        }
        self::assertCount(1, self::$servidor->registros());
    }

    public static function respostasTruncadas(): iterable
    {
        yield 'JSON Content-Length' => ['json', 'tamanho', '{"id":"parcial"}'];
        yield 'MIME Content-Length' => ['bytes', 'tamanho', "From: origem@example.test\r\n\r\nMIME parcial"];
        yield 'CSV Content-Length' => ['fluxo', 'tamanho', "email,nome\r\nusuario@example.test,Parcial\r\n"];
        yield 'MIME chunked' => ['bytes', 'chunked', "From: origem@example.test\r\n\r\nMIME parcial"];
        yield 'CSV chunked' => ['fluxo', 'chunked', "email,nome\r\nusuario@example.test,Parcial\r\n"];
    }

    public function testJsonEscapadoDeCsvPodeUltrapassar8MibMasTeto50MibEAplicado(): void
    {
        $transporte = $this->transporte(['tempoLimite' => 10]);
        $conteudo = "email,primeiro_nome\nusuario@example.test,\"".str_repeat('""', 2 * 1024 * 1024 + 64)."\"\n";
        self::assertLessThan(8 * 1024 * 1024, strlen($conteudo));
        $transporte->executar('POST', '/contacts/imports', corpo: (object) ['arquivo_nome' => 'contatos.csv', 'conteudo' => $conteudo, 'mapeamento' => (object) ['0' => 'email', '1' => 'primeiro_nome']]);
        $registros = self::$servidor->registros();
        self::assertCount(1, $registros);
        self::assertGreaterThan(8 * 1024 * 1024, $registros[0]->corpoTamanho);
        foreach ([NAN, INF, -INF, "\xff", new \DateTimeImmutable()] as $valor) {
            try {
                $transporte->executar('POST', '/events/send', corpo: (object) ['valor' => $valor]);
                self::fail('Valor não JSON deveria ser recusado.');
            } catch (ErroConfiguracao) {
                self::assertCount(1, self::$servidor->registros());
            }
        }
        unset($conteudo);
        try {
            $transporte->executar('POST', '/contacts/imports', corpo: (object) ['conteudo' => str_repeat('x', 50 * 1024 * 1024)]);
            self::fail('O teto de 50 MiB deveria ser aplicado.');
        } catch (ErroConfiguracao) {
            self::assertCount(1, self::$servidor->registros());
        }
    }

    public function testFecharIdempotenteENovoPedidoAposFecharRecusado(): void
    {
        $transporte = $this->transporte();
        self::assertSame(200, $transporte->executar('GET', '/domains')->status);
        $transporte->fechar();
        $transporte->fechar();
        try {
            $transporte->executar('GET', '/domains');
            self::fail('Cliente encerrado deveria recusar novos pedidos.');
        } catch (ErroConfiguracao) {
            self::assertCount(1, self::$servidor->registros());
        }
    }

}

final class ServidorCurlTeste
{
    private mixed $processo;
    private array $pipes = [];
    public readonly stdClass $info;

    public function __construct()
    {
        $this->processo = proc_open(['python3', __DIR__.'/servidor_https.py'], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $this->pipes, __DIR__);
        if (!is_resource($this->processo)) {
            throw new RuntimeException('Não foi possível iniciar o laboratório HTTPS.');
        }
        stream_set_timeout($this->pipes[1], 10);
        $this->info = $this->ler();
    }

    public function configurar(array $configuracao): void
    {
        $this->comando(['acao' => 'configurar', 'configuracao' => (object) $configuracao]);
    }

    public function registros(): array
    {
        return $this->comando(['acao' => 'registros']);
    }

    public function liberar(): void
    {
        $this->comando(['acao' => 'liberar']);
    }

    public function encerrar(): void
    {
        $this->comando(['acao' => 'encerrar']);
        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }
        $codigo = proc_close($this->processo);
        if ($codigo !== 0) {
            throw new RuntimeException('O laboratório HTTPS não encerrou normalmente.');
        }
    }

    private function comando(array $comando): mixed
    {
        fwrite($this->pipes[0], json_encode($comando, JSON_THROW_ON_ERROR)."\n");
        fflush($this->pipes[0]);
        return $this->ler();
    }

    private function ler(): mixed
    {
        $linha = fgets($this->pipes[1]);
        if ($linha === false) {
            throw new RuntimeException('O laboratório HTTPS não respondeu ao controlador.');
        }
        return json_decode($linha, false, 512, JSON_THROW_ON_ERROR);
    }
}

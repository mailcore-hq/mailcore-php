<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Mailcore\Exemplos\Symfony\EnviarControlador;
use Mailcore\Exemplos\Symfony\FabricaClientePsr18;
use Mailcore\Exemplos\Symfony\ServicoEnvio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class SymfonyTest extends TestCase
{
    private const TOKEN = 'token_sintetico_aplicacao_Ab9_Ab9_Ab9_Ab9_Ab9_Ab9_Ab9_';
    private const CHAVE_API = 'mc_chave_sintetica_API_0123456789_0123456789';

    /** @var list<array{metodo: string, url: string, opcoes: array<string, mixed>}> */
    private array $chamadas = [];

    private function kernel(?callable $responder = null, ?array $ambiente = null): HttpKernel
    {
        $http = new MockHttpClient(function (string $metodo, string $url, array $opcoes) use ($responder): MockResponse {
            $this->chamadas[] = ['metodo' => $metodo, 'url' => $url, 'opcoes' => $opcoes];
            return $responder !== null ? $responder() : new MockResponse(
                '{"id":"email-sintetico","estado":"na_fila","interno":"nao-publicar"}',
                ['http_code' => 202, 'response_headers' => ['content-type: application/json', 'x-interno: protegido']],
            );
        });
        $cliente = FabricaClientePsr18::criar(self::CHAVE_API, ['urlBase' => 'https://api-mailcore.example.test/v1'], $http);
        $servico = new ServicoEnvio($cliente, $ambiente ?? $this->ambiente());
        $rotas = new RouteCollection();
        $rotas->add('mailcore_enviar', new Route('/enviar', ['_controller' => new EnviarControlador($servico)], methods: ['POST']));
        $eventos = new EventDispatcher();
        $pilha = new RequestStack();
        $eventos->addSubscriber(new RouterListener(new UrlMatcher($rotas, new RequestContext()), $pilha));
        // O Kernel mínimo do teste precisa do tratamento de erros de roteamento que a aplicação normalmente configura.
        $eventos->addListener(KernelEvents::EXCEPTION, static function (ExceptionEvent $evento): void {
            $erro = $evento->getThrowable();
            if ($erro instanceof HttpExceptionInterface) {
                $evento->setResponse(new JsonResponse(['erro' => 'Requisição inválida.'], $erro->getStatusCode(), $erro->getHeaders()));
            }
        });
        return new HttpKernel($eventos, new ControllerResolver(), $pilha, new ArgumentResolver());
    }

    /** @return array<string, string> */
    private function ambiente(): array
    {
        return [
            'TOKEN_APLICACAO' => self::TOKEN,
            'MAILCORE_API_KEY' => self::CHAVE_API,
            'MAILCORE_REMETENTE' => 'Equipe <equipe@example.test>',
            'MAILCORE_DESTINATARIO' => 'destinatario@example.test',
        ];
    }

    private function requisicao(string $corpo = '{}', array $cabecalhos = [], string $metodo = 'POST', string $caminho = '/enviar'): Request
    {
        $requisicao = Request::create($caminho, $metodo, server: [], content: $corpo);
        $requisicao->headers->replace([
            'Authorization' => 'Bearer ' . self::TOKEN,
            'Idempotency-Key' => 'pedido-sintetico-1',
            'Content-Type' => 'application/json',
            ...$cabecalhos,
        ]);
        return $requisicao;
    }

    private function cabecalhoEnviado(string $nome): ?string
    {
        foreach ($this->chamadas[0]['opcoes']['headers'] as $chave => $valor) {
            if (is_string($chave) && strtolower($chave) === strtolower($nome)) {
                return is_array($valor) ? implode(', ', $valor) : $valor;
            }
            if (is_string($valor) && str_starts_with(strtolower($valor), strtolower($nome) . ':')) {
                return trim(substr($valor, strlen($nome) + 1));
            }
        }
        return null;
    }

    public function testKernelRealEnviaSdkComEnvelopeFixo(): void
    {
        $corpo = '{"remetente":"invasor@example.test","destinatarios":["outro@example.test"],"urlBase":"https://outro.example.test","clienteHttp":"proibido","assunto":"alterado"}';
        $resposta = $this->kernel()->handle($this->requisicao($corpo, ['Content-Type' => 'application/json; charset=utf-8'], caminho: '/enviar?destinatario=outro@example.test'));
        self::assertSame(202, $resposta->getStatusCode());
        self::assertSame(['id' => 'email-sintetico', 'estado' => 'na_fila'], json_decode($resposta->getContent(), true, flags: JSON_THROW_ON_ERROR));
        self::assertTrue($resposta->headers->hasCacheControlDirective('no-store'));
        self::assertFalse($resposta->headers->has('x-interno'));
        self::assertCount(1, $this->chamadas);
        self::assertSame('POST', $this->chamadas[0]['metodo']);
        self::assertSame('https://api-mailcore.example.test/v1/emails', $this->chamadas[0]['url']);
        self::assertSame('Bearer ' . self::CHAVE_API, $this->cabecalhoEnviado('Authorization'));
        self::assertSame('pedido-sintetico-1', $this->cabecalhoEnviado('Idempotency-Key'));
        self::assertSame([
            'remetente' => 'Equipe <equipe@example.test>',
            'destinatarios' => ['destinatario@example.test'],
            'assunto' => 'Mensagem de exemplo',
            'texto' => 'Integração Mailcore com Symfony.',
        ], json_decode((string) $this->chamadas[0]['opcoes']['body'], true, flags: JSON_THROW_ON_ERROR));
    }

    /** @return iterable<string, array{string, array<string, mixed>, int}> */
    public static function pedidosInvalidos(): iterable
    {
        yield 'token ausente' => ['{}', ['Authorization' => ''], 401];
        yield 'token incorreto' => ['{}', ['Authorization' => 'Bearer ' . str_repeat('x', 43)], 401];
        yield 'chave API não autentica aplicação' => ['{}', ['Authorization' => 'Bearer ' . self::CHAVE_API], 401];
        yield 'autorização duplicada' => ['{}', ['Authorization' => ['Bearer ' . self::TOKEN, 'Bearer ' . self::TOKEN]], 401];
        yield 'autorização combinada' => ['{}', ['Authorization' => 'Bearer ' . self::TOKEN . ', Bearer outro'], 401];
        yield 'injeção de cabeçalho' => ['{}', ['Authorization' => 'Bearer ' . self::TOKEN . "\r\nX: segredo"], 401];
        yield 'idempotência ausente' => ['{}', ['Idempotency-Key' => ''], 400];
        yield 'idempotência duplicada' => ['{}', ['Idempotency-Key' => ['a', 'b']], 400];
        yield 'idempotência combinada' => ['{}', ['Idempotency-Key' => 'a,b'], 400];
        yield 'idempotência grande' => ['{}', ['Idempotency-Key' => str_repeat('a', 257)], 400];
        yield 'idempotência com controle' => ['{}', ['Idempotency-Key' => "pedido\r\nX: segredo"], 400];
        yield 'tipo não JSON' => ['{}', ['Content-Type' => 'text/plain'], 415];
        yield 'JSON vazio' => ['', [], 400];
        yield 'JSON incompleto' => ['{', [], 400];
        yield 'lista não é objeto' => ['[]', [], 400];
        yield 'escalar não é objeto' => ['"texto"', [], 400];
        yield 'UTF-8 inválido' => ["{\"texto\":\"\xff\"}", [], 400];
        yield 'corpo grande sem tamanho' => [str_repeat(' ', 4097), [], 413];
        yield 'tamanho declarado grande' => ['{}', ['Content-Length' => '4097'], 413];
    }

    #[DataProvider('pedidosInvalidos')]
    public function testRejeitaAntesDeChamarApi(string $corpo, array $cabecalhos, int $status): void
    {
        $resposta = $this->kernel()->handle($this->requisicao($corpo, $cabecalhos));
        self::assertSame($status, $resposta->getStatusCode());
        self::assertSame([], $this->chamadas);
        $this->assertSemSegredos($resposta);
    }

    public function testRoteamentoRealRestringeMetodoECaminho(): void
    {
        $kernel = $this->kernel();
        self::assertSame(405, $kernel->handle($this->requisicao(metodo: 'GET'))->getStatusCode());
        self::assertSame(404, $kernel->handle($this->requisicao(caminho: '/outro'))->getStatusCode());
        self::assertSame([], $this->chamadas);
    }

    public function testLeituraSemContentLengthFicaLimitadaAntesDoJson(): void
    {
        if (!in_array('mailcorecorpo', stream_get_wrappers(), true)) {
            stream_wrapper_register('mailcorecorpo', CorpoVirtualSymfony::class);
        }
        CorpoVirtualSymfony::$lidos = 0;
        $entrada = fopen('mailcorecorpo://sintetico', 'rb');
        self::assertIsResource($entrada);
        try {
            $requisicao = new Request(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/enviar'], content: $entrada);
            $requisicao->headers->replace([
                'Authorization' => 'Bearer ' . self::TOKEN,
                'Idempotency-Key' => 'operacao-grande',
                'Content-Type' => 'application/json',
            ]);
            self::assertFalse($requisicao->headers->has('content-length'));
            $resposta = $this->kernel()->handle($requisicao);
            self::assertSame(413, $resposta->getStatusCode());
            self::assertGreaterThan(0, CorpoVirtualSymfony::$lidos);
            // O buffer de streams do PHP pode antecipar um bloco de 8192 bytes.
            self::assertLessThanOrEqual(8192, CorpoVirtualSymfony::$lidos);
            self::assertSame([], $this->chamadas);
        } finally {
            fclose($entrada);
        }
    }

    public function testRepeticaoMantemChaveSemRepeticaoAutomatica(): void
    {
        $kernel = $this->kernel();
        self::assertSame(202, $kernel->handle($this->requisicao())->getStatusCode());
        self::assertSame(202, $kernel->handle($this->requisicao())->getStatusCode());
        self::assertCount(2, $this->chamadas);
        self::assertSame($this->chamadas[0]['opcoes']['body'], $this->chamadas[1]['opcoes']['body']);
        self::assertSame($this->chamadas[0]['opcoes']['headers'], $this->chamadas[1]['opcoes']['headers']);
    }

    /** @return iterable<string, array{int, string, int}> */
    public static function respostasInvalidas(): iterable
    {
        yield 'erro validacao' => [422, '{"codigo":"validacao","mensagem":"segredo_interno"}', 422];
        yield 'indisponibilidade desconhecida' => [503, '{"erro":"segredo_interno"}', 503];
        yield 'redirecionamento' => [302, '{"segredo":"segredo_interno"}', 502];
        yield 'aceitacao incompleta' => [202, '{"id":"id"}', 502];
        yield 'sucesso inesperado' => [200, '{"id":"id","estado":"na_fila"}', 502];
    }

    #[DataProvider('respostasInvalidas')]
    public function testErrosSaoSanitizadosSemRetry(int $codigo, string $corpo, int $esperado): void
    {
        $kernel = $this->kernel(static fn (): MockResponse => new MockResponse($corpo, [
            'http_code' => $codigo,
            'response_headers' => ['content-type: application/json', 'x-segredo: segredo_interno', 'location: https://outro.example.test/'],
        ]));
        $resposta = $kernel->handle($this->requisicao());
        self::assertSame($esperado, $resposta->getStatusCode());
        self::assertCount(1, $this->chamadas);
        self::assertStringNotContainsString('segredo_interno', $resposta->getContent());
        self::assertFalse($resposta->headers->has('x-segredo'));
        self::assertFalse($resposta->headers->has('location'));
        $this->assertSemSegredos($resposta);
    }

    public function testErroTransporteNaoExpoeMensagemDoProvedor(): void
    {
        $resposta = $this->kernel(static function (): never {
            throw new TransportException('segredo_interno ' . self::CHAVE_API);
        })->handle($this->requisicao());
        self::assertSame(502, $resposta->getStatusCode());
        self::assertCount(1, $this->chamadas);
        self::assertStringNotContainsString('segredo_interno', $resposta->getContent());
        $this->assertSemSegredos($resposta);
    }

    public function testConfiguracaoRejeitaTokenCurtoECompartilhado(): void
    {
        foreach ([str_repeat('a', 20), str_repeat('a', 43)] as $token) {
            $ambiente = $this->ambiente();
            $ambiente['TOKEN_APLICACAO'] = $token;
            if (strlen($token) === 43) {
                $ambiente['MAILCORE_API_KEY'] = $token;
            }
            try {
                $this->kernel(ambiente: $ambiente);
                self::fail('A configuração inválida foi aceita.');
            } catch (\InvalidArgumentException $erro) {
                self::assertStringNotContainsString($token, $erro->getMessage());
            }
        }
    }

    private function assertSemSegredos(Response $resposta): void
    {
        self::assertStringNotContainsString(self::TOKEN, $resposta->getContent());
        self::assertStringNotContainsString(self::CHAVE_API, $resposta->getContent());
    }
}

/** Corpo sintético virtual de 1 GiB; o teste não aloca esse volume. */
final class CorpoVirtualSymfony
{
    public mixed $context;
    public static int $lidos = 0;
    private int $posicao = 0;

    public function stream_open(string $caminho, string $modo, int $opcoes, ?string &$caminhoAberto): bool
    {
        return true;
    }

    public function stream_read(int $quantidade): string
    {
        $tamanho = min($quantidade, 1073741824 - $this->posicao);
        $this->posicao += $tamanho;
        self::$lidos += $tamanho;
        return str_repeat(' ', $tamanho);
    }

    public function stream_eof(): bool
    {
        return $this->posicao >= 1073741824;
    }

    public function stream_seek(int $deslocamento, int $origem = SEEK_SET): bool
    {
        if ($origem !== SEEK_SET || $deslocamento < 0 || $deslocamento > 1073741824) {
            return false;
        }
        $this->posicao = $deslocamento;
        return true;
    }

    public function stream_tell(): int
    {
        return $this->posicao;
    }

    public function stream_stat(): array
    {
        return [];
    }
}

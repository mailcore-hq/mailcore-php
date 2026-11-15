<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Mailcore\Cliente;
use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Excecoes\ErroResposta;
use Mailcore\Excecoes\ErroTransporte;
use Mailcore\Resposta;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CoreTest extends TestCase
{
    public function testFornecedorInvalidoNaoHerdaChaveGlobal(): void
    {
        $original = getenv('MAILCORE_API_KEY');
        putenv('MAILCORE_API_KEY=mc_global_privilegiada');
        $chamadas = 0;
        $http = new Psr18Client(new MockHttpClient(static function () use (&$chamadas): MockResponse {
            $chamadas++;
            return new MockResponse('{}');
        }));
        try {
            $cliente = new Cliente(static fn () => null, ['clienteHttp' => $http]);
            try {
                $cliente->emails->listar();
                self::fail('Fornecedor sem chave foi aceito.');
            } catch (ErroConfiguracao $erro) {
                self::assertStringNotContainsString('mc_global', $erro->getMessage());
                self::assertNull($erro->getPrevious());
            }
            self::assertSame(0, $chamadas);
        } finally {
            putenv($original === false ? 'MAILCORE_API_KEY' : 'MAILCORE_API_KEY='.$original);
        }
    }

    public function testObjetosListasValoresNumericosENulosNaoSaoConfundidos(): void
    {
        $pedidos = [];
        $http = new Psr18Client(new MockHttpClient(static function (string $_metodo, string $_url, array $opcoes) use (&$pedidos): MockResponse {
            $pedidos[] = json_decode($opcoes['body'], false, 512, JSON_THROW_ON_ERROR);
            return new MockResponse('{"objeto":{},"lista":[],"nulo":null,"ativo":false,"grande":123456789012345678901234567890}', ['response_headers' => ['content-type' => 'application/json']]);
        }));
        $cliente = new Cliente('mc_sintetica', ['clienteHttp' => $http]);
        $dados = ['esquema' => (object) [], 'propriedades' => (object) ['0' => 'email'], 'valor' => null, 'ativo' => false, 'lista' => []];
        $resposta = $cliente->eventos->criar($dados);
        self::assertInstanceOf(\stdClass::class, $pedidos[0]->esquema);
        self::assertSame('email', $pedidos[0]->propriedades->{'0'});
        self::assertNull($pedidos[0]->valor);
        self::assertFalse($pedidos[0]->ativo);
        self::assertSame([], $pedidos[0]->lista);
        self::assertInstanceOf(\stdClass::class, $resposta['objeto']);
        self::assertSame([], $resposta['lista']);
        self::assertNull($resposta['nulo']);
        self::assertFalse($resposta['ativo']);
        self::assertSame('123456789012345678901234567890', $resposta['grande']);
        $cliente->fechar();
        $cliente->fechar();
        self::assertEquals((object) [], $dados['esquema']);
    }

    public function testDebugNaoExibeCredenciaisOuConteudo(): void
    {
        $cliente = new Cliente('mc_segredo_sintetico');
        $resposta = new Resposta((object) ['segredo' => 'conteudo_privado'], 200);
        ob_start();
        var_dump($cliente, $cliente->emails, $resposta);
        $debug = ob_get_clean();
        self::assertIsString($debug);
        self::assertStringNotContainsString('mc_segredo_sintetico', $debug);
        self::assertStringNotContainsString('conteudo_privado', $debug);
        $cliente->fechar();
    }

    public function testArrayAccessSomenteLeituraComNullPreservado(): void
    {
        $resposta = new Resposta((object) ['nome' => null, 'ativo' => false], 200);
        self::assertNull($resposta['nome']);
        self::assertFalse(isset($resposta['nome']));
        self::assertTrue(property_exists($resposta->dados, 'nome'));
        self::assertFalse($resposta['ativo']);
        $this->expectException(\LogicException::class);
        $resposta['ativo'] = true;
    }

    public function testFalhasDeLeituraPsr7SaoSanitizadasEFechamOFluxo(): void
    {
        foreach (['rewind', 'read', 'eof', 'close'] as $metodo) {
            $fluxo = $this->createMock(StreamInterface::class);
            $fluxo->method('isSeekable')->willReturn($metodo === 'rewind');
            if ($metodo !== 'read') {
                $fluxo->method('read')->willReturn('');
            }
            if ($metodo !== 'eof') {
                $fluxo->method('eof')->willReturn(true);
            }
            $fluxo->method($metodo)->willThrowException(new \RuntimeException('detalhes_privados_do_provedor'));
            $fluxo->expects(self::once())->method('close');
            $http = $this->createMock(ClientInterface::class);
            $http->method('sendRequest')->willReturn(new Response(200, [], $fluxo));
            $cliente = new Cliente('mc_sintetica', ['clienteHttp' => $http]);
            try {
                $cliente->recebimento->baixarOriginal('id');
                self::fail('Falha de fluxo não foi propagada.');
            } catch (ErroTransporte $erro) {
                self::assertStringNotContainsString('detalhes_privados', $erro->getMessage());
                self::assertNull($erro->getPrevious());
            }
        }
    }

    public function testErroDeFechamentoNaoSubstituiErroDoConsumidor(): void
    {
        $fluxo = $this->createMock(StreamInterface::class);
        $fluxo->method('isSeekable')->willReturn(false);
        $fluxo->method('read')->willReturn('parte');
        $fluxo->expects(self::once())->method('close')->willThrowException(new \RuntimeException('erro_privado_ao_fechar'));
        $http = $this->createMock(ClientInterface::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $fluxo));
        $cliente = new Cliente('mc_sintetica', ['clienteHttp' => $http]);
        $original = new \LogicException('consumidor_interrompeu');
        try {
            $cliente->recebimento->baixarOriginalFluxo('id', static function () use ($original): void {
                throw $original;
            });
            self::fail('Consumidor não interrompeu a leitura.');
        } catch (\LogicException $erro) {
            self::assertSame($original, $erro);
        }
    }

    public function testFluxoPsr7FechaMesmoSeMetadadosForemInvalidos(): void
    {
        $fluxo = $this->createMock(StreamInterface::class);
        $fluxo->expects(self::once())->method('close');
        $resposta = new class (200, [], $fluxo) extends Response {
            public function getHeaders(): array
            {
                throw new \RuntimeException('cabecalho_privado_do_provedor');
            }
        };
        $http = $this->createMock(ClientInterface::class);
        $http->method('sendRequest')->willReturn($resposta);
        $cliente = new Cliente('mc_sintetica', ['clienteHttp' => $http]);
        try {
            $cliente->recebimento->baixarOriginal('id');
            self::fail('Metadados inválidos foram aceitos.');
        } catch (ErroTransporte $erro) {
            self::assertSame('resposta_incompleta', $erro->codigo);
            self::assertStringNotContainsString('cabecalho_privado', $erro->getMessage());
            self::assertNull($erro->getPrevious());
        }
    }

    public function testLimiteDeRespostaNaoExibeConteudoAcumuladoNoTrace(): void
    {
        $privado = 'conteudo_privado_de_mensagem';
        $http = new Psr18Client(new MockHttpClient(new MockResponse($privado.str_repeat('a', 12000))));
        $cliente = new Cliente('mc_sintetica', ['clienteHttp' => $http, 'limiteRespostaBytes' => 10000]);
        $anterior = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');
        try {
            $cliente->recebimento->baixarOriginal('id');
            self::fail('Resposta grande foi aceita.');
        } catch (ErroResposta $erro) {
            self::assertSame('resposta_excedida', $erro->codigo);
            self::assertStringNotContainsString($privado, print_r($erro->getTrace(), true));
            self::assertStringNotContainsString('Bearer mc_sintetica', print_r($erro->getTrace(), true));
        } finally {
            ini_set('zend.exception_ignore_args', $anterior);
        }
    }
}

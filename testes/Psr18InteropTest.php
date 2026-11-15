<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Mailcore\Cliente;
use Mailcore\Excecoes\ErroApi;
use Mailcore\Exemplos\Symfony\FabricaClientePsr18;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

final class Psr18InteropTest extends TestCase
{
    public function testClienteRealComFactoriesPsr17Explicitas(): void
    {
        $http = new MockHttpClient(new MockResponse('{"id":"email-sintetico","estado":"na_fila"}', [
            'http_code' => 202,
            'response_headers' => ['content-type: application/json', 'x-request-id: requisicao-sintetica'],
        ]));
        $fabrica = new Psr17Factory();
        $cliente = new Cliente('mc_chave_sintetica', [
            'urlBase' => 'https://api-mailcore.example.test/v1',
            'clienteHttp' => new Psr18Client($http, $fabrica, $fabrica),
            'fabricaRequisicoes' => $fabrica,
            'fabricaFluxos' => $fabrica,
        ]);
        $resposta = $cliente->emails->enviar(['remetente' => 'equipe@example.test', 'destinatarios' => ['destino@example.test'], 'texto' => 'Olá'], ['chaveIdempotencia' => 'operacao-sintetica']);
        self::assertSame(202, $resposta->status);
        self::assertInstanceOf(\stdClass::class, $resposta->dados);
        self::assertSame('email-sintetico', $resposta['id']);
        self::assertSame('requisicao-sintetica', $resposta->cabecalhos['x-request-id']);
        self::assertSame(1, $http->getRequestsCount());
    }

    public function testFactoryAplicaPoliticaExplicitaAoProvedor(): void
    {
        $capturadas = [];
        $http = new MockHttpClient(static function (string $metodo, string $url, array $opcoes) use (&$capturadas): MockResponse {
            $capturadas = $opcoes;
            return new MockResponse('{"dados":[],"proximo":null,"tem_mais":false}', ['response_headers' => ['content-type: application/json']]);
        });
        $cliente = FabricaClientePsr18::criar('mc_chave_sintetica', ['urlBase' => 'https://api-mailcore.example.test/v1', 'tempoLimite' => 7], $http);
        $resposta = $cliente->eventos->listar(['limite' => 1]);
        self::assertSame([], $resposta->dados->dados);
        self::assertNull($resposta->dados->proximo);
        self::assertFalse($resposta->dados->tem_mais);
        self::assertSame(7.0, $capturadas['timeout']);
        self::assertSame(7.0, $capturadas['max_duration']);
        self::assertSame(0, $capturadas['max_redirects']);
        self::assertTrue($capturadas['verify_peer']);
        self::assertTrue($capturadas['verify_host']);
        self::assertSame('', $capturadas['proxy']);
        self::assertSame('*', $capturadas['no_proxy']);
    }

    public function testErroHttpDesconhecidoContinuaSendoErro(): void
    {
        $chamadas = 0;
        $http = new MockHttpClient(static function () use (&$chamadas): MockResponse {
            ++$chamadas;
            return new MockResponse('{"erro_desconhecido":"detalhe_privado"}', [
                'http_code' => 503, 'response_headers' => ['content-type: application/json'],
            ]);
        });
        $cliente = FabricaClientePsr18::criar('mc_chave_sintetica', ['urlBase' => 'https://api-mailcore.example.test/v1'], $http);
        try {
            $cliente->eventos->listar();
            self::fail('O status de erro foi aceito como sucesso.');
        } catch (ErroApi $erro) {
            self::assertSame(503, $erro->status);
            self::assertStringNotContainsString('detalhe_privado', $erro->getMessage());
        }
        self::assertSame(1, $chamadas);
    }

    public function testDownloadAutenticadoPreservaMimeBinarioPorCallback(): void
    {
        $original = "From: origem@example.test\r\nTo: destino@example.test\r\nSubject: MIME\r\n\r\n\x00\xffconteudo\r\n";
        $urlRecebida = '';
        $chamadas = 0;
        $http = new MockHttpClient(static function (string $metodo, string $url) use ($original, &$urlRecebida, &$chamadas): MockResponse {
            ++$chamadas;
            $urlRecebida = $url;
            return new MockResponse($original, ['response_headers' => ['content-type: message/rfc822']]);
        });
        $cliente = FabricaClientePsr18::criar('mc_chave_sintetica', ['urlBase' => 'https://api-mailcore.example.test/v1'], $http);
        $partes = [];
        $resposta = $cliente->recebimento->baixarOriginalFluxo('mensagem-sintetica', static function (string $parte) use (&$partes): void {
            $partes[] = $parte;
        });
        self::assertSame($original, implode('', $partes));
        self::assertSame(strlen($original), $resposta->dados);
        self::assertSame(200, $resposta->status);
        self::assertSame('https://api-mailcore.example.test/v1/receiving/mensagem-sintetica/raw', $urlRecebida);
        self::assertSame(1, $chamadas);
    }
}

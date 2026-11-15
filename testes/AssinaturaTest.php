<?php

declare(strict_types=1);

namespace Mailcore\Testes;

use Mailcore\Assinatura;
use Mailcore\Excecoes\ErroAssinatura;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssinaturaTest extends TestCase
{
    private const SEGREDO = 'whsec_Y2hhdmUtc2ludGV0aWNhLTMyLWJ5dGVzLXRlc3RlLTEyMw==';
    private const CORPO = '{"type":"email.received","created_at":"2026-11-14T12:00:00Z","data":{"email_id":"sintetico","texto":"Olá"}}';
    private const CABECALHOS = ['svix-id' => 'evento-sintetico', 'svix-timestamp' => '1794657600', 'svix-signature' => 'v1,m9hOSHQVO80Rb70GMzUg63QIVu/RqjtC5MC5rCjISXM='];

    public function testVetorIndependenteNodeCryptoUtf8ERotacao(): void
    {
        $evento = Assinatura::verificar(self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657600);
        self::assertSame('Olá', $evento->data->texto);
        $rotacao = self::CABECALHOS;
        $rotacao['svix-signature'] = 'v1,AAAA v0,ignorada '.self::CABECALHOS['svix-signature'];
        self::assertEquals($evento, Assinatura::verificar(self::CORPO, $rotacao, self::SEGREDO, 1794657600));
        $wsgi = [];
        foreach (self::CABECALHOS as $nome => $valor) {
            $wsgi['HTTP_'.strtoupper(str_replace('-', '_', $nome))] = [$valor];
        }
        self::assertEquals($evento, Assinatura::verificar(self::CORPO, $wsgi, self::SEGREDO, 1794657600));
    }

    /** @return iterable<string, array{string, array<string, string|list<string>>, string, int, int}> */
    public static function invalidos(): iterable
    {
        yield 'corpo alterado' => [self::CORPO.' ', self::CABECALHOS, self::SEGREDO, 1794657600, 300];
        yield 'sem cabecalhos' => [self::CORPO, [], self::SEGREDO, 1794657600, 300];
        yield 'passado' => [self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657901, 300];
        yield 'futuro' => [self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657299, 300];
        yield 'segredo invalido' => [self::CORPO, self::CABECALHOS, 'whsec_???', 1794657600, 300];
        yield 'segredo curto' => [self::CORPO, self::CABECALHOS, 'whsec_YQ==', 1794657600, 300];
        yield 'duplicado' => [self::CORPO, self::CABECALHOS + ['Svix-Id' => 'outro'], self::SEGREDO, 1794657600, 300];
        yield 'lista ambigua' => [self::CORPO, ['svix-id' => ['primeiro', 'segundo']] + self::CABECALHOS, self::SEGREDO, 1794657600, 300];
        yield 'timestamp decimal' => [self::CORPO, ['svix-timestamp' => '1794657600.0'] + self::CABECALHOS, self::SEGREDO, 1794657600, 300];
        yield 'sem janela' => [self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657600, 0];
        yield 'janela excessiva' => [self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657600, 301];
    }

    /** @param array<string, string|list<string>> $cabecalhos */
    #[DataProvider('invalidos')]
    public function testRecusaSemRevelarSegredo(string $corpo, array $cabecalhos, string $segredo, int $agora, int $tolerancia): void
    {
        try {
            Assinatura::verificar($corpo, $cabecalhos, $segredo, $agora, $tolerancia);
            self::fail('O evento deveria ser recusado.');
        } catch (ErroAssinatura $erro) {
            self::assertSame('assinatura_invalida', $erro->codigo);
            self::assertNull($erro->getPrevious());
            self::assertStringNotContainsString($segredo, $erro->getMessage());
        }
    }

    public function testJanelaInclusivaEEnvelopeAutenticadoInvalido(): void
    {
        foreach ([-300, 300] as $desvio) {
            self::assertSame('email.received', Assinatura::verificar(self::CORPO, self::CABECALHOS, self::SEGREDO, 1794657600 + $desvio)->type);
        }
        foreach (['{}', '[]', '{"type":1,"created_at":"data","data":null}', '{"type":"email.received","created_at":"data","data":NaN}'] as $corpo) {
            $cabecalhos = self::CABECALHOS;
            $cabecalhos['svix-signature'] = 'v1,'.base64_encode(hash_hmac('sha256', 'evento-sintetico.1794657600.'.$corpo, base64_decode(substr(self::SEGREDO, 6), true), true));
            try {
                Assinatura::verificar($corpo, $cabecalhos, self::SEGREDO, 1794657600);
                self::fail('Envelope inválido foi aceito.');
            } catch (ErroAssinatura $erro) {
                self::assertSame('assinatura_invalida', $erro->codigo);
            }
        }
    }
}

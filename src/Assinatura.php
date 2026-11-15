<?php

declare(strict_types=1);

namespace Mailcore;

use Mailcore\Excecoes\ErroAssinatura;

final class Assinatura
{
    /** @param array<string, string|list<string>> $cabecalhos */
    public static function verificar(
        #[\SensitiveParameter] string $corpoBruto,
        #[\SensitiveParameter] array $cabecalhos,
        #[\SensitiveParameter] string $segredo,
        ?int $agora = null,
        int $tolerancia = 300,
    ): \stdClass {
        try {
            if (strlen($corpoBruto) > 8 * 1024 * 1024 || !str_starts_with($segredo, 'whsec_') || strlen($segredo) > 1024 || $tolerancia < 1 || $tolerancia > 300) {
                throw new \UnexpectedValueException();
            }
            $normalizados = [];
            foreach ($cabecalhos as $nome => $valor) {
                $nome = strtolower(str_replace('_', '-', $nome));
                $nome = str_starts_with($nome, 'http-') ? substr($nome, 5) : $nome;
                if (!in_array($nome, ['svix-id', 'svix-timestamp', 'svix-signature'], true)) {
                    continue;
                }
                if (array_key_exists($nome, $normalizados) || (is_array($valor) && count($valor) !== 1)) {
                    throw new \UnexpectedValueException();
                }
                $normalizados[$nome] = is_array($valor) ? array_values($valor)[0] : $valor;
            }
            $id = $normalizados['svix-id'] ?? '';
            $instante = $normalizados['svix-timestamp'] ?? '';
            $assinaturas = $normalizados['svix-signature'] ?? '';
            if (!preg_match('/\A[\x21-\x7e]{1,256}\z/D', $id) || !preg_match('/\A[1-9][0-9]{0,15}\z/D', $instante) || strlen($assinaturas) > 4096 || abs(($agora ?? time()) - (int) $instante) > $tolerancia) {
                throw new \UnexpectedValueException();
            }
            $chave = self::decodificar(substr($segredo, 6));
            if (strlen($chave) < 16) {
                throw new \UnexpectedValueException();
            }
            $esperado = hash_hmac('sha256', $id.'.'.$instante.'.'.$corpoBruto, $chave, true);
            $valido = false;
            foreach (array_slice(explode(' ', $assinaturas), 0, 32) as $assinatura) {
                $par = explode(',', $assinatura, 2);
                if (count($par) !== 2 || $par[0] !== 'v1') {
                    continue;
                }
                try {
                    $valido = hash_equals($esperado, self::decodificar($par[1])) || $valido;
                } catch (\UnexpectedValueException) {
                    continue;
                }
            }
            if (!$valido) {
                throw new \UnexpectedValueException();
            }
            $evento = json_decode($corpoBruto, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            if (!$evento instanceof \stdClass || !is_string($evento->type ?? null) || !is_string($evento->created_at ?? null) || !property_exists($evento, 'data')) {
                throw new \UnexpectedValueException();
            }
            return $evento;
        } catch (\JsonException|\UnexpectedValueException|\TypeError|\ValueError) {
            throw new ErroAssinatura('Não foi possível verificar a autenticidade do webhook.', 'assinatura_invalida');
        }
    }

    private static function decodificar(#[\SensitiveParameter] string $valor): string
    {
        $bytes = base64_decode($valor, true);
        if ($bytes === false || base64_encode($bytes) !== $valor) {
            throw new \UnexpectedValueException();
        }
        return $bytes;
    }
}

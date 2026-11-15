<?php

declare(strict_types=1);

namespace Mailcore\Interno;

use Mailcore\Excecoes\ErroConfiguracao;

/** @internal */
final class CodificacaoJson
{
    /** @param array<array-key, mixed>|\stdClass $corpo */
    public static function codificar(#[\SensitiveParameter] array|\stdClass $corpo): string
    {
        try {
            $json = json_encode(self::normalizar($corpo), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION, 128);
        } catch (\JsonException) {
            throw new ErroConfiguracao('O corpo deve usar JSON e texto UTF-8 válidos.');
        }
        if (strlen($json) > 50 * 1024 * 1024) {
            throw new ErroConfiguracao('O corpo JSON ultrapassa 50 MiB.');
        }
        return $json;
    }

    private static function normalizar(#[\SensitiveParameter] mixed $valor, int $profundidade = 0): mixed
    {
        if ($profundidade > 100) {
            throw new ErroConfiguracao('O corpo JSON excede a profundidade permitida.');
        }
        if ($valor instanceof \stdClass) {
            $objeto = new \stdClass();
            foreach (get_object_vars($valor) as $nome => $item) {
                $objeto->{(string) $nome} = self::normalizar($item, $profundidade + 1);
            }
            return $objeto;
        }
        if (is_array($valor)) {
            $resultado = [];
            foreach ($valor as $nome => $item) {
                $resultado[$nome] = self::normalizar($item, $profundidade + 1);
            }
            return $resultado;
        }
        if (is_float($valor) && !is_finite($valor)) {
            throw new ErroConfiguracao('Números JSON devem ser finitos.');
        }
        if ($valor !== null && !is_scalar($valor)) {
            throw new ErroConfiguracao('O corpo deve conter somente valores JSON ou stdClass.');
        }
        return $valor;
    }
}

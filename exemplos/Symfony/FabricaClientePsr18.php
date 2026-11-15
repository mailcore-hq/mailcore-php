<?php

declare(strict_types=1);

namespace Mailcore\Exemplos\Symfony;

use Closure;
use InvalidArgumentException;
use Mailcore\Cliente;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FabricaClientePsr18
{
    /** @param array<string, mixed> $configuracao */
    public static function criar(
        #[\SensitiveParameter] string|Closure|null $chaveApi,
        #[\SensitiveParameter] array $configuracao = [],
        #[\SensitiveParameter] ?HttpClientInterface $clienteHttp = null,
    ): Cliente {
        $tempo = $configuracao['tempoLimite'] ?? 30;
        if ((!is_int($tempo) && !is_float($tempo)) || !is_finite((float) $tempo) || $tempo <= 0) {
            throw new InvalidArgumentException('Configure um prazo positivo e finito.');
        }
        $opcoes = [
            'timeout' => (float) $tempo,
            'max_duration' => (float) $tempo,
            'max_redirects' => 0,
            'verify_peer' => true,
            'verify_host' => true,
            'proxy' => '',
            'no_proxy' => '*',
            'buffer' => false,
        ];
        if (isset($configuracao['certificadoCa'])) {
            $opcoes['cafile'] = $configuracao['certificadoCa'];
        }
        $http = ($clienteHttp ?? HttpClient::create())->withOptions($opcoes);
        $fabrica = new Psr17Factory();

        return new Cliente($chaveApi, [
            ...$configuracao,
            'clienteHttp' => new Psr18Client($http, $fabrica, $fabrica),
            'fabricaRequisicoes' => $fabrica,
            'fabricaFluxos' => $fabrica,
        ]);
    }
}

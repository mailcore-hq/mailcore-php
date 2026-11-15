<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Resposta;
use Mailcore\TransporteInterface;

abstract class RecursoBase
{
    public function __construct(protected readonly TransporteInterface $transporte)
    {
    }

    /**
     * @param array<array-key, mixed>|\stdClass|null $corpo
     * @param array<string, mixed> $consulta
     * @param array<string, mixed> $opcoes
     * @param (callable(string): void)|null $consumidor
     */
    protected function executar(
        string $metodo,
        #[\SensitiveParameter] string $caminho,
        #[\SensitiveParameter] array|\stdClass|null $corpo = null,
        #[\SensitiveParameter] array $consulta = [],
        string $formato = 'json',
        bool $aceitarVazio = false,
        #[\SensitiveParameter] array $opcoes = [],
        #[\SensitiveParameter] ?callable $consumidor = null,
    ): Resposta {
        return $this->transporte->executar($metodo, $caminho, $corpo, $consulta, $formato, $aceitarVazio, $opcoes, $consumidor);
    }

    protected function segmento(#[\SensitiveParameter] string $valor): string
    {
        if ($valor === '' || $valor === '.' || $valor === '..' || preg_match('/[\x00-\x1f\x7f]/', $valor) || preg_match('//u', $valor) !== 1) {
            throw new ErroConfiguracao('Informe um identificador UTF-8 não vazio e sem controles.');
        }
        return rawurlencode($valor);
    }
}

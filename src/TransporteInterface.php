<?php

declare(strict_types=1);

namespace Mailcore;

interface TransporteInterface
{
    /**
     * @param array<array-key, mixed>|\stdClass|null $corpo
     * @param array<string, mixed> $consulta
     * @param array<string, mixed> $opcoes
     * @param (callable(string): void)|null $consumidor
     */
    public function executar(
        string $metodo,
        string $caminho,
        array|\stdClass|null $corpo = null,
        array $consulta = [],
        string $formato = 'json',
        bool $aceitarVazio = false,
        array $opcoes = [],
        ?callable $consumidor = null,
    ): Resposta;
}

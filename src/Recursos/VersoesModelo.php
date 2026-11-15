<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

// Fonte estática: manutenção em scripts/gerar_recursos.py.
use Mailcore\Resposta;
use stdClass;

/**
 * @phpstan-type OpcoesRequisicao array{
 *     chaveIdempotencia?: string,
 *     tempoLimite?: int|float,
 *     limiteRespostaBytes?: int,
 *     cabecalhos?: array<string, string>
 * }
 */
final class VersoesModelo extends RecursoBase
{
    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $modeloId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/templates/' . $this->segmento($modeloId) . '/versions',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $modeloId,
        #[\SensitiveParameter] string $versaoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/templates/' . $this->segmento($modeloId) . '/versions/' . $this->segmento($versaoId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function restaurar(
        #[\SensitiveParameter] string $modeloId,
        #[\SensitiveParameter] string $versaoId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($modeloId) . '/versions/' . $this->segmento($versaoId) . '/restore',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass|null $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function prever(
        #[\SensitiveParameter] string $modeloId,
        #[\SensitiveParameter] string $versaoId,
        #[\SensitiveParameter] array|stdClass|null $pedido = null,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($modeloId) . '/versions/' . $this->segmento($versaoId) . '/preview',
            corpo: ($pedido === null ? new stdClass() : (object) $pedido),
            opcoes: $opcoes,
        );
    }
}

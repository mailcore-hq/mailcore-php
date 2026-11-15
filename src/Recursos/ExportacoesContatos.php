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
final class ExportacoesContatos extends RecursoBase
{
    /**
     * @param array<string, mixed>|stdClass|null $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function criar(
        #[\SensitiveParameter] array|stdClass|null $filtros = null,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/contacts/exports',
            corpo: ($filtros === null ? new stdClass() : (object) $filtros),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/exports',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/exports/' . $this->segmento($id),
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function baixar(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/exports/' . $this->segmento($id) . '/download',
            formato: 'bytes',
            opcoes: $opcoes,
        );
    }

    /**
     * @param callable(string): void $consumir
     * @param OpcoesRequisicao $opcoes
     */
    public function baixarFluxo(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] callable $consumir,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/exports/' . $this->segmento($id) . '/download',
            formato: 'fluxo',
            opcoes: $opcoes,
            consumidor: $consumir,
        );
    }
}

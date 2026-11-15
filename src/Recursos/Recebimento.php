<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

// Fonte estática: manutenção em scripts/gerar_recursos.py.
use Mailcore\Resposta;

/**
 * @phpstan-type OpcoesRequisicao array{
 *     chaveIdempotencia?: string,
 *     tempoLimite?: int|float,
 *     limiteRespostaBytes?: int,
 *     cabecalhos?: array<string, string>
 * }
 */
final class Recebimento extends RecursoBase
{
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
            '/receiving',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id),
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function listarAnexos(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/attachments',
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obterAnexo(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] string $anexoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/attachments/' . $this->segmento($anexoId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function baixarOriginal(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/raw',
            formato: 'bytes',
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function baixarAnexo(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] string $anexoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/attachments/' . $this->segmento($anexoId) . '/download',
            formato: 'bytes',
            opcoes: $opcoes,
        );
    }

    /**
     * @param callable(string): void $consumir
     * @param OpcoesRequisicao $opcoes
     */
    public function baixarOriginalFluxo(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] callable $consumir,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/raw',
            formato: 'fluxo',
            opcoes: $opcoes,
            consumidor: $consumir,
        );
    }

    /**
     * @param callable(string): void $consumir
     * @param OpcoesRequisicao $opcoes
     */
    public function baixarAnexoFluxo(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] string $anexoId,
        #[\SensitiveParameter] callable $consumir,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/receiving/' . $this->segmento($id) . '/attachments/' . $this->segmento($anexoId) . '/download',
            formato: 'fluxo',
            opcoes: $opcoes,
            consumidor: $consumir,
        );
    }
}

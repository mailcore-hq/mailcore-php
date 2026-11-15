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
final class EtiquetasCaixa extends RecursoBase
{
    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/labels',
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $etiquetaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/labels/' . $this->segmento($etiquetaId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function criar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/labels',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function atualizar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $etiquetaId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PATCH',
            '/inboxes/' . $this->segmento($caixaId) . '/labels/' . $this->segmento($etiquetaId),
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $etiquetaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/inboxes/' . $this->segmento($caixaId) . '/labels/' . $this->segmento($etiquetaId),
            opcoes: $opcoes,
        );
    }
}

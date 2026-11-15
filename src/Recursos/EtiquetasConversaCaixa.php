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
final class EtiquetasConversaCaixa extends RecursoBase
{
    /**
     * @param list<string> $ids
     * @param OpcoesRequisicao $opcoes
     */
    public function adicionar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] array $ids,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/labels',
            corpo: (object) ['ids' => $ids],
            opcoes: $opcoes,
        );
    }

    /**
     * @param list<string> $ids
     * @param OpcoesRequisicao $opcoes
     */
    public function substituir(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] array $ids,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PUT',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/labels',
            corpo: (object) ['ids' => $ids],
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] string $etiquetaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/labels/' . $this->segmento($etiquetaId),
            opcoes: $opcoes,
        );
    }
}

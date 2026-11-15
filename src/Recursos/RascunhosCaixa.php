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
final class RascunhosCaixa extends RecursoBase
{
    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $rascunhoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts/' . $this->segmento($rascunhoId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass|null $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function criar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] array|stdClass|null $pedido = null,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts',
            corpo: ($pedido === null ? new stdClass() : (object) $pedido),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function atualizar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $rascunhoId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PATCH',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts/' . $this->segmento($rascunhoId),
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $rascunhoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts/' . $this->segmento($rascunhoId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function enviar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $rascunhoId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/drafts/' . $this->segmento($rascunhoId) . '/send',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }
}

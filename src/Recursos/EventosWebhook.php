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
final class EventosWebhook extends RecursoBase
{
    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $webhookId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/webhooks/' . $this->segmento($webhookId) . '/events',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $webhookId,
        #[\SensitiveParameter] string $eventoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/webhooks/' . $this->segmento($webhookId) . '/events/' . $this->segmento($eventoId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listarTentativas(
        #[\SensitiveParameter] string $webhookId,
        #[\SensitiveParameter] string $eventoId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/webhooks/' . $this->segmento($webhookId) . '/events/' . $this->segmento($eventoId) . '/attempts',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function reenviar(
        #[\SensitiveParameter] string $webhookId,
        #[\SensitiveParameter] string $eventoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/webhooks/' . $this->segmento($webhookId) . '/events/' . $this->segmento($eventoId) . '/replay',
            opcoes: $opcoes,
        );
    }
}

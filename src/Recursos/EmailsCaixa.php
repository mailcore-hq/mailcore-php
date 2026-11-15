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
final class EmailsCaixa extends RecursoBase
{
    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/emails',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] string $emailId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/emails/' . $this->segmento($emailId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function responder(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] string $emailId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/emails/' . $this->segmento($emailId) . '/reply',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function encaminhar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] string $emailId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId) . '/emails/' . $this->segmento($emailId) . '/forward',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }
}

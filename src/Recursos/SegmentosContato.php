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
final class SegmentosContato extends RecursoBase
{
    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function listar(
        #[\SensitiveParameter] string $contatoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/' . $this->segmento($contatoId) . '/segments',
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function adicionar(
        #[\SensitiveParameter] string $contatoId,
        #[\SensitiveParameter] string $segmentoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/contacts/' . $this->segmento($contatoId) . '/segments',
            corpo: (object) ['segmento_id' => $segmentoId],
            aceitarVazio: true,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $contatoId,
        #[\SensitiveParameter] string $segmentoId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/contacts/' . $this->segmento($contatoId) . '/segments/' . $this->segmento($segmentoId),
            opcoes: $opcoes,
        );
    }
}

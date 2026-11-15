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
final class TopicosContato extends RecursoBase
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
            '/contacts/' . $this->segmento($contatoId) . '/topics',
            opcoes: $opcoes,
        );
    }

    /**
     * @param list<array<string, mixed>|stdClass> $topicos
     * @param OpcoesRequisicao $opcoes
     */
    public function atualizar(
        #[\SensitiveParameter] string $contatoId,
        #[\SensitiveParameter] array $topicos,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PATCH',
            '/contacts/' . $this->segmento($contatoId) . '/topics',
            corpo: (object) ['topicos' => $topicos],
            opcoes: $opcoes,
        );
    }
}

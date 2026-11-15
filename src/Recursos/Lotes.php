<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

// Fonte estática: manutenção em scripts/gerar_recursos.py.
use Mailcore\Excecoes\ErroConfiguracao;
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
final class Lotes extends RecursoBase
{
    /**
     * @param list<array<string, mixed>|stdClass> $dados
     * @param OpcoesRequisicao $opcoes
     */
    public function enviar(
        #[\SensitiveParameter] array $dados,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        if (!array_is_list($dados)) {
            throw new ErroConfiguracao('Um lote deve ser uma lista de mensagens.');
        }

        return $this->executar(
            'POST',
            '/emails/batch',
            corpo: $dados,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obterPorIdempotencia(
        #[\SensitiveParameter] string $chave,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/emails/batch/idempotency/' . $this->segmento($chave),
            opcoes: $opcoes,
        );
    }
}

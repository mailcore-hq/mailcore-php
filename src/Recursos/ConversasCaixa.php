<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

// Fonte estática: manutenção em scripts/gerar_recursos.py.
use Mailcore\Resposta;
use Mailcore\TransporteInterface;
use stdClass;

/**
 * @phpstan-type OpcoesRequisicao array{
 *     chaveIdempotencia?: string,
 *     tempoLimite?: int|float,
 *     limiteRespostaBytes?: int,
 *     cabecalhos?: array<string, string>
 * }
 */
final class ConversasCaixa extends RecursoBase
{
    public readonly EmailsCaixa $emails;
    public readonly EtiquetasConversaCaixa $etiquetas;

    public function __construct(TransporteInterface $transporte)
    {
        parent::__construct($transporte);
        $this->emails = new EmailsCaixa($transporte);
        $this->etiquetas = new EtiquetasConversaCaixa($transporte);
    }

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
            '/inboxes/' . $this->segmento($caixaId) . '/threads',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, scalar|null> $filtros
     * @param OpcoesRequisicao $opcoes
     */
    public function buscar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] array $filtros = [],
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/search',
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
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function atualizar(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PATCH',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId),
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $caixaId,
        #[\SensitiveParameter] string $conversaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/inboxes/' . $this->segmento($caixaId) . '/threads/' . $this->segmento($conversaId),
            opcoes: $opcoes,
        );
    }
}

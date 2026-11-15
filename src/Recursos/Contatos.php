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
final class Contatos extends RecursoBase
{
    public readonly SegmentosContato $segmentos;
    public readonly TopicosContato $topicos;
    public readonly ImportacoesContatos $importacoes;
    public readonly ExportacoesContatos $exportacoes;

    public function __construct(TransporteInterface $transporte)
    {
        parent::__construct($transporte);
        $this->segmentos = new SegmentosContato($transporte);
        $this->topicos = new TopicosContato($transporte);
        $this->importacoes = new ImportacoesContatos($transporte);
        $this->exportacoes = new ExportacoesContatos($transporte);
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function criar(
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/contacts',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

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
            '/contacts',
            consulta: $filtros,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obter(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/contacts/' . $this->segmento($id),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function atualizar(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'PATCH',
            '/contacts/' . $this->segmento($id),
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function remover(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'DELETE',
            '/contacts/' . $this->segmento($id),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function adicionarEmLote(
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/contacts/batch',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function alterarEmLote(
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/contacts/bulk',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }
}

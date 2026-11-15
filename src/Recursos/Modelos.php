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
final class Modelos extends RecursoBase
{
    public readonly VersoesModelo $versoes;
    public readonly PastasModelo $pastas;

    public function __construct(TransporteInterface $transporte)
    {
        parent::__construct($transporte);
        $this->versoes = new VersoesModelo($transporte);
        $this->pastas = new PastasModelo($transporte);
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
            '/templates',
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
            '/templates',
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
            '/templates/' . $this->segmento($id),
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
            '/templates/' . $this->segmento($id),
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
            '/templates/' . $this->segmento($id),
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function prever(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($id) . '/preview',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param OpcoesRequisicao $opcoes
     */
    public function obterPrevia(
        #[\SensitiveParameter] string $organizacaoId,
        #[\SensitiveParameter] string $previaId,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'GET',
            '/template-previews/' . $this->segmento($organizacaoId) . '/' . $this->segmento($previaId),
            formato: 'texto',
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function testar(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($id) . '/test',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function publicar(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array|stdClass $pedido,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($id) . '/publish',
            corpo: (object) $pedido,
            opcoes: $opcoes,
        );
    }

    /**
     * @param array<string, mixed>|stdClass|null $pedido
     * @param OpcoesRequisicao $opcoes
     */
    public function duplicar(
        #[\SensitiveParameter] string $id,
        #[\SensitiveParameter] array|stdClass|null $pedido = null,
        #[\SensitiveParameter] array $opcoes = [],
    ): Resposta {
        return $this->executar(
            'POST',
            '/templates/' . $this->segmento($id) . '/duplicate',
            corpo: ($pedido === null ? new stdClass() : (object) $pedido),
            opcoes: $opcoes,
        );
    }
}

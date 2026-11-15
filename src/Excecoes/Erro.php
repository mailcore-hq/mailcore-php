<?php

declare(strict_types=1);

namespace Mailcore\Excecoes;

class Erro extends \RuntimeException
{
    /** @param array<string, string> $cabecalhos */
    public function __construct(
        #[\SensitiveParameter] string $mensagem = 'Não foi possível concluir a operação.',
        public readonly string $codigo = 'erro_mailcore',
        public readonly ?int $status = null,
        #[\SensitiveParameter] public readonly array $cabecalhos = [],
    ) {
        parent::__construct($mensagem);
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return ['codigo' => $this->codigo, 'status' => $this->status];
    }
}

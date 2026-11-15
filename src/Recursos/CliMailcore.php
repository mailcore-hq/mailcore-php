<?php

declare(strict_types=1);

namespace Mailcore\Recursos;

// Fonte estática: manutenção em scripts/gerar_recursos.py.
use Mailcore\TransporteInterface;

final class CliMailcore extends RecursoBase
{
    public readonly ConcessoesCli $concessoes;

    public function __construct(TransporteInterface $transporte)
    {
        parent::__construct($transporte);
        $this->concessoes = new ConcessoesCli($transporte);
    }
}

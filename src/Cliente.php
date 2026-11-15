<?php

declare(strict_types=1);

namespace Mailcore;

final class Cliente
{
    private readonly Transporte $transporte;
    public readonly Recursos\Emails $emails;
    public readonly Recursos\Lotes $lotes;
    public readonly Recursos\Dominios $dominios;
    public readonly Recursos\ChavesApi $chavesApi;
    public readonly Recursos\Contatos $contatos;
    public readonly Recursos\Segmentos $segmentos;
    public readonly Recursos\Topicos $topicos;
    public readonly Recursos\PropriedadesContatos $propriedadesContatos;
    public readonly Recursos\Modelos $modelos;
    public readonly Recursos\Webhooks $webhooks;
    public readonly Recursos\Campanhas $campanhas;
    public readonly Recursos\Automacoes $automacoes;
    public readonly Recursos\Eventos $eventos;
    public readonly Recursos\Recebimento $recebimento;
    public readonly Recursos\Caixas $caixas;
    public readonly Recursos\CliMailcore $cli;

    /** @param array<string, mixed> $configuracao */
    public function __construct(#[\SensitiveParameter] string|\Closure|null $chaveApi = null, #[\SensitiveParameter] array $configuracao = [])
    {
        $this->transporte = new Transporte($chaveApi, $configuracao);
        $this->emails = new Recursos\Emails($this->transporte);
        $this->lotes = new Recursos\Lotes($this->transporte);
        $this->dominios = new Recursos\Dominios($this->transporte);
        $this->chavesApi = new Recursos\ChavesApi($this->transporte);
        $this->contatos = new Recursos\Contatos($this->transporte);
        $this->segmentos = new Recursos\Segmentos($this->transporte);
        $this->topicos = new Recursos\Topicos($this->transporte);
        $this->propriedadesContatos = new Recursos\PropriedadesContatos($this->transporte);
        $this->modelos = new Recursos\Modelos($this->transporte);
        $this->webhooks = new Recursos\Webhooks($this->transporte);
        $this->campanhas = new Recursos\Campanhas($this->transporte);
        $this->automacoes = new Recursos\Automacoes($this->transporte);
        $this->eventos = new Recursos\Eventos($this->transporte);
        $this->recebimento = new Recursos\Recebimento($this->transporte);
        $this->caixas = new Recursos\Caixas($this->transporte);
        $this->cli = new Recursos\CliMailcore($this->transporte);
    }

    public function fechar(): void
    {
        $this->transporte->fechar();
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['credenciais' => 'protegidas'];
    }
}

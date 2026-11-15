<?php

declare(strict_types=1);

namespace Mailcore\Interno;

use Mailcore\Excecoes\ErroApi;
use Mailcore\Excecoes\ErroResposta;
use Mailcore\Excecoes\ErroTransporte;
use Mailcore\Resposta;

/** @internal */
final class LeituraResposta
{
    private int $status = 0;
    /** @var array<string, string> */
    private array $cabecalhos = [];
    private string $corpo = '';
    private int $total = 0;
    private readonly ?\Closure $consumidor;

    /** @param (callable(string): void)|null $consumidor */
    public function __construct(private readonly string $formato, private readonly int $limite, #[\SensitiveParameter] ?callable $consumidor)
    {
        $this->consumidor = $consumidor === null ? null : \Closure::fromCallable($consumidor);
    }

    /** @param array<string, string> $cabecalhos */
    public function iniciar(int $status, #[\SensitiveParameter] array $cabecalhos): void
    {
        $this->status = $status;
        $this->cabecalhos = array_diff_key($cabecalhos, array_flip(['set-cookie', 'authorization', 'proxy-authorization']));
        if ($status >= 300 && $status <= 399) {
            throw new ErroApi('A API respondeu com um redirecionamento recusado.', 'redirecionamento_recusado', $status, $this->cabecalhos);
        }
        if (!in_array(strtolower(trim($cabecalhos['content-encoding'] ?? 'identity')), ['', 'identity'], true)) {
            throw new ErroResposta('A API respondeu com uma codificação não negociada.', 'codificacao_inesperada', $status, $this->cabecalhos);
        }
        if (array_key_exists('content-length', $cabecalhos)) {
            if (!preg_match('/\A[0-9]+\z/D', $cabecalhos['content-length'])) {
                throw new ErroResposta('Tamanho HTTP inválido.', 'resposta_invalida', $status, $this->cabecalhos);
            }
            if ((float) $cabecalhos['content-length'] > $this->limite) {
                $this->excedida();
            }
        }
    }

    public function receber(#[\SensitiveParameter] string $parte): void
    {
        $this->total += strlen($parte);
        if ($this->total > $this->limite) {
            $this->excedida();
        }
        if ($this->status >= 200 && $this->status <= 299 && $this->formato === 'fluxo' && $this->consumidor !== null) {
            if ($parte !== '') {
                ($this->consumidor)($parte);
            }
        } else {
            $this->corpo .= $parte;
        }
    }

    public function concluir(bool $aceitarVazio): Resposta
    {
        if (isset($this->cabecalhos['content-length']) && (int) $this->cabecalhos['content-length'] !== $this->total) {
            throw new ErroTransporte('A resposta terminou antes do tamanho anunciado.', 'resposta_incompleta', $this->status, $this->cabecalhos);
        }
        if ($this->status < 200 || $this->status > 299) {
            try {
                $envelope = json_decode($this->corpo, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
                $erro = $envelope instanceof \stdClass ? ($envelope->erro ?? null) : null;
                if ($erro instanceof \stdClass && is_string($erro->codigo ?? null) && is_string($erro->mensagem ?? null)) {
                    throw new ErroApi($erro->mensagem, $erro->codigo, $this->status, $this->cabecalhos);
                }
            } catch (\JsonException) {
                // Proxies podem responder HTML; esse corpo não deve entrar na exceção.
            }
            throw new ErroApi('A API retornou erro HTTP sem detalhes JSON válidos.', 'erro_http', $this->status, $this->cabecalhos);
        }
        if ($this->formato === 'fluxo') {
            $dados = $this->total;
        } elseif ($this->formato === 'bytes' || $this->formato === 'texto') {
            $dados = $this->corpo;
            if ($this->formato === 'texto' && preg_match('//u', $dados) !== 1) {
                throw new ErroResposta('A API retornou texto UTF-8 inválido.', 'resposta_invalida', $this->status, $this->cabecalhos);
            }
        } elseif ($this->corpo === '' && ($this->status === 204 || $aceitarVazio)) {
            $dados = null;
        } else {
            try {
                $dados = json_decode($this->corpo, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            } catch (\JsonException) {
                throw new ErroResposta('A API retornou JSON inválido.', 'resposta_invalida', $this->status, $this->cabecalhos);
            }
        }
        return new Resposta($dados, $this->status, $this->cabecalhos);
    }

    private function excedida(): never
    {
        throw new ErroResposta('A resposta excedeu o limite de bytes configurado.', 'resposta_excedida', $this->status, $this->cabecalhos);
    }

    /** @return array<string, int> */
    public function __debugInfo(): array
    {
        return ['status' => $this->status, 'bytes' => $this->total];
    }
}

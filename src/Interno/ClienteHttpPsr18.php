<?php

declare(strict_types=1);

namespace Mailcore\Interno;

use Mailcore\Excecoes\ErroTransporte;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** @internal O provedor PSR-18 controla sua política de conexão, prazos e buffering. */
final class ClienteHttpPsr18
{
    public function __construct(
        private readonly ClientInterface $cliente,
        private readonly RequestFactoryInterface $requisicoes,
        private readonly StreamFactoryInterface $fluxos,
    ) {
    }

    /** @param array<string, string> $cabecalhos */
    public function enviar(string $metodo, #[\SensitiveParameter] string $url, #[\SensitiveParameter] array $cabecalhos, #[\SensitiveParameter] ?string $corpo, #[\SensitiveParameter] LeituraResposta $leitura): void
    {
        try {
            $pedido = $this->requisicoes->createRequest($metodo, $url);
            foreach ($cabecalhos as $nome => $valor) {
                $pedido = $pedido->withHeader($nome, $valor);
            }
            if ($corpo !== null) {
                $pedido = $pedido->withBody($this->fluxos->createStream($corpo));
            }
            $resposta = $this->cliente->sendRequest($pedido);
        } catch (\Throwable) {
            throw new ErroTransporte('Não foi possível concluir a comunicação com o provedor PSR-18.', 'falha_de_rede');
        }
        try {
            $fluxo = $resposta->getBody();
        } catch (\Throwable) {
            throw new ErroTransporte('Não foi possível abrir a resposta PSR-7.', 'resposta_incompleta');
        }
        $falhou = false;
        $status = null;
        try {
            try {
                $recebidos = [];
                foreach ($resposta->getHeaders() as $nome => $valores) {
                    $recebidos[strtolower($nome)] = implode(', ', $valores);
                }
                $status = $resposta->getStatusCode();
            } catch (\Throwable) {
                throw new ErroTransporte('Não foi possível abrir a resposta PSR-7.', 'resposta_incompleta');
            }
            $leitura->iniciar($status, $recebidos);
            try {
                if ($fluxo->isSeekable()) {
                    $fluxo->rewind();
                }
            } catch (\Throwable) {
                throw new ErroTransporte('Não foi possível posicionar a resposta PSR-7.', 'resposta_incompleta', $status);
            }
            while (true) {
                try {
                    $parte = $fluxo->read(8192);
                    $finalizado = $parte === '' && $fluxo->eof();
                } catch (\Throwable) {
                    throw new ErroTransporte('Não foi possível ler a resposta PSR-7.', 'resposta_incompleta', $status);
                }
                if ($parte === '') {
                    if (!$finalizado) {
                        throw new ErroTransporte('O fluxo PSR-7 não avançou.', 'fluxo_sem_progresso', $status);
                    }
                    break;
                }
                $leitura->receber($parte);
            }
        } catch (\Throwable $erro) {
            $falhou = true;
            throw $erro;
        } finally {
            try {
                $fluxo->close();
            } catch (\Throwable) {
                if (!$falhou) {
                    throw new ErroTransporte('Não foi possível encerrar a resposta PSR-7.', 'falha_de_rede', $status);
                }
            }
        }
    }
}

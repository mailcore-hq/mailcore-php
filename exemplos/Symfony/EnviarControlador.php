<?php

declare(strict_types=1);

namespace Mailcore\Exemplos\Symfony;

use Mailcore\Excecoes\Erro;
use Mailcore\Excecoes\ErroApi;
use Mailcore\Excecoes\ErroConfiguracao;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class EnviarControlador
{
    public function __construct(#[\SensitiveParameter] private readonly ServicoEnvio $servico)
    {
    }

    #[Route('/enviar', name: 'mailcore_enviar', methods: ['POST'])]
    public function __invoke(#[\SensitiveParameter] Request $requisicao): JsonResponse
    {
        try {
            if ($requisicao->getMethod() !== 'POST') {
                throw new HttpException(405, 'Use POST para enviar.');
            }
            $chave = $this->servico->validarCabecalhos(
                $requisicao->headers->all('authorization'),
                $requisicao->headers->all('idempotency-key'),
                $requisicao->headers->get('content-type', ''),
            );
            $tamanho = $requisicao->headers->get('content-length', '');
            if (ctype_digit($tamanho) && (strlen($tamanho) > 8 || (int) $tamanho > ServicoEnvio::LIMITE_CORPO)) {
                throw new HttpException(413, 'O corpo da requisição excede o limite.');
            }
            $entrada = $requisicao->getContent(true);
            $corpo = is_resource($entrada) ? stream_get_contents($entrada, ServicoEnvio::LIMITE_CORPO + 1) : false;
            if ($corpo === false) {
                throw new HttpException(400, 'Não foi possível ler a requisição.');
            }

            return $this->responder($this->servico->enviar($corpo, $chave), 202);
        } catch (HttpExceptionInterface $erro) {
            return $this->responder(['erro' => 'Não foi possível aceitar a requisição.'], $erro->getStatusCode());
        } catch (ErroApi $erro) {
            $status = $erro->status !== null && $erro->status >= 400 && $erro->status <= 599 ? $erro->status : 502;

            return $this->responder(['erro' => 'Não foi possível aceitar a mensagem.'], $status);
        } catch (Erro|ErroConfiguracao) {
            return $this->responder(['erro' => 'Não foi possível confirmar a aceitação da mensagem.'], 502);
        }
    }

    /** @param array<string, string> $dados */
    private function responder(#[\SensitiveParameter] array $dados, int $status): JsonResponse
    {
        return new JsonResponse($dados, $status, ['Cache-Control' => 'no-store']);
    }
}

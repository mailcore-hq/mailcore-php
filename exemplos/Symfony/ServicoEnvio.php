<?php

declare(strict_types=1);

namespace Mailcore\Exemplos\Symfony;

use InvalidArgumentException;
use JsonException;
use Mailcore\Cliente;
use stdClass;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ServicoEnvio
{
    public const LIMITE_CORPO = 4096;

    private readonly string $resumoToken;
    private readonly string $remetente;
    private readonly string $destinatario;

    /** @param array<string, mixed> $ambiente */
    public function __construct(
        #[\SensitiveParameter] private readonly Cliente $cliente,
        #[\SensitiveParameter] array $ambiente,
    ) {
        $token = $ambiente['TOKEN_APLICACAO'] ?? null;
        $chave = $ambiente['MAILCORE_API_KEY'] ?? null;
        $remetente = $ambiente['MAILCORE_REMETENTE'] ?? null;
        $destinatario = $ambiente['MAILCORE_DESTINATARIO'] ?? null;
        if (!is_string($token) || preg_match('/\A[A-Za-z0-9_-]{43,256}\z/D', $token) !== 1
            || !is_string($chave) || preg_match('/\A[\x21-\x7e]{1,512}\z/D', $chave) !== 1
            || !$this->enderecoConfigurado($remetente) || !$this->enderecoConfigurado($destinatario)) {
            throw new InvalidArgumentException('Configure as credenciais e os endereços fixos da aplicação.');
        }
        $resumo = hash('sha256', $token, true);
        if (hash_equals($resumo, hash('sha256', $chave, true))) {
            throw new InvalidArgumentException('Use credenciais diferentes para a aplicação e a API.');
        }
        $this->resumoToken = $resumo;
        $this->remetente = $remetente;
        $this->destinatario = $destinatario;
    }

    /** @param list<string> $autorizacoes @param list<string> $idempotencias */
    public function validarCabecalhos(
        #[\SensitiveParameter] array $autorizacoes,
        #[\SensitiveParameter] array $idempotencias,
        string $tipo,
    ): string {
        $autorizacao = count($autorizacoes) === 1 ? $autorizacoes[0] : '';
        if (preg_match('/\ABearer ([A-Za-z0-9_-]{43,256})\z/D', $autorizacao, $partes) !== 1
            || !hash_equals($this->resumoToken, hash('sha256', $partes[1], true))) {
            throw new HttpException(401, 'Acesso não autorizado.');
        }
        $chave = count($idempotencias) === 1 ? $idempotencias[0] : '';
        if (preg_match('/\A[\x21-\x7e]{1,256}\z/D', $chave) !== 1 || str_contains($chave, ',')) {
            throw new HttpException(400, 'Informe uma chave de idempotência válida.');
        }
        if (strtolower(trim(explode(';', $tipo, 2)[0])) !== 'application/json') {
            throw new HttpException(415, 'Envie um objeto JSON.');
        }

        return $chave;
    }

    /** @return array{id: string, estado: string} */
    public function enviar(
        #[\SensitiveParameter] string $corpo,
        #[\SensitiveParameter] string $chaveIdempotencia,
    ): array {
        if (strlen($corpo) > self::LIMITE_CORPO) {
            throw new HttpException(413, 'O corpo da requisição excede o limite.');
        }
        try {
            $objeto = json_decode($corpo, false, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'Envie um objeto JSON válido em UTF-8.');
        }
        if (!$objeto instanceof stdClass) {
            throw new HttpException(400, 'Envie um objeto JSON.');
        }
        $resposta = $this->cliente->emails->enviar([
            'remetente' => $this->remetente,
            'destinatarios' => [$this->destinatario],
            'assunto' => 'Mensagem de exemplo',
            'texto' => 'Integração Mailcore com Symfony.',
        ], ['chaveIdempotencia' => $chaveIdempotencia]);
        $dados = $resposta->dados;
        if ($resposta->status !== 202 || !$dados instanceof stdClass
            || !isset($dados->id, $dados->estado) || !is_string($dados->id) || $dados->id === ''
            || !is_string($dados->estado) || $dados->estado === '') {
            throw new HttpException(502, 'Resposta de aceitação inválida.');
        }

        return ['id' => $dados->id, 'estado' => $dados->estado];
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['configuracao' => 'protegida'];
    }

    private function enderecoConfigurado(#[\SensitiveParameter] mixed $valor): bool
    {
        return is_string($valor) && trim($valor) === $valor
            && preg_match('/\A[^\x00-\x1f\x7f]{1,320}\z/uD', $valor) === 1;
    }
}

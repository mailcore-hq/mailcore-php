<?php

declare(strict_types=1);

namespace Mailcore\Interno;

use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Excecoes\ErroResposta;
use Mailcore\Excecoes\ErroTransporte;

/** @internal Reutiliza conexões cURL e mantém cada resposta somente em memória. */
final class ClienteHttpCurl
{
    private ?\CurlHandle $handle = null;
    private bool $emUso = false;
    private bool $fechado = false;

    /** @param array<string, string> $cabecalhos */
    public function enviar(string $metodo, #[\SensitiveParameter] string $url, #[\SensitiveParameter] array $cabecalhos, #[\SensitiveParameter] ?string $corpo, float $tempo, float $conexao, ?string $ca, #[\SensitiveParameter] LeituraResposta $leitura): void
    {
        if ($metodo === '' || $url === '' || $ca === '') {
            throw new ErroConfiguracao('Método, URL e caminho de CA devem ser válidos.');
        }
        if ($this->fechado || $this->emUso) {
            throw new ErroConfiguracao('O cliente foi encerrado ou já possui uma operação em andamento.');
        }
        if (!extension_loaded('curl')) {
            throw new ErroConfiguracao('Ative ext-curl ou forneça um cliente HTTP PSR-18.');
        }
        $this->emUso = true;
        $erroPendente = null;
        $status = 0;
        $recebidos = [];
        $tamanhoCabecalhos = 0;
        $handle = $this->handle ??= curl_init();
        try {
            curl_reset($handle);
            $opcoes = [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => $metodo,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => false,
                // Uma conexão reutilizada sem resposta pode ser repetida internamente
                // pelo libcurl. Mutações usam conexão nova para evitar esse replay.
                CURLOPT_FRESH_CONNECT => $metodo !== 'GET',
                CURLOPT_FAILONERROR => false,
                CURLOPT_PROXY => '',
                CURLOPT_NOSIGNAL => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_TIMEOUT_MS => (int) ceil($tempo * 1000),
                CURLOPT_CONNECTTIMEOUT_MS => (int) ceil(min($tempo, $conexao) * 1000),
                CURLOPT_HTTP_CONTENT_DECODING => false,
                CURLOPT_HTTPHEADER => array_map(static fn (string $nome, string $valor): string => $nome.': '.$valor, array_keys($cabecalhos), array_values($cabecalhos)),
                CURLOPT_HEADERFUNCTION => static function (\CurlHandle $_handle, #[\SensitiveParameter] string $linha) use (&$status, &$recebidos, &$tamanhoCabecalhos, &$erroPendente, $leitura): int {
                    try {
                        if (preg_match('/\AHTTP\/[0-9.]+ ([0-9]{3})(?: |\r|\n|$)/D', $linha, $partes)) {
                            $status = (int) $partes[1];
                            $recebidos = [];
                            $tamanhoCabecalhos = 0;
                        }
                        $tamanhoCabecalhos += strlen($linha);
                        if ($tamanhoCabecalhos > 65536) {
                            throw new ErroResposta('Cabeçalhos da resposta excederam o limite.', 'resposta_excedida', $status);
                        }
                        if (trim($linha) === '') {
                            if ($status >= 200) {
                                $leitura->iniciar($status, $recebidos);
                            }
                        } elseif (str_contains($linha, ':')) {
                            [$nome, $valor] = explode(':', $linha, 2);
                            $nome = strtolower(trim($nome));
                            $valor = trim($valor);
                            $recebidos[$nome] = isset($recebidos[$nome]) ? $recebidos[$nome].', '.$valor : $valor;
                        }
                        return strlen($linha);
                    } catch (\Throwable $erro) {
                        $erroPendente = $erro;
                        return 0;
                    }
                },
                CURLOPT_WRITEFUNCTION => static function (\CurlHandle $_handle, #[\SensitiveParameter] string $parte) use ($leitura, &$erroPendente): int {
                    try {
                        $leitura->receber($parte);
                        return strlen($parte);
                    } catch (\Throwable $erro) {
                        $erroPendente = $erro;
                        return 0;
                    }
                },
            ];
            if ($corpo !== null) {
                $opcoes[CURLOPT_POSTFIELDS] = $corpo;
            }
            if ($ca !== null) {
                $opcoes[CURLOPT_CAINFO] = $ca;
            }
            curl_setopt_array($handle, $opcoes);
            $sucesso = curl_exec($handle);
            $codigo = curl_errno($handle);
            if ($erroPendente !== null) {
                throw $erroPendente;
            }
            if ($sucesso === false) {
                if ($codigo === CURLE_OPERATION_TIMEDOUT) {
                    throw new ErroTransporte('A transferência excedeu o prazo configurado.', 'prazo_excedido');
                }
                if ($codigo === CURLE_PARTIAL_FILE) {
                    throw new ErroTransporte('A resposta terminou incompleta.', 'resposta_incompleta', $status ?: null);
                }
                throw new ErroTransporte('Não foi possível concluir a comunicação com a API.', 'falha_de_rede', $status ?: null);
            }
            if ($status === 0) {
                throw new ErroTransporte('A API não retornou um status HTTP válido.', 'falha_de_rede');
            }
        } finally {
            // curl_reset preserva o cache de conexões e remove callbacks/credenciais do pedido.
            curl_reset($handle);
            $this->emUso = false;
        }
    }

    public function fechar(): void
    {
        if ($this->emUso) {
            throw new ErroConfiguracao('Encerre o consumo antes de fechar o cliente.');
        }
        $this->handle = null;
        $this->fechado = true;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['credenciais' => 'protegidas'];
    }
}

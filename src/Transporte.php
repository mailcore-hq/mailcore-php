<?php

declare(strict_types=1);

namespace Mailcore;

use Mailcore\Excecoes\ErroConfiguracao;
use Mailcore\Interno\ClienteHttpCurl;
use Mailcore\Interno\ClienteHttpPsr18;
use Mailcore\Interno\CodificacaoJson;
use Mailcore\Interno\LeituraResposta;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** Um pedido por operação, sem camada de repetição automática no SDK. */
final class Transporte implements TransporteInterface
{
    private readonly string $base;
    private readonly float $tempo;
    private readonly float $conexao;
    private readonly int $limite;
    private readonly ?string $ca;
    private readonly ClienteHttpCurl|ClienteHttpPsr18 $http;
    private bool $fechado = false;
    private bool $emUso = false;

    /** @param array<string, mixed> $configuracao */
    public function __construct(
        #[\SensitiveParameter] private readonly string|\Closure|null $chaveApi = null,
        #[\SensitiveParameter] array $configuracao = [],
    ) {
        $permitidas = ['urlBase', 'tempoLimite', 'tempoConexao', 'limiteRespostaBytes', 'certificadoCa', 'permitirHttpLocal', 'clienteHttp', 'fabricaRequisicoes', 'fabricaFluxos'];
        if (array_diff(array_keys($configuracao), $permitidas) !== []) {
            throw new ErroConfiguracao('Opção de configuração desconhecida.');
        }
        if (is_string($chaveApi)) {
            self::validarChave($chaveApi);
        }
        $base = $configuracao['urlBase'] ?? 'https://mailcore.com.br/v1';
        if (!is_string($base) || preg_match('/[\\\\\x00-\x20\x7f]/', $base)) {
            throw new ErroConfiguracao('Use URL base HTTPS válida.');
        }
        $url = parse_url($base);
        if ($url === false || !isset($url['host'], $url['scheme']) || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
            throw new ErroConfiguracao('Use URL base HTTPS sem credenciais, parâmetros ou fragmentos.');
        }
        $local = in_array(strtolower($url['host']), ['localhost', '127.0.0.1', '[::1]'], true);
        if (strtolower($url['scheme']) !== 'https' && !(strtolower($url['scheme']) === 'http' && $local && ($configuracao['permitirHttpLocal'] ?? false) === true)) {
            throw new ErroConfiguracao('HTTPS é obrigatório; HTTP explícito de teste se limita a loopback.');
        }
        $this->base = rtrim($base, '/');
        $this->tempo = self::validarTempo($configuracao['tempoLimite'] ?? 30);
        $this->conexao = self::validarTempo($configuracao['tempoConexao'] ?? 10);
        $this->limite = self::validarLimite($configuracao['limiteRespostaBytes'] ?? 16 * 1024 * 1024);
        $ca = $configuracao['certificadoCa'] ?? null;
        if ($ca !== null && (!is_string($ca) || str_contains($ca, "\0") || !is_file($ca) || !is_readable($ca))) {
            throw new ErroConfiguracao('Informe um arquivo acessível com a autoridade certificadora.');
        }
        $this->ca = $ca;
        $http = $configuracao['clienteHttp'] ?? null;
        $requisicoes = $configuracao['fabricaRequisicoes'] ?? null;
        $fluxos = $configuracao['fabricaFluxos'] ?? null;
        if ($http !== null) {
            if (!$http instanceof ClientInterface || ($requisicoes !== null && !$requisicoes instanceof RequestFactoryInterface) || ($fluxos !== null && !$fluxos instanceof StreamFactoryInterface)) {
                throw new ErroConfiguracao('Forneça cliente PSR-18 e fábricas PSR-17 compatíveis.');
            }
            $fabrica = new Psr17Factory();
            $this->http = new ClienteHttpPsr18($http, $requisicoes ?? $fabrica, $fluxos ?? $fabrica);
        } else {
            if ($requisicoes !== null || $fluxos !== null) {
                throw new ErroConfiguracao('Fábricas PSR-17 exigem um cliente PSR-18.');
            }
            $this->http = new ClienteHttpCurl();
        }
    }

    /**
     * @param array<array-key, mixed>|\stdClass|null $corpo
     * @param array<string, mixed> $consulta
     * @param array<string, mixed> $opcoes
     * @param (callable(string): void)|null $consumidor
     */
    public function executar(
        string $metodo,
        #[\SensitiveParameter] string $caminho,
        #[\SensitiveParameter] array|\stdClass|null $corpo = null,
        #[\SensitiveParameter] array $consulta = [],
        string $formato = 'json',
        bool $aceitarVazio = false,
        #[\SensitiveParameter] array $opcoes = [],
        #[\SensitiveParameter] ?callable $consumidor = null,
    ): Resposta {
        if ($this->fechado || $this->emUso) {
            throw new ErroConfiguracao('O cliente foi encerrado ou já possui uma operação em andamento.');
        }
        if (!in_array($metodo, ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'], true) || !in_array($formato, ['json', 'texto', 'bytes', 'fluxo'], true)) {
            throw new ErroConfiguracao('Método HTTP ou formato de resposta inválido.');
        }
        if ($formato === 'fluxo' && $consumidor === null) {
            throw new ErroConfiguracao('Forneça um consumidor para o download em fluxo.');
        }
        if (!str_starts_with($caminho, '/') || str_starts_with($caminho, '//') || preg_match('/[?#\\\\\x00-\x20]/', $caminho) || array_intersect(explode('/', $caminho), ['.', '..']) !== []) {
            throw new ErroConfiguracao('Caminho de recurso inválido.');
        }
        if (array_diff(array_keys($opcoes), ['tempoLimite', 'limiteRespostaBytes', 'chaveIdempotencia', 'cabecalhos']) !== []) {
            throw new ErroConfiguracao('Opção de requisição desconhecida.');
        }
        $tempo = self::validarTempo($opcoes['tempoLimite'] ?? $this->tempo);
        $limite = self::validarLimite($opcoes['limiteRespostaBytes'] ?? $this->limite);
        $pares = [];
        foreach ($consulta as $nome => $valor) {
            if ($valor === null) {
                continue;
            }
            if (!is_string($nome) || !is_scalar($valor) || (is_float($valor) && !is_finite($valor))) {
                throw new ErroConfiguracao('A consulta deve conter nomes textuais e valores simples.');
            }
            $pares[$nome] = is_bool($valor) ? ($valor ? 'true' : 'false') : (string) $valor;
        }
        $url = $this->base.$caminho.($pares !== [] ? '?'.http_build_query($pares, '', '&', PHP_QUERY_RFC3986) : '');
        $cabecalhos = $opcoes['cabecalhos'] ?? [];
        if (!is_array($cabecalhos)) {
            throw new ErroConfiguracao('Os cabeçalhos devem ser um mapa textual.');
        }
        $reservados = ['authorization', 'proxy-authorization', 'cookie', 'host', 'content-length', 'content-type', 'accept', 'connection', 'transfer-encoding', 'idempotency-key', 'user-agent', 'accept-encoding'];
        foreach ($cabecalhos as $nome => $valor) {
            if (!is_string($nome) || !preg_match('/\A[!#$%&\x27*+.^_`|~0-9A-Za-z-]+\z/D', $nome) || in_array(strtolower($nome), $reservados, true) || !is_string($valor) || !preg_match('/\A[\x20-\x7e]*\z/D', $valor)) {
                throw new ErroConfiguracao('Cabeçalho inválido ou reservado pelo SDK.');
            }
        }
        $cabecalhos['Accept'] = in_array($formato, ['bytes', 'fluxo'], true) ? 'application/octet-stream' : ($formato === 'texto' ? 'text/html' : 'application/json');
        $cabecalhos['Accept-Encoding'] = 'identity';
        $cabecalhos['User-Agent'] = 'mailcore-php/'.Versao::VALOR;
        $cabecalhos['Authorization'] = 'Bearer '.$this->resolverChave();
        if (array_key_exists('chaveIdempotencia', $opcoes)) {
            $idempotencia = $opcoes['chaveIdempotencia'];
            if (!is_string($idempotencia) || !preg_match('/\A[\x21-\x7e]{1,256}\z/D', $idempotencia)) {
                throw new ErroConfiguracao('A chave de idempotência deve ter de 1 a 256 caracteres ASCII visíveis.');
            }
            $cabecalhos['Idempotency-Key'] = $idempotencia;
        }
        $json = $corpo === null ? null : CodificacaoJson::codificar($corpo);
        if ($json !== null) {
            $cabecalhos['Content-Type'] = 'application/json';
        }
        $leitura = new LeituraResposta($formato, $limite, $consumidor);
        $this->emUso = true;
        try {
            if ($this->http instanceof ClienteHttpCurl) {
                $this->http->enviar($metodo, $url, $cabecalhos, $json, $tempo, $this->conexao, $this->ca, $leitura);
            } else {
                $this->http->enviar($metodo, $url, $cabecalhos, $json, $leitura);
            }
            return $leitura->concluir($aceitarVazio);
        } finally {
            $this->emUso = false;
        }
    }

    public function fechar(): void
    {
        if ($this->emUso) {
            throw new ErroConfiguracao('Encerre o consumo antes de fechar o cliente.');
        }
        if ($this->http instanceof ClienteHttpCurl) {
            $this->http->fechar();
        }
        $this->fechado = true;
    }

    private function resolverChave(): string
    {
        try {
            $chave = $this->chaveApi === null ? getenv('MAILCORE_API_KEY') : ($this->chaveApi instanceof \Closure ? ($this->chaveApi)() : $this->chaveApi);
        } catch (\Throwable) {
            throw new ErroConfiguracao('Não foi possível obter uma chave de API válida.');
        }
        return self::validarChave($chave);
    }

    private static function validarChave(#[\SensitiveParameter] mixed $valor): string
    {
        if (!is_string($valor) || !preg_match('/\A[\x21-\x7e]+\z/D', $valor)) {
            throw new ErroConfiguracao('Informe uma chave Mailcore válida ou configure MAILCORE_API_KEY.');
        }
        return $valor;
    }

    private static function validarTempo(#[\SensitiveParameter] mixed $valor): float
    {
        if ((!is_int($valor) && !is_float($valor)) || $valor <= 0 || $valor > 86400 || !is_finite((float) $valor)) {
            throw new ErroConfiguracao('Informe um prazo positivo e finito, em segundos, até 86400.');
        }
        return (float) $valor;
    }

    private static function validarLimite(#[\SensitiveParameter] mixed $valor): int
    {
        if (!is_int($valor) || $valor < 1) {
            throw new ErroConfiguracao('Informe um limite inteiro positivo em bytes.');
        }
        return $valor;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['credenciais' => 'protegidas'];
    }
}

# Mailcore para PHP

[Código-fonte](https://github.com/mailcore-hq/mailcore-php) · [Packagist](https://packagist.org/packages/mailcore/mailcore-php) · [Problemas e sugestões](https://github.com/mailcore-hq/mailcore-php/issues)

Cliente PHP da API Mailcore, com 151 operações, transporte cURL, interoperabilidade PSR-18, downloads por callback e verificação de assinaturas de webhooks. Versão **0.1.0-alpha.1**.

Requer PHP **8.2 ou superior**, Composer e JSON. O transporte padrão requer `ext-curl` com suporte a HTTPS e uma cadeia de autoridades certificadoras confiável. É possível fornecer um cliente PSR-18 em seu lugar. Docker é utilizado no desenvolvimento desta biblioteca; não é necessário para quem a integra a uma aplicação PHP. Symfony é opcional.

## Instalação

A versão `0.1.0-alpha.1` está disponível no [Packagist](https://packagist.org/packages/mailcore/mailcore-php). Instale o pacote na aplicação consumidora:

```sh
composer require mailcore/mailcore-php:0.1.0-alpha.1
```

A instalação pública foi validada com cache vazio, PHP 8.2.35 e Composer, incluindo plataforma, autoload e as 151 operações com transporte PSR-18 sintético. Veja o [registro da publicação](docs/publicacao.md).

## Desenvolvimento e instalação local

Para desenvolver com o checkout local, adicione um repositório Composer `path` na aplicação consumidora, apontando para esta pasta:

```json
{
  "repositories": [
    {"type": "path", "url": "../mailcore-php", "options": {"symlink": false}}
  ],
  "require": {"mailcore/mailcore-php": "0.1.0-alpha.1"}
}
```

Execute `composer install` na aplicação. O caminho é relativo ao `composer.json` dela. Outra opção é extrair `dist/mailcore-php-0.1.0-alpha.1.zip` em `../mailcore-php` e utilizar a mesma configuração. Os scripts também verificam a instalação direta dos ZIPs em uma aplicação separada, sem carregar o checkout nem ferramentas de desenvolvimento.

## Enviar um email

Disponibilize `MAILCORE_API_KEY` pelo gerenciador de segredos da aplicação. Use remetente autorizado pela sua conta e destinatário válido:

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Mailcore\Cliente;
use Mailcore\Excecoes\Erro;

$mailcore = new Cliente();

try {
    $resposta = $mailcore->emails->enviar([
        'remetente' => 'Equipe <avisos@seu-dominio.com>',
        'destinatarios' => ['pessoa@example.com'],
        'assunto' => 'Olá!',
        'html' => '<strong>Sua integração está pronta.</strong>',
    ], [
        'chaveIdempotencia' => 'pedido-123-confirmacao',
    ]);

    $id = $resposta['id'];
} catch (Erro $erro) {
    // Use codigo/status para decidir o tratamento da aplicação.
    // Não registre corpos, credenciais ou mensagens privadas da API.
    $codigo = $erro->codigo;
    $status = $erro->status;
} finally {
    $mailcore->fechar();
}
```

A resposta comprova a aceitação da intenção de envio. Acompanhe o estado pelos eventos e webhooks. A mesma intenção, inclusive numa tentativa explícita após falha de rede, deve conservar chave de idempotência e conteúdo. Gere uma nova chave apenas para uma nova intenção. O servidor decide a deduplicação e os conflitos.

## Recursos

As propriedades do cliente são `emails`, `lotes`, `dominios`, `chavesApi`, `contatos`, `segmentos`, `topicos`, `propriedadesContatos`, `modelos`, `webhooks`, `campanhas`, `automacoes`, `eventos`, `recebimento`, `caixas` e `cli`. Recursos relacionados ficam agrupados, por exemplo `contatos->importacoes` e `modelos->versoes`. Consulte a [cobertura dos métodos](docs/cobertura.md) e os PHPDocs das classes para os parâmetros.

Métodos e configuração do cliente usam português. Campos dos pedidos e respostas seguem o contrato público da API, inclusive os nomes definidos pelo protocolo. Listagens retornam uma página; a aplicação controla filtros e cursores.

## Configuração e credenciais

```php
$mailcore = new Cliente($chaveObtidaNoServidor, [
    'urlBase' => 'https://mailcore.com.br/v1',
    'tempoLimite' => 30,
    'tempoConexao' => 10,
    'limiteRespostaBytes' => 16 * 1024 * 1024,
]);
```

Os tempos são segundos. `certificadoCa` aceita o caminho de uma CA privada confiável, sem desativar a verificação de certificado ou identidade. HTTP só é aceito em loopback com `permitirHttpLocal: true`, para testes.

Sem chave no construtor, o cliente consulta `MAILCORE_API_KEY` em cada operação. Para rotação ou múltiplas contas, forneça uma `Closure` que retorne a chave: `new Cliente(static fn () => $provedor->obterChave())`. Um fornecedor inválido é recusado; ele não herda a variável global de outra conta. Resolva a conta em contexto confiável da aplicação.

Cada método aceita opções finais `chaveIdempotencia`, `tempoLimite`, `limiteRespostaBytes` e `cabecalhos`. Cabeçalhos de autenticação, cookies, host e enquadramento HTTP ficam reservados ao SDK. Configurações desconhecidas são recusadas.

O transporte padrão valida TLS, recusa redirecionamentos, ignora proxies de ambiente e não mantém cookies. Reutilize o cliente no ciclo de vida adequado à aplicação: as leituras GET compartilham o cache de conexões; operações que alteram dados abrem uma conexão nova para evitar o replay interno do libcurl quando uma conexão antiga cai sem resposta. O SDK não possui um laço de repetição de pedidos. O libcurl ainda pode recuperar conexões de leitura internamente. Configure idempotência para operações de envio.

O cliente executa uma operação de cada vez. Uma chamada reentrante é recusada; use clientes separados para tarefas concorrentes. `fechar()` libera o transporte próprio e é idempotente. Um cliente PSR-18 fornecido pela aplicação continua sob responsabilidade dela.

## Respostas e erros

`Resposta` contém `dados`, `status` e `cabecalhos`. O acesso `$resposta['id']` é somente de leitura. Objetos JSON tornam-se `stdClass`; listas tornam-se arrays; `null` e `false` são preservados. Inteiros além da capacidade nativa do PHP são strings. Use `property_exists($resposta->dados, 'campo')` para distinguir campo omitido de campo presente com `null`.

Nos pedidos, arrays associativos representam objetos e arrays sequenciais representam listas. Para um objeto vazio, use `(object) []`; para mapas com chaves numéricas, use `stdClass`, por exemplo `(object) ['0' => 'email']`. O objeto raiz dos métodos que recebem um objeto é preservado inclusive quando o argumento é `[]`. O SDK não modifica os dados recebidos nem transforma todas as listas em objetos.

Erros HTTP geram `Mailcore\Excecoes\ErroApi`. Falhas de rede geram `ErroTransporte`; respostas inválidas ou grandes, `ErroResposta`; configurações inválidas, `ErroConfiguracao`. As três primeiras herdam `Erro`, com `codigo`, `status` e `cabecalhos`. Respostas de erro sem JSON válido recebem mensagem genérica. A mensagem estruturada de erro da API pode conter informações privadas; não a publique automaticamente.

## Downloads e limite de memória

Downloads comuns retornam a string binária em `dados`. As variantes terminadas em `Fluxo` entregam partes ao consumidor síncrono:

```php
$arquivo = fopen('/caminho/privado/mensagem.eml', 'wb');
if ($arquivo === false) {
    throw new RuntimeException('Não foi possível abrir o destino.');
}

try {
    $resposta = $mailcore->recebimento->baixarOriginalFluxo(
        $idRecebido,
        static function (string $parte) use ($arquivo): void {
            $restante = $parte;
            while ($restante !== '') {
                $gravado = fwrite($arquivo, $restante);
                if ($gravado === false || $gravado === 0) {
                    throw new RuntimeException('Não foi possível gravar o download.');
                }
                $restante = substr($restante, $gravado);
            }
        },
        ['limiteRespostaBytes' => 32 * 1024 * 1024],
    );
    $totalBytes = $resposta->dados;
} finally {
    fclose($arquivo);
}
```

O transporte espera cada callback terminar antes de entregar a próxima parte. Exceções do consumidor encerram a transferência e são propagadas. Respostas incompletas não produzem sucesso; a aplicação deve descartar o arquivo parcial. O prazo de rede não interrompe à força código PHP bloqueado no consumidor. O limite padrão da resposta é 16 MiB, ajustável por operação; o corpo JSON do pedido tem limite de 50 MiB. Limites de negócio, como importação CSV, pertencem à API.

## PSR-18 e Symfony

Injete `clienteHttp` compatível com PSR-18 e, opcionalmente, `fabricaRequisicoes` e `fabricaFluxos` PSR-17. Sem fábricas explícitas, são usadas as de Nyholm. Não há descoberta global de transportes. Configure TLS, redirecionamento, proxy, repetição, prazo e armazenamento de resposta no provedor: PSR-18 não padroniza essas políticas. O limite do SDK vale para os bytes consumidos, sem controlar o buffer anterior do provedor.

O [exemplo Symfony](docs/symfony.md) inclui configuração explícita, controlador autenticado e testes com componentes reais do framework. A biblioteca pode ser usada em outros frameworks por Composer.

## Webhooks

`Mailcore\Assinatura::verificar($corpoBruto, $cabecalhos, $segredo)` valida HMAC-SHA256 e o instante do evento, retornando o objeto JSON autenticado. Utilize os bytes originais recebidos, antes de qualquer decodificação ou reserialização. O segredo tem prefixo `whsec_`; a tolerância padrão e máxima é de 300 segundos. Cabeçalhos ambíguos, assinatura inválida e payload incompatível geram `ErroAssinatura`. A aplicação deve deduplicar os eventos e persistir o resultado do processamento.

## Desenvolvimento e verificação

```sh
sh scripts/verificar.sh
sh scripts/verificar_api.sh
```

Execute esses comandos no checkout do SDK. O primeiro verifica PHP 8.2, 8.3, 8.4 e 8.5, tipos, formato, contrato e instalação do ZIP. O segundo usa o checkout backend local e um laboratório exclusivo de API/PostgreSQL com TLS. O host precisa apenas de shell e Docker Linux; PHP, Composer e ferramentas rodam nos containers. Veja requisitos e resultados em [verificação](docs/verificacao.md), [contribuição](CONTRIBUTING.md) e [segurança](SECURITY.md).

Licença [MIT](LICENSE). Dependências mantêm suas próprias licenças e avisos.

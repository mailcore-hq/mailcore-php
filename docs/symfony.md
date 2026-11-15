# Symfony

Este exemplo conecta um controlador Symfony diretamente ao SDK. O serviço utiliza um envelope de email fixo, configurado na inicialização da aplicação. Não é um transporte do componente Mailer e não registra DSN ou integração com Laravel.

Os exemplos e testes usam Symfony 7.4.23 LTS, PHP 8.2 ou superior e Nyholm PSR-7 1.8.2. Symfony é uma dependência opcional de desenvolvimento; instalar o SDK não instala o framework. Consulte o [ciclo da versão 7.4](https://symfony.com/releases/7.4), o [cliente HTTP](https://symfony.com/doc/7.4/http_client.html) e os [contratos PSR-18](https://www.php-fig.org/psr/psr-18/) e [PSR-17](https://www.php-fig.org/psr/psr-17/).

## Configuração

Os arquivos em `exemplos/Symfony/` pertencem à aplicação de exemplo. O namespace `Mailcore\Exemplos\` está no autoload de desenvolvimento deste repositório. Ao incorporá-los em uma aplicação, configure o autoload correspondente e registre os serviços no contêiner da aplicação.

Configure, pelo gerenciador de segredos da aplicação:

- `MAILCORE_API_KEY`: chave da API com a permissão necessária para enviar.
- `MAILCORE_REMETENTE`: endereço remetente autorizado pela conta.
- `MAILCORE_DESTINATARIO`: único destinatário deste fluxo de exemplo.
- `TOKEN_APLICACAO`: token independente da chave da API, gerado a partir de pelo menos 32 bytes aleatórios e codificado em Base64 URL sem preenchimento. São aceitos de 43 a 256 caracteres alfanuméricos, `_` e `-`.

O token da aplicação autentica quem pode acionar este fluxo. Mantenha-o no servidor ou com clientes confiáveis. O código verifica os resumos SHA-256 com comparação de tempo constante e recusa reutilizar a chave da API como token da aplicação. Formato e comprimento não comprovam aleatoriedade; gere o segredo criptograficamente.

Na inicialização, resolva as quatro variáveis pelo mecanismo de segredos da aplicação em `$ambiente` e a URL HTTPS da API em `$urlDaApi`. Depois monte o cliente, o serviço e o controlador:

```php
use Mailcore\Exemplos\Symfony\EnviarControlador;
use Mailcore\Exemplos\Symfony\FabricaClientePsr18;
use Mailcore\Exemplos\Symfony\ServicoEnvio;

$cliente = FabricaClientePsr18::criar($ambiente['MAILCORE_API_KEY'], [
    'urlBase' => $urlDaApi,
    'tempoLimite' => 30,
]);
$controlador = new EnviarControlador(new ServicoEnvio($cliente, $ambiente));
```

Não existe fallback para credenciais de outra conta. Registre o controlador como serviço e importe os atributos de rota do diretório do exemplo conforme a configuração de rotas da aplicação. A rota é `POST /enviar`.

## Contrato da rota

Envie um objeto JSON, inclusive `{}`, com os cabeçalhos:

```text
Authorization: Bearer <TOKEN_APLICACAO>
Idempotency-Key: <identificador-estavel-da-operacao>
Content-Type: application/json
```

O corpo tem limite de 4096 bytes. A leitura da entrada é limitada antes de decodificar JSON, inclusive sem `Content-Length`. O corpo e os parâmetros da URL não escolhem remetente, destinatário, assunto, conteúdo, chave ou cliente HTTP. O exemplo envia apenas a mensagem fixa definida em `ServicoEnvio`, para o endereço configurado na inicialização.

Cada operação lógica precisa de uma chave de idempotência estável. Uma tentativa explícita após resposta incerta deve conservar a chave e o mesmo envelope. O exemplo não gera chaves a cada tentativa, não armazena resultados e não repete chamadas automaticamente; a deduplicação pertence à API.

A aceitação retorna HTTP 202 com `id` e `estado`. Ela representa aceitação pela API, sem comprovar entrega SMTP. Autenticação inválida retorna 401; chave inválida ou JSON inválido retorna 400; corpo grande, 413; tipo de conteúdo incorreto, 415. Erros HTTP da API mantêm o status de erro, com mensagem genérica. Falhas de transporte ou respostas de aceitação incompatíveis retornam 502. Nenhum erro publica o corpo da API, credenciais ou seus cabeçalhos privados. As respostas usam `Cache-Control: no-store`.

## Transporte e limites

`FabricaClientePsr18` injeta `Psr18Client` e as fábricas PSR-17 explicitamente. A política do cliente Symfony mantém a verificação TLS, desabilita redirecionamentos e proxies de ambiente, evita buffer de resposta e configura tanto `timeout` quanto `max_duration`. Para uma autoridade certificadora privada, forneça `certificadoCa` com o caminho de uma cadeia confiável montada como somente leitura. Não desabilite a verificação de certificado ou identidade.

PSR-18 não padroniza prazo, redirecionamento, proxy ou limite de memória de um provedor. Ao fornecer outro cliente HTTP, configure essas políticas nele. O SDK limita os bytes que consome, mas um provedor pode armazenar a resposta antes de entregá-la ao SDK. Não injete esse provedor a partir de parâmetros HTTP. Reutilize cliente e serviço dentro do ciclo de vida adequado à aplicação.

Configure limites de corpo e prazo também no servidor HTTP e na plataforma. Evite registrar `Authorization`, `Idempotency-Key`, corpos de pedidos ou respostas de email em profiler, logs ou rastreamento. Em aplicações com profiler, revise explicitamente a coleta de conteúdo e o acesso aos relatórios; o exemplo não altera a política global de observabilidade da aplicação.

## Verificação local

`SymfonyTest` executa o roteamento real de `HttpKernel`, o controlador e o SDK com `MockHttpClient`. `Psr18InteropTest` verifica as fábricas PSR-17, cabeçalhos e DTOs, erro HTTP desconhecido e download MIME por callback. Nenhum teste usa rede externa ou credenciais reais. A entrada virtual de 1 GiB verifica a recusa de um corpo sem tamanho sem alocar o corpo inteiro.

O [MockHttpClient oficial](https://symfony.com/doc/7.4/http_client.html#mockhttpclient-and-mockresponse) comprova a integração local do adaptador; não comprova transporte TLS, implantação de uma aplicação Symfony ou entrega de email. Execute os testes pela imagem Docker de desenvolvimento e consulte o relatório geral de verificação para a matriz efetivamente executada.

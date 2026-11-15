# Segurança e privacidade

O pacote está em alpha. Relate vulnerabilidades pelo canal privado de manutenção
do projeto, com a versão afetada e uma reprodução sintética. Não publique chaves,
endereços, mensagens ou dados de clientes em issues, relatórios ou exemplos.

## Credenciais e autorização

Use o SDK no servidor. Guarde chaves em configuração protegida ou gerenciador de
segredos e limite seus escopos e domínios. Não coloque chaves em navegador, Git,
URL, imagem Docker, mensagens de erro ou logs. A API continua responsável pela
autorização de cada operação.

O cliente aceita uma chave explícita ou uma `Closure` resolvida por operação.
Somente o argumento de chave `null` consulta `MAILCORE_API_KEY`; uma `Closure`
que retorna `null` falha antes da rede, sem recorrer à variável de ambiente.
Em aplicações com várias contas, forneça a chave ou o resolvedor explicitamente
para evitar vincular uma operação à configuração global do processo.

Nos exemplos HTTP, use um token de aplicação independente da chave da API.
Preserve remetente e destinatário configurados na inicialização. Não transforme
parâmetros do usuário em credenciais, URL base, cliente HTTP ou envelope livre
de envio. O [exemplo Symfony](docs/symfony.md) documenta esse contrato.

## Comunicação e limites

O transporte cURL padrão exige HTTPS verificado, mantém a verificação de
certificado e hostname e recusa redirecionamentos. Ele desabilita proxies de
ambiente e não habilita armazenamento de cookies. Uma CA privada é fornecida por
arquivo confiável; ela não desabilita TLS. HTTP de teste exige opção explícita e
se limita a loopback.

Um cliente PSR-18 injetado possui política de rede própria. Configure nele TLS,
redirecionamentos, proxies, prazos e buffering antes de fornecer credenciais.
O SDK recusa respostas de redirecionamento que recebe, mas não controla destinos
que o provedor tenha seguido internamente. O exemplo Symfony configura essa
política explicitamente.

O limite padrão de resposta é 16 MiB e é aplicado durante a leitura, inclusive
em downloads em fluxo e respostas de erro. O transporte pede codificação
`identity` e recusa codificações não negociadas. Esses limites não substituem os
do servidor HTTP, da API ou de um provedor que armazene o corpo antes de entregá-lo
ao SDK. Ajuste limites por operação apenas quando necessários.

## Mensagens, arquivos e webhooks

Pedidos, respostas e erros de negócio podem conter dados pessoais. Não registre
corpos JSON/MIME, anexos, segredos, URLs temporárias, cabeçalhos de autorização ou
chaves de idempotência. O SDK não registra requisições automaticamente, mas isso
não altera logs, profiler ou rastreamento da aplicação e do provedor HTTP.
`ErroApi` pode preservar a mensagem de negócio recebida da API; trate-a como dado
privado e exponha somente informações apropriadas ao destinatário do erro.

Baixe MIME, anexos recebidos e exportações pelas rotas autenticadas do SDK.
URLs temporárias nos metadados são capacidades sensíveis; o SDK não as segue
automaticamente. Proteja arquivos locais e descarte downloads parciais antes de
usá-los. O consumidor de um fluxo decide onde os bytes serão armazenados e deve
aplicar permissões, criptografia e retenção adequadas.

Verifique webhooks com `Assinatura::verificar` sobre os bytes brutos, antes de
executar efeitos. O helper valida HMAC e janela temporal, mas não implementa
deduplicação durável. Preserve `svix-id` para deduplicar e limite o corpo no
servidor antes de lê-lo. Assinaturas válidas podem reaparecer dentro da janela.

## Falhas e idempotência

O SDK não implementa uma camada de repetição automática. Bibliotecas de rede,
provedores injetados e filas da aplicação podem ter comportamento próprio;
avalie essas políticas separadamente. No transporte cURL, mutações usam conexão
nova para evitar o replay interno associado a uma conexão antiga interrompida.
Consultas `GET` podem reutilizar conexões e sofrer recuperação interna do libcurl.
Preserve chave de idempotência e conteúdo
ao repetir uma operação lógica. Um timeout ou cancelamento da espera não prova
que o servidor deixou de aceitar o pedido; consulte a intenção antes de criar
uma nova. Aceitação HTTP não comprova entrega de email.

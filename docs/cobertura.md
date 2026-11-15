# Cobertura do SDK PHP

O pacote Composer `mailcore/mailcore-php`, versão `0.1.0-alpha.1`, oferece
**16 recursos principais, 30 classes de recursos e 151 operações**. Esta alpha
ainda não foi publicada. A contagem descreve métodos públicos do SDK, incluindo
variantes de download, e não 151 URLs distintas ou toda a API do produto.

O contrato segue a API Mailcore. O [relatório de API local](integracao-api.json)
registra a revisão e o hash das fontes efetivamente usados no ensaio. Esses
identificadores descrevem aquela execução, sem afirmar o estado atual do backend.

## Recursos

Os caminhos abaixo partem de uma instância de `Mailcore\Cliente`. Os métodos
usam português e nomes como `obterPorIdempotencia`, `registrosDns` e
`baixarOriginalFluxo`.

| Recurso principal | Superfície incluída | Operações |
| --- | --- | ---: |
| `emails` | Envio, consulta, agendamento/cancelamento, eventos, idempotência e anexo | 8 |
| `lotes` | Envio e consulta por idempotência | 2 |
| `dominios` | CRUD, capacidades, rastreamento, registros DNS e verificação | 7 |
| `chavesApi` | Criação, listagem, renomeação e revogação | 4 |
| `contatos` | CRUD, lote, segmentos, tópicos, importações e exportações | 20 |
| `segmentos` | Criação, listagem, consulta, edição e remoção | 5 |
| `topicos` | Criação, listagem, consulta, edição e remoção | 5 |
| `propriedadesContatos` | Criação, listagem, consulta, edição e remoção | 5 |
| `modelos` | CRUD, publicação, duplicação, prévias, testes, versões e pastas | 18 |
| `webhooks` | CRUD, eventos, tentativas e reenvio | 9 |
| `campanhas` | CRUD, duplicação, revisão, envio/cancelamento, prévias, testes e métricas | 15 |
| `automacoes` | CRUD, duplicação, parada e consulta de execuções | 9 |
| `eventos` | Definição, listagem, consulta, edição, remoção e envio de ocorrência | 6 |
| `recebimento` | Consulta, anexos, MIME original e downloads comuns ou em fluxo | 8 |
| `caixas` | CRUD, etiquetas, conversas, mensagens, respostas, encaminhamento e rascunhos | 28 |
| `cli` | Listagem e revogação de concessões | 2 |
| **Total** | | **151** |

Recursos subordinados são propriedades, como
`$cliente->contatos->exportacoes`, `$cliente->modelos->versoes`,
`$cliente->webhooks->eventos`, `$cliente->caixas->conversas->emails` e
`$cliente->cli->concessoes`. `cli` agrega as concessões e não faz uma chamada por
si. A verificação local `Mailcore\Assinatura::verificar` não entra na contagem
de operações HTTP.

## Pedidos, respostas e paginação

Os métodos são síncronos. A maioria dos pedidos aceita `array` associativo ou
`stdClass`, conservando campos do DTO HTTP, como `remetente`, `destinatarios`,
`agendada_em` e `revisao`. Opções da operação ficam em outro array, com nomes como
`chaveIdempotencia`, `tempoLimite`, `limiteRespostaBytes` e `cabecalhos`.
Regras de negócio, autorização e revisões continuam sob validação do servidor.

JSON admite valores simples, arrays e `stdClass`, com UTF-8 válido e números
finitos. Classes arbitrárias e recursos PHP são recusados. Um campo omitido difere
de `null`; `null` e `false` no corpo são preservados. Em filtros de query, `null`
é omitido e booleanos são codificados como `true` ou `false`. Use `new stdClass()`
para um objeto vazio aninhado; use `[]` para lista vazia. Os métodos que recebem
um DTO de objeto codificam o pedido principal como objeto.

`Mailcore\Resposta` separa `dados`, `status` e `cabecalhos`. Objetos JSON são
`stdClass`, listas são arrays, respostas vazias admitidas usam `null` e downloads
comuns retornam strings binárias. Números JSON maiores que o inteiro suportado
são preservados como strings. `$resposta['id']` acessa um campo de `dados` por
`ArrayAccess`, que não permite escrita por esse mecanismo.

A superfície usa declarações PHP e PHPDoc para pedidos, filtros e opções; não
oferece DTOs completos e específicos para cada entidade. `dados` permanece
`mixed`, preservando o JSON do servidor. A paginação é explícita: uma chamada
obtém uma página e mantém o envelope e os cursores recebidos, sem percorrer páginas
automaticamente ou uniformizar envelopes distintos.

## Contratos relevantes

- Envio direto admite Cc/Cco, cabeçalhos e anexos Base64/CID conforme o DTO de
  composição. Envio por modelo tem formato próprio. O SDK não busca anexos
  remotos por iniciativa própria.
- Lotes preservam a aceitação atômica e ordenada da API; o SDK não divide o
  pedido nem implementa repetição automática. Agendamento usa `agendada_em` com
  fuso e depende dos estados admitidos pelo servidor. Aceitação não comprova
  entrega SMTP.
- Importações recebem CSV UTF-8 dentro do JSON. O contrato da API limita o CSV
  a 8 MiB; o limite geral de JSON serializado de 50 MiB acomoda os escapes e não
  amplia limites menores de cada endpoint.
- Modelos, campanhas, automações, eventos e caixas mantêm revisões, estados,
  objetos vazios e campos anuláveis. Cancelar ou parar não desfaz efeitos já
  aceitos. Preenchimento e prévias retornam o formato definido na rota.
- O segredo de uma chave da API aparece na criação. Revogação pode não ter corpo.
  Concessões CLI permitem consulta e revogação, sem iniciar autorização
  interativa ou renovar tokens.

## Downloads e recebimento

MIME original, anexos recebidos e exportações usam rotas autenticadas fixas.
Metadados podem conter URLs temporárias, que o SDK não segue automaticamente.
Disponibilidade e retenção do conteúdo são controladas pela API.

`baixarOriginalFluxo`, `baixarAnexoFluxo` e `contatos->exportacoes->baixarFluxo`
recebem um callback `callable(string): void`. O consumidor recebe blocos de bytes
de forma síncrona; o retorno é uma `Resposta` cujo `dados` contém a contagem total
de bytes. O transporte não acumula o corpo de sucesso nesse modo. O consumidor
pode interromper levantando uma exceção; descarte qualquer arquivo parcial.

O teto padrão de resposta é **16 MiB**, também em fluxo e em respostas de erro.
`limiteRespostaBytes` permite alterá-lo por operação. O limite é aplicado durante
a leitura; no caminho PSR-18, ele não impede buffering anterior feito pelo
provedor. O SDK solicita codificação `identity` e recusa codificações não
negociadas, preservando bytes MIME e CSV.

## Transporte e ciclo de vida

O transporte padrão usa `ext-curl` e um handle reutilizável. Consultas `GET`
podem aproveitar conexões existentes; mutações usam uma conexão nova para evitar
que a recuperação interna de uma conexão antiga reproduza o pedido. As opções
de cada operação são limpas com `curl_reset`. Encerre com `$cliente->fechar()`.
A instância recusa operações simultâneas ou reentrantes e não oferece métodos
assíncronos ou execução `curl_multi`.

No cURL, `tempoLimite` vale **30 segundos** por padrão para a transferência total;
`tempoConexao` vale **10 segundos**, limitado pelo prazo total. O consumo lento
de callbacks da aplicação não se transforma em uma garantia de interrupção
imediata de código PHP. HTTPS verifica certificado e hostname; redirecionamentos
são recusados, proxies de ambiente são desabilitados e o armazenamento de cookies
não é habilitado. HTTP é uma exceção explícita restrita a loopback de teste.

Também é possível fornecer `clienteHttp` PSR-18 e fábricas PSR-17 explícitas.
Na ausência das fábricas, o SDK usa Nyholm. Conexão, TLS, prazos, proxy,
redirecionamentos, buffering e políticas próprias de repetição pertencem ao
provedor injetado. As opções de prazo do cURL não configuram universalmente esse
provedor. Consulte [o exemplo Symfony](symfony.md) para uma configuração
explícita e seus limites.

O SDK não implementa uma camada de repetição automática. O libcurl pode recuperar
internamente conexões reutilizadas em consultas `GET`; esse comportamento é
distinto de um laço de repetição no SDK. Preserve chave de idempotência e conteúdo
ao decidir repetir uma intenção; timeout ou cancelamento da espera não desfaz um
pedido aceito. Esta versão não apresenta benchmark nem garantia de capacidade
de envio em produção.

## Alcance da validação

A matriz **PHP 8.2–8.5** passou com 261 testes por versão. Symfony
7.4.23 e Nyholm PSR-7 1.8.2 são exercitados como integração opcional; carregar o
SDK básico não exige o framework. Não há transporte Symfony Mailer, pacote
Laravel, cliente SMTP ou renderizador React nesta versão.

Os testes de contrato conferem as 151 operações com transporte coletor. A
integração Symfony usa roteamento real e `MockHttpClient`; o ensaio de API local
usa backend e dados sintéticos isolados. Veja [a verificação](verificacao.md) e
os relatórios correspondentes para resultados, datas e limites. Esses ensaios
não comprovam publicação, implantação cloud, entrega SMTP ou desempenho sob carga.

Esta seleção não cobre toda a API do produto. Recursos globais de logs, uso e
supressões, autorização OAuth interativa e helpers de sessão ou dashboard ficam
fora desta alpha. Campos adicionais de um DTO não criam métodos ou fluxos novos.

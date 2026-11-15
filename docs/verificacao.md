# Verificação do SDK PHP

O laboratório utiliza dados sintéticos e ferramentas em Docker. Não publica
pacotes, não faz deploy e não entrega emails por SMTP. Os relatórios JSON
registram os resultados e imagens de cada execução.

Em 14/11/2026, passaram **261 testes e 1.395 asserções por versão** em PHP
8.2.35, 8.3.36, 8.4.27 e 8.5.12, sem falhas, erros ou testes ignorados.
PHPStan, formato, gerador, validação Composer e instalação offline também
passaram. A auditoria das dependências não encontrou avisos de vulnerabilidade
conhecidos na consulta registrada em `auditoria-dependencias.json`.

## Matriz, qualidade e distribuição

```sh
sh scripts/verificar.sh
```

Execute no checkout do SDK. O host precisa de shell POSIX e Docker Linux
acessível ao usuário; PHP, Composer, Python e ferramentas de qualidade ficam
nas imagens. A construção das imagens e a auditoria de dependências precisam
de acesso à internet. Os testes e a instalação dos artefatos usam `--network none`.

As bases PHP 8.2, 8.3, 8.4 e 8.5 e as ferramentas Composer/Docker estão fixadas
por digest. `composer.lock` fixa as dependências de desenvolvimento. PHPUnit
11.5.58, Symfony 7.4.23 e Nyholm PSR-7 1.8.2 são usados no laboratório.

Cada relatório `php-8.*.json` precisa indicar `resultado: "aprovado"`, sem
erros, falhas ou testes ignorados. A suíte cobre:

- As 151 operações: método, caminho, filtros, cabeçalhos, corpo, opções e
  formato de resposta, com um contrato independente coletado do SDK Node.
- HTTPS real em loopback: cadeia certificadora, hostname, ausência de cookies
  e proxies de ambiente, recusa de redirecionamento, erros e limites.
- Objetos/listas JSON, mapas numéricos, Unicode, `null`, `false`, números
  grandes e ausência de mutação dos argumentos.
- Downloads binários e por callback, respostas incompletas, limite em fluxo,
  prazos e propagação de falhas do consumidor com fechamento do transporte.
- HMAC de webhooks, janela temporal, cabeçalhos ambíguos e conteúdo bruto.
- Interoperabilidade PSR-18/PSR-17 e exemplo Symfony com roteamento real,
  autenticação, envelope fixo e entrada limitada antes da decodificação.

Os testes de rede conferem separadamente o comportamento de conexão do libcurl.
No cenário HTTP/1.1 testado, um GET numa conexão reutilizada interrompida pode
ser enviado duas vezes internamente. As mutações usam conexão nova e o POST do
mesmo cenário chega uma vez ao servidor. Isso não é uma prova de que qualquer
pilha HTTP jamais repetirá pedidos; a idempotência continua necessária.

PHPStan no nível máximo verifica `src/`, sem baseline. PHP-CS-Fixer confere
PSR-12 e `strict_types`; o gerador confere que as classes estáticas correspondem
à especificação. O SDK não gera métodos ao carregar o autoload. Composer valida
o manifesto e consulta os avisos conhecidos de segurança das dependências.

O estágio de distribuição prepara um ZIP próprio sem `vendor`, testes ou
dependências de desenvolvimento, além dos quatro pacotes de runtime separados.
O consumidor temporário resolve esses ZIPs por metadados Composer locais, com
Packagist desabilitado, e verifica plataforma, autoload, 151 métodos e uma chamada
por PSR-18. As dependências preservam seus avisos e licenças.
`instalacao-php-8.*.json` e `instalacao-local.json` registram essa prova.
O manifesto em `dist/` identifica versões e resumos dos artefatos.

## API e PostgreSQL reais locais

```sh
sh scripts/verificar_api.sh
```

Esse ensaio adicional precisa do checkout backend, runtime local da API, imagem
Rust e cache público Cargo já preparados. O controlador compila a API com
`--offline --locked` em Docker e registra revisão e SHA-256 dos fontes/binário.
`MAILCORE_BACKEND` e `MAILCORE_IMAGEM_CLIENTE` permitem escolher outros caminhos
e imagens. O controlador utiliza o socket Docker para criar e remover o
laboratório; não reutiliza os serviços ou o banco da aplicação em execução.
Veja as opções com `--help` no script.

O [relatório aprovado](integracao-api.json) registra dez grupos, 23 migrações,
HTTPS verificado e PostgreSQL com TLS `verify-full`. A rede Docker é interna,
sem portas publicadas, worker, Postal ou SMTP. As configurações, chaves e
certificados sintéticos ficam em volume temporário em memória.

Passaram escopos Full/Sending, composição/CID/Cc/Cco, replay/conflito por
idempotência, lote atômico, domínios, contatos/catálogos, importação aceita,
exportação CSV, modelos/pastas/versões/publicação com revisão, marketing sem
disparo, caixas/etiquetas/rascunhos, recebimento MIME e revogação de concessões
CLI. Quatro callbacks reais conferem CSV/MIME por cURL e por PSR-18 Symfony.

O banco terminou com três mensagens, três tarefas e um recebido pendente. O
recebimento e a importação não foram processados por worker. Esses resultados
comprovam a aceitação e persistência exercitadas, sem comprovar entrega de email.

A [interrupção SIGTERM](integracao-api-interrupcao.json) foi conferida em execução
separada: falha intencional e limpeza concluída. Uma execução normal só é
aprovada com `resultado: "aprovado"` e `limpeza_concluida: true`. Credenciais e
corpos não entram nos relatórios.

## Limites da evidência

Os testes não comprovam publicação em registro, instalação em toda combinação
de extensões/provedores, implantação cloud ou desempenho sob carga. O provedor
PSR-18 controla sua política de rede e seu buffer anterior ao consumo pelo SDK.
O exemplo Symfony não instala um transporte do componente Mailer. Consulte
[a cobertura](cobertura.md) para a superfície incluída nesta versão alpha.

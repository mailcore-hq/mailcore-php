# Histórico de mudanças

## 0.1.0-alpha.1 — 14/11/2026

Primeira alpha do pacote Composer `mailcore/mailcore-php`.

- Cliente síncrono com estado próprio, 16 recursos principais, 30 classes de
  recursos e 151 operações do contrato HTTP Mailcore.
- Pedidos em arrays/`stdClass`, respostas com JSON objeto preservado como
  `stdClass`, status e cabeçalhos separados, e acesso de leitura por `ArrayAccess`.
- Transporte cURL com handle reutilizável, conexões reutilizadas em `GET` e
  conexão nova por mutação; injeção opcional de cliente PSR-18 com fábricas
  PSR-17 e política de rede sob responsabilidade do provedor.
- Downloads autenticados de MIME, anexos e exportações; consumo síncrono por
  callback e limite de bytes durante a leitura.
- Verificação HMAC de webhooks sobre o corpo bruto, HTTPS verificado no
  transporte padrão, redirecionamentos recusados e limites de resposta.
- Ausência de camada de repetição automática no SDK e propagação de chaves de
  idempotência fornecidas pela aplicação.
- Exemplo Symfony opcional com controlador/serviço, token independente, envelope
  fixo, idempotência externa, erros sanitizados e entrada limitada.
- Verificação por Docker, testes de contrato, integração PSR-18 e ensaio de API
  local isolada. A matriz PHP 8.2–8.5 passou com 261 testes por versão e
  instalação offline do ZIP nas quatro versões.

Os ensaios locais não publicam o pacote nem comprovam entrega SMTP ou desempenho
em produção. Consulte [a cobertura](docs/cobertura.md) e
[os resultados de verificação](docs/verificacao.md).

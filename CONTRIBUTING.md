# Contribuir

O pacote Composer `mailcore/mailcore-php` está na versão `0.1.0-alpha.1` e ainda
não foi publicado. O trabalho desta etapa usa a branch `codex/sdk-php`. Escreva
código, testes e documentação em português do Brasil.

Preserve o contrato público da API Mailcore: confirme rota, método, DTO,
autorização, revisão e resposta antes de acrescentar uma operação. Uma rota de
API não comprova a existência de uma tela ou de um fluxo de interface.

## Implementação

- Mantenha os recursos modulares e o transporte compartilhado. Os recursos
  gerados são mantidos em `scripts/gerar_recursos.py`; atualize a fonte do
  gerador junto com as classes e os testes de contrato.
- Preserve nomes, envelopes, cursores, `null`, `false`, listas e objetos vazios.
  Use `stdClass` quando o JSON exigir `{}`; `[]` representa uma lista vazia.
  Não normalize DTOs diferentes para um formato inventado.
- Reutilize o cliente em operações sequenciais e encerre-o com `fechar()`.
  Mantenha as credenciais por instância e não compartilhe uma instância em
  operações simultâneas ou reentrantes.
- Preserve o limite de leitura durante downloads e a propagação de exceções do
  consumidor. Não acumule o conteúdo de um download em fluxo no transporte.
- Considere separadamente cURL e PSR-18. O provedor PSR-18 controla conexão,
  redirecionamentos, proxies, prazos, buffering e eventuais repetições próprias;
  não atribua ao contrato PSR garantias que ele não padroniza.
- Mantenha exemplos de framework opcionais e separados do núcleo. O SDK básico
  não deve exigir Symfony. Não registre transportes Mailer ou integrações de
  outros frameworks sem uma implementação e um contrato próprios.

## Verificação em Docker

Use os verificadores do projeto, sem instalar PHP ou Composer no host:

```sh
sh scripts/verificar.sh
sh scripts/verificar_api.sh
```

O primeiro comando reúne as verificações de desenvolvimento e a matriz PHP
8.2–8.5. O segundo executa o ensaio contra a API local isolada. Consulte os scripts
e [o relatório de verificação](docs/verificacao.md) para requisitos, imagens,
opções e resultados efetivamente executados. A existência da matriz não comprova
que todos os seus itens tenham terminado com sucesso.

Use dados sintéticos exclusivos, rede Docker interna e nenhuma porta publicada
nos ensaios de serviços. Não reutilize banco existente, tokens pessoais,
produção ou contas reais. Confie explicitamente na CA de teste e mantenha
verificação TLS. Registre os recursos criados e remova-os também em falha ou
interrupção. A verificação local não publica pacotes nem aplicações.

Inclua verificações pertinentes à mudança: método/caminho/query/corpo, erros HTTP,
idempotência, limite de bytes, fechamento de fluxos e ausência de dados privados
nas respostas do exemplo. Alterações de transporte precisam de ensaio HTTP real;
um cliente falso não comprova TLS ou comportamento de rede. Diferencie contrato,
HTTP sintético, API local, produção e entrega SMTP ao relatar resultados.

Descreva o problema, a mudança, as validações e os limites conhecidos. Atualize
[a cobertura](docs/cobertura.md) e [o histórico](CHANGELOG.md) quando houver
alteração pública. Relate vulnerabilidades conforme [SECURITY.md](SECURITY.md).

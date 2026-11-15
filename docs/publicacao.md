# Publicação do SDK PHP

A versão **0.1.0-alpha.1** está disponível no [Packagist](https://packagist.org/packages/mailcore/mailcore-php), mantida pela conta `mailcore-hq`. A publicação e a instalação pública foram conferidas em **15/11/2026**.

```sh
composer require mailcore/mailcore-php:0.1.0-alpha.1
```

Requer PHP 8.2 ou superior, Composer e JSON. O transporte padrão requer `ext-curl` com HTTPS e uma cadeia certificadora confiável; também é possível fornecer um cliente PSR-18.

| Referência | Valor |
|---|---|
| Nome Composer | `mailcore/mailcore-php` |
| Versão no índice | `v0.1.0-alpha.1` |
| Tag anotada | [`v0.1.0-alpha.1`](https://github.com/mailcore-hq/mailcore-php/tree/v0.1.0-alpha.1) |
| Objeto da tag | `35e2a414c87d2bc75232f2f59f1be4da5c03e641` |
| Commit do pacote | `2c2d5545af95b1f52c30e7da91e5629c9bfe0a65` |

O [índice público P2](https://repo.packagist.org/p2/mailcore/mailcore-php.json) e os [metadados do pacote](https://packagist.org/packages/mailcore/mailcore-php.json) apontaram `source.reference` e `dist.reference` para o commit acima. A tag e a versão publicada permanecem preservadas; este registro documental posterior em `main` não substitui o artefato publicado.

## Instalação confirmada

O consumidor utilizou PHP **8.2.35** em Docker, fora do checkout, com HOME e cache inicialmente vazios. A instalação usou o Packagist padrão e HTTPS, sem credenciais, plugins, scripts ou repositórios locais. O Composer resolveu cinco pacotes de runtime e nenhuma dependência de desenvolvimento.

Foram aprovados:

- Identidade, versão e referências Git no índice e no `composer.lock`.
- Comparação SHA-256 de 50 arquivos instalados, incluindo fontes, manifesto, README e licença, com a revisão publicada.
- Requisitos de plataforma, autoload PSR-4 fora do checkout, versão e User-Agent `mailcore-php/0.1.0-alpha.1`.
- 30 recursos, **151 operações e requisições PSR-18 sintéticas** e três callbacks de download.

O ZIP recebido do GitHub pelo Composer nesse ensaio teve **153.221 bytes** e SHA-256:

```text
6be350664fac2c3e1212a68e85202d658092d033303d7f7a1e5ee58e7c4deeee
```

Esse resumo identifica o arquivo recebido no ensaio. O ZIP distribuído pelo GitHub e o ZIP de runtime preparado manualmente no laboratório têm empacotamentos diferentes; a comparação dos fontes instalados foi feita contra o commit público.

A rede foi usada para resolver e baixar os pacotes. As operações do SDK utilizaram transporte sintético, sem acesso à API real ou envio de emails. O teste não mede entrega SMTP nem desempenho sob carga. Containers, consumidor, caches e arquivos temporários foram removidos após a verificação.

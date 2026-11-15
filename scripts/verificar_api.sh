#!/bin/sh
set -eu
raiz=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
backend=${MAILCORE_BACKEND:-$(dirname "$(dirname "$raiz")")/mailcore}
imagem=${MAILCORE_IMAGEM_CLIENTE:-mailcore/php-testes:8.2}
docker run --rm --stop-timeout 30 --network none \
  --mount type=bind,src=/var/run/docker.sock,dst=/var/run/docker.sock \
  --mount "type=bind,src=$backend,dst=$backend,readonly" \
  --mount "type=bind,src=$raiz,dst=$raiz" \
  --workdir "$raiz" --entrypoint python3 "$imagem" testes/controlar_api.py \
  --backend "$backend" --imagem-cliente "$imagem" "$@"

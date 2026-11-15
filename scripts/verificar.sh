#!/bin/sh
set -eu
raiz=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
mkdir -p "$raiz/docs" "$raiz/dist"
definir_base() {
  case "$1" in
    8.2) resumo=ce09ca1d4b532edf9b0347b44a1968a80d9175addb581c450ba0c9a43fa0c5ea ;;
    8.3) resumo=232331112d3a7c5b98edeeddc3b85f610172f99352c0635fe6b3e166e47062cb ;;
    8.4) resumo=b3e907a1108629c562c4bb313ce3449e34d49991f6bb2487913f6146f47200fe ;;
    8.5) resumo=050713ce008e2360ea9dc96e1f9a075690132388d917fc3552806f7a7be6f2a0 ;;
    *) exit 1 ;;
  esac
  base="php:$1-cli-bookworm@sha256:$resumo"
}
for perfil in 8.2 8.3 8.4 8.5; do
  definir_base "$perfil"
  docker build --target testes --build-arg "PHP_IMAGE=$base" \
    --tag "mailcore/php-testes:$perfil" "$raiz"
  imagem=$(docker image inspect "mailcore/php-testes:$perfil" --format '{{.Id}}')
  docker run --rm --network none --read-only --cap-drop ALL \
    --security-opt no-new-privileges:true --memory 1g --pids-limit 128 \
    --tmpfs /tmp:rw,nosuid,size=256m,mode=1777 --user "$(id -u):$(id -g)" \
    --mount "type=bind,src=$raiz/docs,dst=/relatorios" \
    --env "MAILCORE_IMAGEM_TESTE=$imagem" "$imagem" php scripts/executar_testes.php
done
docker run --rm --network none --read-only --tmpfs /tmp:rw,size=256m \
  --user "$(id -u):$(id -g)" --env COMPOSER_HOME=/tmp/composer \
  mailcore/php-testes:8.2 sh -c \
  'vendor/bin/phpstan analyse --no-progress && vendor/bin/php-cs-fixer fix --dry-run --diff --using-cache=no --allow-risky=yes && python3 scripts/gerar_recursos.py --verificar && composer validate --no-check-publish'
docker run --rm --read-only --tmpfs /tmp:rw,size=128m \
  --user "$(id -u):$(id -g)" --env COMPOSER_HOME=/tmp/composer \
  --mount "type=bind,src=$raiz/docs,dst=/relatorios" mailcore/php-testes:8.2 \
  sh -c 'composer audit --locked --format=json > /relatorios/auditoria-dependencias.json'
docker build --target distribuicao --tag mailcore/php-distribuicao:local "$raiz"
container=$(docker create mailcore/php-distribuicao:local)
trap 'docker rm --force "$container" >/dev/null 2>&1 || true' EXIT HUP INT TERM
docker cp "$container:/pacotes/." "$raiz/dist/"
docker rm "$container" >/dev/null
trap - EXIT HUP INT TERM
for perfil in 8.2 8.3 8.4 8.5; do
  docker run --rm --network none --read-only --tmpfs /tmp:rw,nosuid,size=256m,mode=1777 \
    --user "$(id -u):$(id -g)" --env COMPOSER_HOME=/tmp/composer \
    --mount "type=bind,src=$raiz/dist,dst=/pacotes,readonly" \
    --mount "type=bind,src=$raiz/docs,dst=/relatorios" "mailcore/php-testes:$perfil" \
    php scripts/verificar_instalacao.php --artefatos=/pacotes "--relatorio=/relatorios/instalacao-php-$perfil.json"
done
docker run --rm --network none --read-only --tmpfs /tmp:rw,size=64m \
  --user "$(id -u):$(id -g)" --mount "type=bind,src=$raiz/docs,dst=/relatorios" \
  mailcore/php-testes:8.2 php scripts/verificar_instalacao.php --agregar=/relatorios

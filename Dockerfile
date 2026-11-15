ARG PHP_IMAGE=php:8.2-cli-bookworm@sha256:ce09ca1d4b532edf9b0347b44a1968a80d9175addb581c450ba0c9a43fa0c5ea
FROM ${PHP_IMAGE} AS ferramentas
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates openssl python3 git unzip zip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2@sha256:ca11f643d7791ebdd90a34fc20caf11380d434007ae539a9ea75cd9212e13dca /usr/bin/composer /usr/local/bin/composer
COPY --from=docker:29-cli@sha256:5a7af0dcb0e46c803efe9b45371dd21cb500e5b4453250394ac6f06d49b6e6f1 /usr/local/bin/docker /usr/local/bin/docker
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /sdk

FROM ferramentas AS testes
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress
COPY . .
CMD ["vendor/bin/phpunit"]

FROM testes AS distribuicao
RUN php scripts/preparar_distribuicao.php /pacotes

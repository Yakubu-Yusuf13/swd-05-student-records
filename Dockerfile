FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN apk add --no-cache ca-certificates \
    && install-php-extensions pdo_mysql \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY Caddyfile /etc/caddy/Caddyfile
COPY assets/ ./assets/
COPY config/config.php ./config/config.php
COPY partials/ ./partials/
COPY src/ ./src/
COPY *.php ./

ENV PORT=80
ENV APP_READ_ONLY=1
ENV APP_SECURE_COOKIES=1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

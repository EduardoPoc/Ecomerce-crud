FROM dunglas/frankenphp:1-php8.3-bookworm

RUN install-php-extensions pdo_mysql

WORKDIR /app
COPY docker/php/Caddyfile /etc/frankenphp/Caddyfile

EXPOSE 80

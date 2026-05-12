FROM php:8.2-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        default-mysql-client \
        libcurl4-openssl-dev \
        libonig-dev \
    && docker-php-ext-install \
        curl \
        mbstring \
        pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev

RUN mkdir -p storage/mail storage/logs \
    && php -r '$required = ["pdo", "pdo_mysql", "mbstring", "openssl", "curl", "json", "fileinfo", "session"]; $missing = array_filter($required, fn ($extension) => !extension_loaded($extension)); if ($missing) { fwrite(STDERR, "Missing PHP extensions: " . implode(", ", $missing) . PHP_EOL); exit(1); }'

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} router.php"]

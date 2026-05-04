FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install bcmath exif gd mbstring pcntl pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .

COPY docker/render/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/render/start.sh /usr/local/bin/render-start

RUN composer run-script post-autoload-dump \
    && chmod +x /usr/local/bin/render-start \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

CMD ["render-start"]

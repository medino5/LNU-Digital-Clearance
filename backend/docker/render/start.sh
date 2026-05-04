#!/usr/bin/env bash
set -e

: "${PORT:=10000}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear
php artisan migrate --force

if [ "${RUN_DATABASE_SEEDER:-true}" = "true" ]; then
    seeder_class="${RUN_DATABASE_SEEDER_CLASS:-Database\\Seeders\\DatabaseSeeder}"
    php artisan db:seed --class="${seeder_class}" --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

apache2-foreground

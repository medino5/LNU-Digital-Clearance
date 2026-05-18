#!/usr/bin/env bash
set -e

: "${PORT:=10000}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:clear

apache2-foreground &
apache_pid=$!

shutdown_apache() {
    kill -TERM "${apache_pid}" 2>/dev/null || true
    wait "${apache_pid}" 2>/dev/null || true
}

trap shutdown_apache EXIT TERM INT

php artisan migrate --force

seeder_class="${RUN_DATABASE_SEEDER_CLASS:-Database\\Seeders\\UatDatabaseSeeder}"
echo "Running database seeder during deploy: ${seeder_class}"
php artisan db:seed --class="${seeder_class}" --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

wait "${apache_pid}"

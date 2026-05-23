#!/usr/bin/env bash

# Exit on error
set -euo pipefail

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
PHP_FPM_BIN="$(command -v "php-fpm${PHP_VERSION}" || true)"

if [ -z "${PHP_FPM_BIN}" ]; then
    echo "Unable to find a php-fpm binary for PHP ${PHP_VERSION}" >&2
    exit 1
fi

mkdir -p /run/php
ln -sf "/run/php/php${PHP_VERSION}-fpm.sock" /run/php/php-fpm.sock

# Start PHP-FPM
"${PHP_FPM_BIN}" -D

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"${WORDPRESS_DB_HOST}" --silent; do
    sleep 1
done

# Check if the database exists
echo "Checking if database '${WORDPRESS_DB_NAME}' exists..."
if ! mysql -u"${WORDPRESS_DB_USER}" -p"${WORDPRESS_DB_PASSWORD}" -h"${WORDPRESS_DB_HOST}" -e "USE ${WORDPRESS_DB_NAME}; SHOW TABLES LIKE 'wp_options';" | grep -q 'wp_options'; then
    echo "Database does not exist. Creating database..."
    wp db create --allow-root --path=/opt/wordpress
else
    echo "Database '${WORDPRESS_DB_NAME}' already exists. Skipping setup."
fi

# Check if there's a wp-config.php file
if [ ! -f /opt/wordpress/wp-config.php ]; then
    echo "Set up wp-config.php..."
    wp config create --allow-root --path=/opt/wordpress \
        --dbname="${WORDPRESS_DB_NAME}" \
        --dbuser="${WORDPRESS_DB_USER}" \
        --dbpass="${WORDPRESS_DB_PASSWORD}" \
        --dbhost="${WORDPRESS_DB_HOST}" \
        --dbcharset="${WORDPRESS_DB_CHARSET}" \
        --dbprefix=wp_ \
        --extra-php <<PHP
define('WP_DEBUG', true);
PHP
    echo "Installing WordPress..."
    wp core install \
    --allow-root \
    --path=/opt/wordpress \
    --url=http://localhost:8081/ \
    --title=WordPress \
    --admin_user=wordpress \
    --admin_password=wordpress \
    --admin_email=test@wordpress.local

    echo "WordPress installation completed."
else
    echo "wp-config.php already exists. Skipping setup."
fi
# Proceed with any additional commands (if passed to the script)
exec "$@"

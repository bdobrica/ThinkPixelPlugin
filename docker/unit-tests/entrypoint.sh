#!/bin/bash
set -euo pipefail

# Check if the database exists
echo "Checking if database '${WORDPRESS_DB_NAME}' exists..."
if ! mysql -u"${WORDPRESS_DB_USER}" -p"${WORDPRESS_DB_PASSWORD}" -h"${WORDPRESS_DB_HOST}" -e "USE ${WORDPRESS_DB_NAME}; SHOW TABLES LIKE 'wp_options';" | grep -q 'wp_options'; then
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
define('WP_DEBUG_DISPLAY', false);
define('WP_USE_THEMES', false);
PHP

    echo "Database does not exist. Creating database..."
    #wp db create --allow-root --path=/opt/wordpress

    echo "Installing WordPress..."
    wp core install \
    --allow-root \
    --path=/opt/wordpress \
    --url=http://localhost \
    --title=WordPress \
    --admin_user=wordpress \
    --admin_password=wordpress \
    --admin_email=test@wordpress.local

    echo "WordPress installation completed."
else
    echo "Database '${WORDPRESS_DB_NAME}' already exists. Skipping setup."
fi

# Proceed with any additional commands (if passed to the script)
exec "$@"

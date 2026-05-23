# Use a lightweight Debian base image
FROM debian:trixie-slim

# Set environment variables for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive

# Update and install required packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-client \
    php-fpm \
    php-mysql \
    php-cli \
    php-curl \
    php-gd \
    php-mbstring \
    php-xml \
    php-zip \
    php-bcmath \
    php-intl \
    ca-certificates \
    curl \
    tar \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PECL and runkit7 extension when the shipped PHP version supports it.
RUN php_version_id="$(php -r 'echo PHP_VERSION_ID;')" && \
    php_version="$(php -r 'echo PHP_VERSION;')" && \
    if [ "${php_version_id}" -lt 80400 ]; then \
    apt-get update && apt-get install -y --no-install-recommends \
    php-dev \
    php-pear \
    build-essential \
    autoconf \
    pkg-config \
    && pecl channel-update pecl.php.net \
    && yes '' | pecl install channel://pecl.php.net/runkit7-4.0.0a6 \
    && printf '%s\n' 'extension=runkit7.so' > /etc/php/mods-available/runkit7.ini \
    && phpenmod runkit7 \
    && rm -rf /var/lib/apt/lists/*; \
    else \
    echo "Skipping runkit7 install on PHP ${php_version}: upstream runkit7 4.0.0a6 does not build on PHP 8.4 yet."; \
    fi

# Install PHPUnit
RUN curl -L https://phar.phpunit.de/phpunit.phar -o /usr/local/bin/phpunit && \
    chmod +x /usr/local/bin/phpunit


# Set up directories
RUN mkdir -p /opt/wordpress && \
    chown -R www-data:www-data /opt/wordpress

# Install WordPress CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

ENV WORDPRESS_DB_NAME=wordpress
ENV WORDPRESS_DB_USER=wordpress
ENV WORDPRESS_DB_PASSWORD=wordpress
ENV WORDPRESS_DB_HOST=mariadb
ENV WORDPRESS_DB_CHARSET=utf8mb4

# Prepare WordPress testing environment
WORKDIR /opt/wordpress
RUN wp core download --allow-root --path=/opt/wordpress --locale=en_US

# Add entrypoint script to start all services
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set entrypoint
ENTRYPOINT ["entrypoint.sh"]

# Move to the tests directory
WORKDIR /opt/tests

# Default command
CMD ["bash"]

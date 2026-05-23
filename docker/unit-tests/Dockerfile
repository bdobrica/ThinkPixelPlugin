# Use a lightweight Debian base image
FROM debian:trixie-slim

# Set environment variables for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

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

# Install PHPUnit and Composer
RUN curl -L https://phar.phpunit.de/phpunit.phar -o /usr/local/bin/phpunit && \
    chmod +x /usr/local/bin/phpunit

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Set up directories
RUN mkdir -p /opt/wordpress /opt/tests && \
    chown -R www-data:www-data /opt/wordpress /opt/tests

COPY tests/composer.json tests/composer.lock /opt/tests/
RUN composer install --working-dir=/opt/tests --no-interaction --prefer-dist

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
COPY docker/unit-tests/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set entrypoint
ENTRYPOINT ["entrypoint.sh"]

# Move to the tests directory
WORKDIR /opt/tests

# Default command
CMD ["bash"]

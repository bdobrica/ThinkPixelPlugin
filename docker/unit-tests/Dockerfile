# Use a lightweight Debian base image
FROM debian:bookworm-slim

# Set environment variables for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive

# Update and install required packages
RUN apt-get update && apt-get install -y \
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
    php-dev \
    curl \
    tar \
    unzip \
    && apt-get clean

# Install PECL and runkit7 extension
RUN apt-get update && apt-get install -y php-pear && \
    pecl install runkit7-4.0.0a6 && \
    echo "extension=runkit7.so" > /etc/php/8.2/cli/conf.d/runkit7.ini

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

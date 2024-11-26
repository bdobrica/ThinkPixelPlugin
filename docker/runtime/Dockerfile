# Use a lightweight Debian base image
FROM debian:bookworm-slim

# Set environment variables for non-interactive installations
ENV DEBIAN_FRONTEND=noninteractive

# Update and install required packages
RUN apt-get update && apt-get install -y \
    nginx \
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
    curl \
    tar \
    unzip \
    && apt-get clean

# Set up directories
RUN mkdir -p /var/www/html /opt/wordpress /etc/nginx/conf.d && \
    chown -R www-data:www-data /var/www/html /opt/wordpress

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Install WordPress CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

# Make sure display_errors and display_startup_errors are configured in php.ini
RUN sed -i 's/display_errors = Off/display_errors = On/' /etc/php/*/fpm/php.ini && \
    sed -i 's/display_startup_errors = Off/display_startup_errors = On/' /etc/php/*/fpm/php.ini

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

# Expose ports for Nginx and MySQL
EXPOSE 80

# Default command
CMD ["nginx", "-g", "daemon off;"]

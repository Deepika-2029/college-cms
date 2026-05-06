# ── College CMS — Render Dockerfile ───────────────────────────────────
FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev \
    libxml2-dev libssl-dev libcurl4-openssl-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring zip gd xml curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy entire project
COPY . .

# Create required Laravel directories before composer install
WORKDIR /var/www/html/core
RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/cache/data storage/framework/views storage/logs storage/app

# Install PHP dependencies (--no-scripts to avoid package:discover failure before dirs exist)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && php artisan package:discover --ansi || true

# Set Laravel storage permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

# Apache: point DocumentRoot to project root (index.php is there)
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    <Directory /var/www/html/core>\n\
        Require all denied\n\
    </Directory>\n\
    <Directory /var/www/html/system>\n\
        Require all denied\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Generate APP_KEY and run migrations on startup via entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]

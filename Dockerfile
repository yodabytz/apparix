FROM php:8.3-fpm-alpine AS base

# Install system dependencies and runtime libs
RUN apk add --no-cache \
    nginx \
    supervisor \
    dcron \
    curl \
    freetype \
    libjpeg-turbo \
    libpng \
    libwebp \
    libzip \
    icu-libs \
    libxml2 \
    imagemagick \
    imagemagick-libs \
    oniguruma \
    mariadb-client \
    gnu-libiconv

# Install build deps, compile PHP extensions, then clean up
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        icu-dev \
        libxml2-dev \
        imagemagick-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Fix Alpine iconv (use GNU iconv instead of musl)
ENV LD_PRELOAD=/usr/lib/preloadable_libiconv.so

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .

# Re-run composer dump-autoload with full app context
RUN composer dump-autoload --optimize --no-dev

# Copy Docker configs
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/apparix.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/crontab /etc/crontabs/www-data
COPY docker/entrypoint.sh /entrypoint.sh

# Remove Docker-specific files from app directory
RUN rm -rf docker/ Dockerfile .dockerignore docker-compose.yml

# Create required directories
RUN mkdir -p \
    storage/logs \
    storage/sessions \
    storage/cache \
    storage/uploads \
    storage/downloads \
    storage/backups \
    storage/updates \
    storage/updates_temp \
    storage/security \
    public/uploads \
    content/themes \
    /var/log/nginx \
    /run/nginx \
    /var/log/supervisor

# Set permissions — www-data must own ALL app files for the update system to work
RUN chown -R www-data:www-data /var/www/html \
    /var/log/nginx \
    /run/nginx \
    && chmod -R 750 storage/ \
    && chmod 600 .env.example \
    && chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

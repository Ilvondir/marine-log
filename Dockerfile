FROM php:8.4-fpm

ARG user=www-data
WORKDIR /var/www/html

# System deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
  && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy only composer files first to leverage Docker cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

# Copy application files
COPY . /var/www/html

RUN chown -R ${user}:${user} /var/www/html && chmod -R 755 /var/www/html

EXPOSE 9000
CMD ["php-fpm"]

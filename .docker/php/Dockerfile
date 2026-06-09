FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y     libpq-dev libonig-dev libxml2-dev libzip-dev libicu-dev libgd-dev     zip unzip git curl gnupg ca-certificates sudo supervisor     && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring xml zip intl gd pcntl

COPY .docker/php/php.ini $PHP_INI_DIR/conf.d/custom.ini

# Install Redis
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www
COPY . .

RUN chown -R www-data:www-data /var/www &&     mkdir -p /var/www/storage/framework/views/livewire/classes &&     chmod -R 777 /var/www/storage

COPY .docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/php/supervisor.conf /etc/supervisor/conf.d/supervisor.conf
COPY .docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN composer install --no-dev --optimize-autoloader 2>&1 &&     npm ci 2>&1 &&     npm run build 2>&1

EXPOSE 9000
ENTRYPOINT [/entrypoint.sh]
CMD [supervisord, -c, /etc/supervisord.conf]

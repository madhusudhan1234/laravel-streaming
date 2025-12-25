FROM php:8.4-fpm-alpine

ARG UID=1000
ARG GID=1000

ENV UID=${UID}
ENV GID=${GID}

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    postgresql-dev \
    zip \
    unzip \
    oniguruma-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd pdo_pgsql

# Install PHP Redis extension via pecl
RUN apk add --no-cache autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make


# Copy custom PHP configuration
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/uploads.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g ${GID:-1000} --system laravel
RUN adduser -G laravel --system -D -s /bin/sh -u ${UID:-1000} laravel

RUN sed -i "s/user = www-data/user = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www

# Set permissions
RUN chown -R laravel:laravel /var/www
USER laravel

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

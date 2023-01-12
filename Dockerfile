FROM php:8.1-apache

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2enmod rewrite

RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

EXPOSE 80

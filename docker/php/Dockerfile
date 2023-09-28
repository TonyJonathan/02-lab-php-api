FROM php:apache-bullseye 

# Installation des extensions PDO pour MySQL
RUN docker-php-ext-install pdo pdo_mysql

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Active le module mod_rewrite
RUN a2enmod rewrite


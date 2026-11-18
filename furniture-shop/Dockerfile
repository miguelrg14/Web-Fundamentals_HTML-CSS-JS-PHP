# PHP + Apache image for the shop. Only public/ is served; src/ stays outside the web root.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'expose_php = Off\n' > "$PHP_INI_DIR/conf.d/security.ini" \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/security-hardening.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/shop/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

COPY src/ /var/www/shop/src/
COPY public/ /var/www/shop/public/

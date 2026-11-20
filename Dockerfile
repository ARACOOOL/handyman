FROM php:8.5-apache

RUN a2enmod headers \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/security.conf \
    && mkdir -p /var/lib/lion/requests \
    && chown -R www-data:www-data /var/lib/lion
COPY docker/php.ini /usr/local/etc/php/conf.d/lion.ini
COPY docker/site.conf /etc/apache2/sites-available/000-default.conf
COPY public/ /var/www/html/
COPY app/ /var/www/app/
USER www-data
EXPOSE 8080
CMD ["apache2-foreground"]

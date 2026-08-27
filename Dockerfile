# Mississippi Büfé & Motel Missouri — PHP 8.4 + Apache
FROM php:8.4-apache

# msmtp: a PHP mail() ezen keresztül tud SMTP-n levelet küldeni (opcionális,
# lásd docker-compose.yml környezeti változók)
RUN apt-get update \
 && apt-get install -y --no-install-recommends msmtp msmtp-mta \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers expires

# PHP beállítások
RUN { \
      echo 'expose_php = Off'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'sendmail_path = "/usr/bin/msmtp -t"'; \
      echo 'date.timezone = Europe/Budapest'; \
    } > /usr/local/etc/php/conf.d/site.ini

# gyorsítótár-fejlécek a statikus fájlokra
RUN { \
      echo '<IfModule mod_expires.c>'; \
      echo '  ExpiresActive On'; \
      echo '  ExpiresByType image/jpeg "access plus 30 days"'; \
      echo '  ExpiresByType text/css "access plus 7 days"'; \
      echo '  ExpiresByType application/javascript "access plus 7 days"'; \
      echo '</IfModule>'; \
    } > /etc/apache2/conf-enabled/cache.conf

# az adminban mentett adatok és a napló kívülről nem elérhetők
RUN { \
      echo '<Directory /var/www/html/data>'; \
      echo '  Require all denied'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-enabled/protect-data.conf
COPY --chown=www-data:www-data . /var/www/html/

# a foglalási napló írható legyen
RUN mkdir -p /var/www/html/data && chown www-data:www-data /var/www/html/data

# msmtp konfigurációt indításkor rakjuk össze a környezeti változókból
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

FROM php:7.2-apache-stretch

WORKDIR /var/www/html

COPY ./docker/000-default.conf /etc/apache2/sites-available/
COPY ./docker/ports.conf /etc/apache2/

EXPOSE 8080

# work around https://github.com/docker-library/openjdk/blob/0584b2804ed12dca7c5e264b5fc55fc07a3ac148/8-jre/slim/Dockerfile#L51-L54
RUN mkdir -p /usr/share/man/man1

RUN apt-get update -q && apt-get install -y \
        epubcheck \
        libzip-dev \
        unzip \
        wget \
        xdg-utils \
        xz-utils \
      && rm -rf /var/lib/apt/lists/* \
      && pecl install xdebug zip \
      && docker-php-ext-enable xdebug zip \
      && docker-php-ext-install pdo_mysql \
      && wget -nv -O- https://download.calibre-ebook.com/linux-installer.sh | sh /dev/stdin \
      && wget -nv -O- https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer 


ENV COMPOSER_ALLOW_SUPERUSER 1

FROM php:7.2-apache-stretch

ARG USER_ID
ARG GROUP_ID

WORKDIR /var/www/html

COPY ./apache/000-default.conf /etc/apache2/sites-available/
COPY ./apache/ports.conf /etc/apache2/

EXPOSE 8080

# work around https://github.com/docker-library/openjdk/blob/0584b2804ed12dca7c5e264b5fc55fc07a3ac148/8-jre/slim/Dockerfile#L51-L54
RUN mkdir -p /usr/share/man/man1

RUN addgroup --gid $GROUP_ID docker 
RUN adduser --disabled-password --gecos '' --uid $USER_ID --gid $GROUP_ID docker 

RUN apt-get update -q && apt-get install -y \
        epubcheck \
        libzip-dev \
        unzip \
        wget \
        xdg-utils \
        xz-utils \
        libicu-dev \
      && rm -rf /var/lib/apt/lists/* \
      && pecl install xdebug zip \
      && docker-php-ext-enable xdebug zip \
      && docker-php-ext-install pdo_mysql intl \
      && a2enmod rewrite \
      && wget -nv -O- https://download.calibre-ebook.com/linux-installer.sh | sh /dev/stdin \
      && wget -nv -O- https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
      && wget -nv -O- https://get.symfony.com/cli/installer | bash \
      && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

USER docker 
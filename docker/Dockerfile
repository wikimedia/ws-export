FROM php:7.3-apache-stretch

ARG USER_ID
ARG GROUP_ID

WORKDIR /var/www/html

EXPOSE 8080

# work around https://github.com/docker-library/openjdk/blob/0584b2804ed12dca7c5e264b5fc55fc07a3ac148/8-jre/slim/Dockerfile#L51-L54
RUN mkdir -p /usr/share/man/man1

RUN addgroup --gid $GROUP_ID docker 
RUN adduser --disabled-password --gecos '' --uid $USER_ID --gid $GROUP_ID docker 

RUN apt-get update -q && apt-get install -y \
        python \
        libnss3 \
        jarwrapper \
        libcommons-compress-java \
        libguava-java \
        libjackson-json-java \
        libjing-java \
        libsac-java \
        libsaxonhe-java \
        fontconfig \
        fonts-gujr-extra \
        fonts-freefont-ttf \
        fonts-linuxlibertine \
        fonts-dejavu-core \
        fonts-gubbi \
        git \
        libzip-dev \
        libicu-dev \
        unzip \
        wget \
        xdg-utils \
        xz-utils \
      && pecl install xdebug zip \
      && docker-php-ext-enable xdebug zip \
      && docker-php-ext-install pdo_mysql intl \
      && a2enmod rewrite \
      && wget -nv -O- https://download.calibre-ebook.com/linux-installer.sh | sh /dev/stdin \
      && wget https://github.com/w3c/epubcheck/releases/download/v4.2.4/epubcheck-4.2.4.zip \
      && unzip epubcheck-4.2.4.zip \
      && mv epubcheck-4.2.4 /usr/bin/. \
      && echo 'java -jar /usr/bin/epubcheck-4.2.4/epubcheck.jar "$@"' > /usr/bin/epubcheck \
      && chmod a+x /usr/bin/epubcheck \
      && wget -nv -O- https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
      && wget -nv -O- https://get.symfony.com/cli/installer | bash \
      && mv /root/.symfony/bin/symfony /usr/local/bin/symfony

USER docker 
/bin/sh
echo "############# SETTING UP LOCAL ENVIRONMENT #########################"
echo "############# SETTING UP LOCAL ENVIRONMENT #########################"
echo "############# SETTING UP LOCAL ENVIRONMENT #########################"
echo "############# SETTING UP LOCAL ENVIRONMENT #########################"

# install and enable xdebug
apk update
apk add --virtual .phpize-deps
apk add --no-cache autoconf automake g++ make nasm libtool zlib zlib-dev libc6-compat
pecl install xdebug-3.1.2

# install composer
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -rf composer-setup.php

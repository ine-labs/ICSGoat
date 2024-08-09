FROM ubuntu:20.04
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
	&& apt install -y supervisor nano net-tools procps build-essential wget curl php php-opcache php-gd php-curl php-mysqlnd php-intl php-json php-ldap php-mbstring php-mysqlnd php-xml php-zip  mariadb-client mariadb-server apache2 autoconf dpkg-dev file g++ gcc libc-dev make pkg-config re2c unzip

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY owncloud /var/www/html/owncloud

COPY files  /var/www/html/

RUN /etc/init.d/apache2 start \
        && a2enmod rewrite mime unique_id dir env headers mime rewrite setenvif \
        && /etc/init.d/apache2 restart

RUN /etc/init.d/mysql start && \
        sleep 4 && \
	echo "CREATE DATABASE IF NOT EXISTS owncloud;" | mysql && \
	echo "CREATE USER IF NOT EXISTS 'owncloud'@'localhost' IDENTIFIED BY 'password';" |  mysql && \
	echo "GRANT ALL PRIVILEGES ON *.* TO 'owncloud'@'localhost' WITH GRANT OPTION;" | mysql && \
	echo "FLUSH PRIVILEGES;" | mysql 


RUN rm /var/www/html/index.html; mkdir /var/www/html/owncloud/data ; chmod -R 777 /var/www/html/owncloud/data; chown -R www-data. /var/www/html/owncloud/data; mkdir -p /var/www/html/owncloud/apps-external; chmod -R 777 /var/www/html/owncloud/apps-external

RUN apt-get install sudo -y

COPY start.sh /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh 

CMD /usr/local/bin/start.sh


FROM ubuntu:20.04

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www/html

RUN apt-get update; apt-get install supervisor  wget net-tools apache2 apache2-utils php php-mysql libapache2-mod-php php-cli php-cgi php-gd procps nano unzip curl psmisc mysql-server daemon -y

RUN usermod -d /var/lib/mysql/ mysql

RUN a2enmod rewrite

RUN /etc/init.d/mysql start && \
        sleep 4 && \
	echo "CREATE DATABASE IF NOT EXISTS wordpress;" | mysql && \
	echo "CREATE USER IF NOT EXISTS 'wordpress'@'localhost' IDENTIFIED BY 'wordpress';" |  mysql && \
	echo "GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'localhost' WITH GRANT OPTION;" | mysql && \
	echo "FLUSH PRIVILEGES;" | mysql 

COPY start.sh /usr/local/bin/start.sh

WORKDIR /var/www
RUN rm -rf html

COPY html.tar.gz /var/www/

RUN tar -xvzf html.tar.gz; rm html.tar.gz

COPY all_db_backup.sql /var/www/html
COPY config.sh /tmp/config.sh


RUN chmod +x /usr/local/bin/start.sh; chmod +x /tmp/config.sh; chmod -R 777 /var/www/html/wp-content/*


CMD /usr/local/bin/start.sh

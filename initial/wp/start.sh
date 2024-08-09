#!/bin/bash
/tmp/config.sh
rm /tmp/config.sh
sleep 1
/etc/init.d/apache2 start
/etc/init.d/mysql start
sleep 10
mysql -u root wordpress < /var/www/html/all_db_backup.sql
rm /var/www/html/all_db_backup.sql
supervisord -n


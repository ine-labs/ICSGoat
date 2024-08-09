#!/bin/bash
addr=$(hostname -i)
sed -i "s/X.X.X.X/$addr/g"  /var/www/html/ownclouddb.sql

sed -i "s/X.X.X.X/$addr/g" /var/www/html/owncloud/config/config.php

mysql -u root test < /var/www/html/ownclouddb.sql


chmod -R 777 /var/www/html/owncloud/data/

chmod -R 777 /var/www/html/owncloud/config/config.php

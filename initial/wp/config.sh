#!/bin/bash
addr=$(hostname -i)
sed -i "s/X.X.X.X/$addr/g" /var/www/html/all_db_backup.sql


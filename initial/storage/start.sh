#!/bin/bash
/etc/init.d/mysql start
sleep 4
/etc/init.d/apache2 start
sleep 2

sudo -u www-data /var/www/html/owncloud/occ maintenance:install \
   --database "mysql" \
   --database-name "owncloud" \
   --database-user "owncloud"\
   --database-pass "password" \
   --admin-user "admin" \
   --admin-pass "admin"

sleep 2

curl -u admin:admin -X MKCOL "http://localhost/remote.php/dav/files/admin/Confidential"
sleep 1
curl -u admin:admin -X PUT --data-binary @/var/www/html/patient_records.csv "http://localhost/remote.php/dav/files/admin/Confidential/patient_records.csv"
sleep 1
curl -u admin:admin -X MKCOL "http://localhost/remote.php/dav/files/admin/Confidential/.ssh"
sleep 1
curl -u admin:admin -X MKCOL "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys"
sleep 1
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/config.txt "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/config.txt"
sleep 1
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/alice.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/alice.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/bob.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/bob.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/charles.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/charles.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/john.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/john.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/johnny.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/johnny.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/mary.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/mary.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/mike.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/mike.pem"
curl -u admin:admin -X PUT --data-binary @/var/www/html/.ssh/keys/sophia.pem "http://localhost/remote.php/dav/files/admin/Confidential/.ssh/keys/sophia.pem"


chmod -R 777 /var/www/html/owncloud/data/admin/files
chown -R www-data. /var/www/html/owncloud/data/admin/files
sleep 5
sudo -u www-data /var/www/html/owncloud/occ config:system:set trusted_domains 1 --value='pharma.tech.local'
sleep 2
curl -v localhost/index.php/login
supervisord -n
# encryption
 :lock_with_ink_pen: server side encryption of files
 
 [![Build Status](https://travis-ci.org/owncloud/encryption.svg?branch=master)](https://travis-ci.org/owncloud/encryption)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_encryption)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_encryption)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_encryption&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_encryption)

In order to use this encryption module you need to enable server-side
encryption in the admin settings. Once enabled this module will encrypt
all your files transparently. The encryption is based on AES 256 keys.
The module won't touch existing files, only new files will be encrypted
after server-side encryption was enabled. It is also not possible to
disable the encryption again and switch back to a unencrypted system.
Please read the documentation to know all implications before you decide
to enable server-side encryption.

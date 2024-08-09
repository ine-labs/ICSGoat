files_pdfviewer
======

This application integrates the [PDF.js](https://mozilla.github.io/pdf.js/) library into ownCloud. Using this application users can view their PDF files online without downloading the file.

[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_pdfviewer&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_files_pdfviewer)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_pdfviewer&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_files_pdfviewer)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_pdfviewer&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_files_pdfviewer)
[![Build Status](https://drone.owncloud.com/api/badges/owncloud/files_pdfviewer/status.svg?branch=master)](https://drone.owncloud.com/owncloud/files_pdfviewer)


Instructions to update pdfjs
===========
1. Prerequisites
- Install npm
- Install bower
- Install gulp

2. Update pdfjs version in bower.json

3. Run `make rebuild-pdfjs`

4. New version of the library will be in `js/vendor/pdfjs`

5. Check if `templates/viewer.php` needs to be updated to match `js/vendor/pdfjs/web/viewer.html`

6. Test the app ;)


Maintainers
===========
- [Lukas Reschke](https://github.com/LukasReschke)

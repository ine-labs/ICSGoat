# Integration with Microsoft Office Online

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/wopi/status.svg?branch=master)](https://drone.owncloud.com/owncloud/wopi)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_wopi&metric=alert_status&token=3b2a3481f04d8ca03a8b5ccc44602f70d92878b3)](https://sonarcloud.io/dashboard?id=owncloud_wopi)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_wopi&metric=security_rating&token=3b2a3481f04d8ca03a8b5ccc44602f70d92878b3)](https://sonarcloud.io/dashboard?id=owncloud_wopi)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_wopi&metric=coverage&token=3b2a3481f04d8ca03a8b5ccc44602f70d92878b3)](https://sonarcloud.io/dashboard?id=owncloud_wopi)
Collaboratively work with Office documents in the browser.

With Microsoft Office Online users can work with Office documents in the browser. The extension connects ownCloud with Microsoft Office Online Server via the WOPI protocol. Please note that a Microsoft Office Online Server is required to use the integration. Out-of-the box only the on-premise version of Microsoft Office Online Server is supported.

## Setup
You need an [OfficeOnline Server](https://docs.microsoft.com/de-de/officeonlineserver/deploy-office-online-server) installed.

The app supports all current versions which are supported by Microsoft. When new versions are released they will work as long as the API doesn't change on the Microsoft side. As WOPI is a standard, this is not likely. Often there will be networking issues at the customer side which need debugging. Please reproduce if those occur.

All involved servers (OfficeOnline Server and the ownCloud server) need to be accessible by HTTPS with valid certificates.

Add the following to config.php:
```
'wopi.token.key' => 'replace-with-your-own-random-string',
'wopi.office-online.server' => 'https://your.office.online.server.tld',
```

## Developing

The integration is based on the [Web Application Open Platform Interface (WOPI)](https://wopi.readthedocs.io/en/latest/).
This app provides a WOPI endpoint for Microsoft Office Online to communicate with ownCloud.

## How to run the wopi-validator on a local setup for testing?

0. Make sure your local owncloud test system is reachable via the docker host ip (use ifconfig to find out)
1. Create an empty file named test.wopitest in user folder e.g. `curl -s -d "" -X PUT http://localhost:8080/remote.php/dav/files/admin/test.wopitest -u admin:admin`
2. Generate wopi token env `./occ wopi:get-token -o env admin test.wopitest http://localhost:8080/index.php > wopi.env`
3. Source env `source wopi.env`
4. Run testsuite (select specific with e.g. -e WOPI_TESTGROUP=FileVersion) `docker run --add-host="localhost:123.456.789" tylerbutler/wopi-validator -- -w $WOPI_URL -t $WOPI_TOKEN -l 0 --testgroup EditFlows`


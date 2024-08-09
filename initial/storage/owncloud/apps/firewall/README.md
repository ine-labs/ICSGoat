
# File Firewall


## Installation
 * run ``` composer install ```
 * run ``` bower install ```

## Testing

### Automated
Drone and Scrutinizer run various integration, unit, coverage
and code quality checks.
See ``.drone.yml`` and ``.scrutinizer.yml`` for details.

### Manual
See UI Testing in Core with Selenium
https://doc.owncloud.org/server/10.0/developer_manual/core/ui-testing.html#ui-testing-in-core-with-selenium
and follow that to get yourself a local ownCloud installed and running, and the selenium server running.

Then get this app and install it into your core ownCloud. The steps below assume
you are in the owncloud core folder.

- clone the app into your app directory:
```
git clone https://github.com/owncloud/firewall.git apps/firewall

```
- enable the app:
```
php occ app:enable firewall
```
- run the UI tests:
```
cd tests/acceptance
./run.sh --config ../../apps/firewall/tests/acceptance/config/behat.yml
```

You can run just a part of the UI tests:

- run the scenarios in a single feature file:
```
./run.sh --config ../../apps/firewall/tests/acceptance/config/behat.yml --feature ../../apps/firewall/tests/acceptance/features/xxxx.feature
```
- run just a single scenario in a feature file by adding the line number of the "Scenario:" statement in the feature file:
```
./run.sh --config ../../apps/firewall/tests/acceptance/config/behat.yml --feature ../../apps/firewall/tests/acceptance/features/xxxx.feature:line-number
```
 
## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/firewall/status.svg?branch=master)](https://drone.owncloud.com/owncloud/firewall)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_firewall&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_firewall)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_firewall&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_firewall)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_firewall&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_firewall)

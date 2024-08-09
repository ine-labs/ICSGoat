# Notifications

Notification backend and UI for the notification panel/icon.
Used for notifications of other apps ([announcementcenter](https://github.com/owncloud/announcementcenter), [federatedfilesharing](https://github.com/owncloud/core/tree/master/apps/federatedfilesharing) etc.)

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/notifications/status.svg?branch=master)](https://drone.owncloud.com/owncloud/notifications)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_notifications&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_notifications)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_notifications&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_notifications)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_notifications&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_notifications)

## Screenshots

### No notifications (Sample)

**Note:**
In ownCloud 8.2 the app hides itself, when there is no app registered,
that creates notifications. In this case the bell and the dropdown are not
accessible.

![Build Status](img/sample-empty.png)

### New notifications (Sample)

![Build Status](img/sample-new.png)

## Notification workflow

For information how to make your app interact with the notifications app, see
[Sending and processing/"mark as read" notifications as an ownCloud App](https://github.com/owncloud/notifications/blob/master/docs/notification-workflow.md)
in the wiki.

If you want to present notifications as a client, see [Reading and deleting notifications as an ownCloud Client](https://github.com/owncloud/notifications/blob/master/docs/ocs-endpoint-v1.md).

Activity App
===============

The ownCloud Activity application enables users to not only get a summarized overview of all file and folder events in their ownCloud, but also to receive notifications for such via email. The user can configure their individual Activity preferences in their personal settings and can decide in detail which file or folder actions should be listed in the Activity stream (accessible via the app launcher) and also for which file or folder actions the users wants to receive email notifications. The bulk email notifications can either be sent out hourly, daily or weekly to fit the specific needs of the individual user.

From creation of new files or folders, to file or folder changes, updates, restores from trash bin, sharing activities, comments, tags and downloads from public share links - the ownCloud Activity app gathers all file or folder related actions in one place for the user to review. For users with lots of activity it is possible to limit the Activity stream to 'Favorites' in order to avoid noise. Furthermore the application provides filters to give users the means to maintain overview by reducing entries to relevant information.

And there you have it - a complete overview of all file and folder activities in your ownCloud with the additional ability to receive activity notifications via email in a time interval of your choice. Never again miss an important event related to content in ownCloud and always be up-to-date on all activities of your files and folders.

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/activity/status.svg?branch=master)](https://drone.owncloud.com/owncloud/activity)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_activity&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_activity)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_activity&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_activity)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_activity&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_activity)

# Add new activities / types for other apps

With the activity manager extensions can be registered which allow any app to extend the activity behavior.

In order to implement an extension create a class which implements the interface `\OCP\Activity\IExtension`.

The PHPDoc comments on each method should give enough information to the developer on how to implement them.

# File Lifecycle Management
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_lifecycle&metric=alert_status&token=41210ba0825dfe874e31fb90b2f6aee50b0812e3)](https://sonarcloud.io/dashboard?id=owncloud_files_lifecycle)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_lifecycle&metric=security_rating&token=41210ba0825dfe874e31fb90b2f6aee50b0812e3)](https://sonarcloud.io/dashboard?id=owncloud_files_lifecycle)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_lifecycle&metric=coverage&token=41210ba0825dfe874e31fb90b2f6aee50b0812e3)](https://sonarcloud.io/dashboard?id=owncloud_files_lifecycle)

Tom Needham, Michael Barz, Patrick Maier
ownCloud Commercial License

Manage the lifecycle of files within ownCloud to keep your working structure clean, organized and up to date. Choose an archiving and expiration policy that suits your workflow and select an appropriate archive destination to store your old files. Policies can be configured to include and exclude certain files, users
and groups from the archiving actions.

## Usage

### For Users

#### Archive Process

To see when and if files are scheduled for archive, a user may select a file within the files app, and in the info sidebar, they can see the remaining days until it will be automatically archived. If the file is waiting for the background job to move it to the archive, it will show 'Scheduled for archive!'.

Depending on the policy configuration, users may be able to archive their own files using a file action in the files app.

Whilst a file is in the archive, it is not readable with the sync client or web UI, but can be located using the Archive Browser. Existing metadata including shares, comments and tags are preserved, but will not be available.

#### Browsing the Archive

Users can browse the archive in a similar fashion to the trashbin, using the 'Archived Files' file list available on the bottom left of the files app. Folder structures are recreated showing the paths that were present at the time the file was archived.

#### Restoring Files

Depending on the policy configuration, users may be able to restore files in the Archive Browser by clicking the 'Restore' action on the file or folder row. Specific policies may require different permissions to access this option, or permanently disable it.

#### Activities

Lifecycle events are added to a files activity history. These can be viewed within the file sidebar in the files app or within the Activity Stream. Within the personal settings page, users can choose to receive emails relating to these events.

### For Admins

Note: disable `holding_period` if enabled - as `files_lifecycle` replaces it in
terms of functionality.

#### Upload Time Tracking

The first part of file lifecycle management begins with knowing when files first enter ownCloud. To start tracking this the app must be enabled. This will store an `upload-time` with each file, which is the server time at which it first appeared on the ownCloud server. Only files are tracked, not folders since these are never archived, only the files within them.

If installing this app on a system with existing data, the `lifecycle:set-upload-time` command can be used to pre-set a specific upload time to all existing files, so they may also be included in the archiving workflow. Files without an `upload-time` will not be considered for archive.
For example: `occ lifecycle:set-upload-time 2019-11-01`

#### Policy Configuration

The Lifecycle app uses policies to determine which files to archive and when, as
well as when to expire the files from archive, if at all. Hard and Soft policies
are available, depending on the use case requirements.

By default, the SoftPolicy is used. To configure the policy, use the `occ` command:

`occ config:app:set files_lifecycle policy --value='hard'`

##### Hard Policy

The Hard policy is designed to enforce strict controls on users data, forcing archive after a set time, and requiring escalated permissions in order to restore.

Three options are available for controlling this policy, all set via `occ config:app:set` under the `files_lifecycle` app:

 - `archive_period` int - The number of days after upload, or restore, that the file will be archived
 - `expire_period` int - The number of days after archive which the file will be  permanently deleted
 - `excluded_groups` string, comma separated group ids - Groups who are exempt from lifecycle policy.

 With Hard Policy, users are not permitted to restore their own files. Impersonators are allowed to restore files of the users they are impersonating. This allows managers to restore specific files or folders for users on request.

##### Soft Policy

The Soft policy works similarly to the Hard policy, however users are allowed to
restore their own files by default. This is for use cases where the main working
directory  should be reserved for actively used files, and users are encouraged to
archive older data. Admins are also still able to impersonate users and restore
files for them to assist where necessary.

See Hard Policy for the three time period options available for configuring when files
are moved to the archive and expired permenantly.


  #### Archive Destination

  By default, archived files are also stored within the ownCloud data directory,
  but outside the users files directories so that they are not accessible using the
  web ui and sync clients. Normal files are stored in `/datadir/$userid/files`, whilst
  archived files are stored in `/datadir/$userid/archive/files/`.

  #### Archive and Expiry Background Jobs

  Scanning the dataset for files that should be archived, and expired, given the active
  policies is expensive. For this reason, those jobs are delegated to specific `occ`
  commands which should be executed using cron jobs on a daily schedule.

  `occ lifecycle:archive` will move files scheduled for archive into the archive.

  `occ lifecycle:expire` will permanently delete files from the archive that have
  met the appropriate policy rules.

#### Restoring Files

Admins may restore users files manually using the command `occ lifecycle:restore`.
If a user would like the folder `/work/projects/project1` restored, the admin can trigger the following command:

`occ lifecycle:restore /userid/archive/files/work/projects/project1`

If you would like to stop using Lifecycle Management and restore all files from  all archives back to their owners' files directories, you can use the `restore-all`  command:

`occ lifecycle:restore-all`

which will restore all files from all users, and report on the progress as it does so.

#### Audit Events

During archive, restore and expiry, appropriate Audit events are emitted. This requires `audit_splunk` v0.3.1 minimum.

#### Disable the UI

It could be desired to disable the whole UI for this app. This can be done by setting the following configuration value.

`occ config:app:set files_lifecycle disable_ui --value='yes'`

To enable the UI again, this config value needs to be removed.

`occ config:app:delete files_lifecycle disable_ui`

#### Notes about Archived Files

 - Shares pointing to archived files will not be found but will resume upon restore
 - Users' archives are not currently transferred with `occ transfer-ownership`
 - Files within a users trashbin are not archived, use trashbin deletion policies
 - Archived files count towards the user's quota

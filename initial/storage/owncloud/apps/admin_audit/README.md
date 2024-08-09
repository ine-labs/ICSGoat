# admin_audit
 :mag: Auditing module for ownCloud to trace user and admin actions

[![Build Status](https://travis-ci.com/owncloud/admin_audit.svg?token=q8ZoWBCat8DFpZ2ALfXP&branch=master)](https://travis-ci.com/owncloud/admin_audit)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_admin_audit&metric=alert_status&token=27561a290390636bda4680d9711773dfd64159bc)](https://sonarcloud.io/dashboard?id=owncloud_admin_audit)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_admin_audit&metric=security_rating&token=27561a290390636bda4680d9711773dfd64159bc)](https://sonarcloud.io/dashboard?id=owncloud_admin_audit)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_admin_audit&metric=coverage&token=27561a290390636bda4680d9711773dfd64159bc)](https://sonarcloud.io/dashboard?id=owncloud_admin_audit)

## Configuration

Configuration is needed to redirect the messages into a separate file.

Add these lines to "config.php" and adjust the target path accordingly:
```
'log.conditions' => [
	[
		'apps' => ['admin_audit'],
		'logfile' => '/var/www/owncloud/data/admin_audit.log'
	]
]
```
Please note that the target path must be writeable for the web server user.

All messages regardless of log level will be logged there.

To ignore all CLI triggered events (not the default), you can set the following 
option:

`occ config:app:set admin_audit ignore_cli_events --value='yes'`

### Grouped Logging

With each log message, a number of users are calculated to be the 'audit context'. 
This is the list of users which are related to the log message. Additionally, 
each log message includes a list of groups that the users are a member of, to enable
filtering / splitting of the log messages at a later date. In cases when users are 
members of many groups, to reduce the data output, the group list can be filtered
 using the following config option:
 
```
'admin_audit.groups' => [
    'group1',
    'group2'
]
```

When the filter is configured, only the filtered list of groups will be output in
`auditGroups`, else, all groups that the `auditUsers` are a member of are output.

### Connect with Splunk Cloud

Install the Universal Forwarder. Connect to the deployment server:  
`splunk set deploy-poll input-prd-your-server-here.cloud.splunk.com:8089`

Install the Splunk Cloud credentials:  
`splunk install app path/to/splunkclouduf.spl -auth admin:changeme`

Monitor the ownCloud Splunk audit log, add this to `inputs.conf`:

```
[monitor://var/www/owncloud/data/admin_audit.log]
 disabled = false
 sourcetype = _json
 index = main
```

Finally, configure the following `props.conf` to ensure the time field is 
correctly used and the fields are extracted.

```
[_json]
 INDEXED_EXTRACTIONS = json
 KV_MODE = json
 TIMESTAMP_FIELDS = [Time]
 category = Structured
```

## Output

The audit app listens for internal ownCloud events and hooks and produces a rich
set of audit entries useful for reporting on usage of your ownCloud server.

Log entries are based upon the internal ownCloud logging system, but utilise 
extra fields to hold relevant data fields related to the specific event. Each event
will contain the following data at a minimum:


`remoteAddr` - string - the remote client IP  
`user` - string - the UID of the user performing the action. Or "IP x.x.x.x.", "cron", "CLI", "unknown"  
`url` - string - the process request URI  
`method` - string - the HTTP request method  
`userAgent` - string - the HTTP request user agent  
`time` - string - the time of the event eg: 2018-05-08T08:26:00+00:00  
`app` - string - always 'admin_audit'  
`message` - string - sentence explaining the action  
`action` - string - unique action identifier eg: file_delete or public_link_created  
`CLI` - boolean - if the action was performed from the CLI  
`level` - integer - the log level of the entry (usually 1 for audit events)  


### Files
##### file_create
When a file is created.

`path` - string - The full path to the create file.  
`owner` - string - The UID of the owner of the file.  
`fileId` - string - The newly created files identifier.
##### file_read

When a file is read.

`path` - string - The full path to the file.  
`owner` - string - The UID of the owner of the file.  
`fileId` - string - The files identifier.
##### file_update

`path` - string - The full path to the updated file.  
`owner` - string - The UID of the owner of the file.  
`fileId` - string - The updated files identifier.
##### file_delete

`path` - string - The full path to the updated file.  
`owner` - string - The UID of the owner of the file.  
`fileId` - string - The updated files identifier.
##### file_copy

`oldPath` - string - The full path to the source file.  
`path` - string - The full path to the new file.  
`sourceOwner` - string - The UID of the owner of the source file.  
`owner` - string - The UID of the owner of the file.  
`sourceFileId` - string - The source files identifier.  
`fileId` - string - The new files identifier.

##### file_rename

`oldPath` - string - The original path file.  
`path` - string - The new path file.   
`fileId` - string - The files identifier.

##### file_trash_delete

`owner` - string - The UID of the owner of the file.  
`path` - string - The full path to the deleted file.  

##### file_trash_restore

`owner` - string - The UID of the owner of the file.  
`fileId` - string - The restored files identifier.  
`oldPath` - string - The original path to the file.  
`newPath` - string - The new path to the file.  
`owner` - string - The UID of the owner of the file. 

##### file_version_delete

`path` - string - The full path to the version file deleted.  
`trigger` - string - The delete trigger reasoning.

##### file_version_restore

`path` - string - The full path to the file being restored to the new version.  
`revision` - string - The revision of the file restored

### Users

##### user_created

`targetUser` - string - The UID of the created user.

##### user_password_reset

`targetUser` - string - The UID of the user.

##### group_member_added

`targetUser` - string - The UID of the user.  
`group` - string - The GID of the group.


##### user_deleted

`targetUser` - string - The UID of the user.

##### group_member_removed

`targetUser` - string - The UID of the user.  
`group` - string - The GID of the group.


##### user_state_changed

`targetUser` - string - The UID of the user.  
`enabled` - boolean - If the user is enabled or not.

##### group_created

`group` - string - The GID of the group.


##### group_deleted

`group` - string - The GID of the group.


##### user_feature_changed

`targetUser` - string - The UID of the user.  
`group` - string - The GID of the group (or empty string).  
`feature` - string - The feature that was changed.  
`value` - string - The new value.

### Sharing

Sharing events come with a default set of fields:

`fileId` - string - The file identifier for the item shared.  
`owner` - string - The UID of the owner of the shared item.  
`path` - string - The path to the shared item.  
`shareId` - string - The sharing identifier. (not available for public_link_accessed
or when recipient unshares)


##### file_shared

`itemType` - string - `file` or `folder`  
`expirationDate` - string - The text expiration date in format 'yyyy-mm-dd'    
`sharePass` - boolean - If the share is password protected.  
`permissions` - string - The permissions string eg: "READ"  
`shareType` - string - `group` `user` or `link`  
`shareWith` - string - The UID or GID of the share recipient. (not available for public link)  
`shareOwner` - string - The UID of the share owner.  
`shareToken` - string - For link shares the unique token, else null

##### file_unshared

`itemType` - string - `file` or `folder`  
`shareType` - string - `group` `user` or `link`  
`shareWith` - string - The UID or GID of the share recipient.  


##### share_permission_update

`itemType` - string - `file` or `folder`  
`shareType` - string - `group` `user` or `link`  
`shareOwner` - string - The UID of the share owner.  
`permissions` - string - The new permissions string eg: "READ"  
`shareWith` - string - The UID or GID of the share recipient. (not available for public link)  
`oldPermissions` - string - The old permissions string eg: "READ"

##### share_name_updated

`oldShareName` - string - The previous share name.  
`shareName` - string - The updated share name.

##### share_password_updated

`itemType` - string - `file` or `folder`  
`shareOwner` - string - The UID of the share owner.  
`permissions` - string - The full permissions string eg: "READ"  
`shareToken` - string - The share token.  
`sharePass` - boolean - If the share is password protected.

##### share_expiration_date_updated

`itemType` - string - `file` or `folder`  
`shareType` - string - `group` `user` or `link`  
`shareOwner` - string - The UID of the owner of the share.  
`permissions` - string - The permissions string eg: "READ"  
`expirationDate` - string - The new text expiration date in format 'yyyy-mm-dd'  
`oldExpirationDate` - string - The old text expiration date in format 'yyyy-mm-dd'

##### share_accepted

`itemType` - string - `file` or `folder`.  
`path` - string - The path of the shared item.  
`owner` - string - The UID of the owner of the shared item.  
`fileId` - string - The file identifier for the item shared.  
`shareId` - string - The sharing identifier. (not available for public_link_accessed)  
`shareType` - string - `group` `user`

##### share_declined

`itemType` - string - `file` or `folder`.  
`path` - string - The path of the shared item.  
`owner` - string - The UID of the owner of the shared item.  
`fileId` - string - The file identifier for the item shared.  
`shareId` - string - The sharing identifier. (not available for public_link_accessed)  
`shareType` - string - `group` `user`

##### federated_share_received
`name` - string - The path of shared item  
`targetuser` - string - The target user who sent the item  
`shareType` - string - `remote`

##### federated_share_accepted
`itemType` - string - The path of shared item  
`targetUser` - string - The target user who sent the item  
`shareType` - string - `remote`

##### federated_share_declined
`itemType` - string - The path of shared item  
`targetuser` - string - The target user who sent the item  
`shareType` - string - `remote`  

##### public_link_accessed

`shareToken` - string - The share token.  
`success` - boolean - If the request was successful.  
`itemType` - string - `file` or `folder`

##### public_link_removed

`shareType` - string - `link`

##### public_link_accessed_webdav

`token` - string - The token used to access the url.

##### federated_share_unshared

`targetUser` - string - The user who initiated the unshare action  
`targetmount` - string - the file/folder unshared.  
`shareType` - string - `remote`  

### Custom Groups

##### custom_group_member_removed

`removedUser` - string - The UID of the user that was removed from the group.  
`group` - string - The custom group name.  
`groupId` - integer - The custom group id.

##### custom_group_user_left

`removedUser` - string - The UID of the user that left the group.  
`group` - string - The custom group name.  
`groupId` - integer - The custom group id.

##### custom_group_user_role_changed

`targetUser` - string - The UID of the user that changed role.  
`group` - string - The custom group name.  
`groupId` - integer - The custom group id  
`roleNumber` - integer - The new role number. 0 = member, 1= admin.  

##### custom_group_renamed

`oldGroup` - string - The old custom group name.  
`group` - string - The new custom group name.  
`groupId` - integer - The custom group id

##### custom_group_created

`group` - string - The custom group name created.  
`groupId` - The custom group id.  
`addedUser` - string - The UID of the user added.  
`admin` - boolean  

### Comments

All comment events have the same data:

`commentId` - string - The comment identifier.  
`path` - string - The path to the file that the comment is attached to.  
`fileId` - string - The file identifier.

##### comment_created
##### comment_deleted
##### comment_updated

### Config

##### config_set

`settingName` - string - The key.  
`settingValue` - string - The new value.  
`oldValue` - string - The old value.  
`created` - boolean - If the setting is created for the first time.

##### config_delete

`settingName` - string - The key.  

### Console

##### command_executed

`command` - string - The exact command that was executed.

### Tags

##### tag_created

`tagName` - string - The tag name.

##### tag_deleted

`tagName` - string - The tag name.

##### tag_updated

`oldTag` - string - The old tag name.  
`tagName` - string - The new tag name.

##### tag_assigned

`tagName` - string - The tag name.  
`fileId` - string - The file identifier to which the tag was assigned.  
`path` - string - The path to the file.

##### tag_unassigned

`tagName` - string - The tag name.  
`fileId` - string - The file identifier from which the tag was unassigned.  
`path` - string - The path to the file.

### Apps

##### app_enabled

`targetApp` - string - The app ID of the enabled app.  
`groups` - string[] - Array of group IDs if the app was enabled for certain groups.

##### app_disabled

`targetApp` - string - The app ID of the disabled app.

### Auth

##### user_login

`success` - boolean - If the login was successful.  
`login` - string - The attempted login value.

##### user_logout

### Holding Period
(requires at least v0.1.3)

### File Lifecycle
(requires at least v1.0.0)

##### lifecycle_archived

`path` - string - The path to the file that was archived  
`owner` - string - The UID of the owner of the file that was deleted  
`fileId` - integer - The file ID for the file that was archived

##### lifecycle_restored

`path` - string - The path to the file that was restored  
`fileId` - integer - The number of days interval specified during expiration

##### lifecycle_expired

`fileId` - integer - The file id of the file that was expired

### User Preference

##### update_user_preference_value
`key` - string - The key  
`value` - string - The value associated with the key   
`appname` - string - The name of the app  
`user` - string - The UID of the user who has the preference key-value for the app.

##### user_preference_set
`key` - string - The key  
`value` - string - The value associated with the key   
`appname` - string - The name of the app  
`user` - string - The UID of the user who has the preference key-value for the app.

##### remove_user_preference_key
`key` - string - The key  
`appname` - string - The name of the app   
`user` - string - The UID of the user whose preference key is deleted for the app.

##### remove_preferences_of_user
`user` - string - The UID of the user, whose all user preferences are deleted.

##### delete_all_user_preference_of_app
`appname` - string - The name of the app whose all user preferences are deleted.

### Impersonate

##### impersonated

`user` - string - The current user who did an impersonate action.  
`targetUser` - string - The user who is being impersonated.

##### impersonate_logout

`user` - string - The user who performed impersonate action.  
`targetUser` - string - The user who was being impersoanted.

### SMB ACL

##### before_set_acl
`user` - string - The user who is trying to set the ACL  
`ocPath` - string - The owncloud instance path  
`smbPath` - string - The SMB path  
`descriptor` - array - The descriptor array. It contains to following keys:
-  `revision` - integer - Always 1
-  `owner` - string - The SMB owner
-  `group` - string - The SMB group
-  `acl` - array - A list of ACEs. The list could be empty. Each ACE contains
    - `trustee` - string - The SMB user affected by this ACE
    - `mode` - string - "allowed" or "denied"
    - `flags` - string - Inheritance flags
    - `mask` - string - Permission mask
    - `flagsAsInt` - integer - The inheritance flags as integer value
    - `maskAsInt` - integer - The permission mask as integer value

##### after_set_acl
`user` - string - The user who is trying to set the ACL  
`ocPath` - string - The owncloud instance path  
`smbPath` - string - The SMB path  
`descriptor` - array - The descriptor array. It contains to following keys:
-  `revision` - integer - Always 1
-  `owner` - string - The SMB owner
-  `group` - string - The SMB group
-  `acl` - array - A list of ACEs. The list could be empty. Each ACE contains
    - `trustee` - string - The SMB user affected by this ACE
    - `mode` - string - "allowed" or "denied"
    - `flags` - string - Inheritance flags
    - `mask` - string - Permission mask
    - `flagsAsInt` - integer - The inheritance flags as integer value
    - `maskAsInt` - integer - The permission mask as integer value

`oldDescriptor` - array|false - The previous descriptor array or false if the previous descriptor couldn't be fetched. The previous descriptor will have the same keys.

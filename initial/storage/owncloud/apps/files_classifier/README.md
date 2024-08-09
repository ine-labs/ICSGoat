File Classifier
===============
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_classifier&metric=alert_status&token=e98824fbb75a21d49e8d0f634ad9d54053f5634c)](https://sonarcloud.io/dashboard?id=owncloud_files_classifier)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_classifier&metric=security_rating&token=e98824fbb75a21d49e8d0f634ad9d54053f5634c)](https://sonarcloud.io/dashboard?id=owncloud_files_classifier)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_files_classifier&metric=coverage&token=e98824fbb75a21d49e8d0f634ad9d54053f5634c)](https://sonarcloud.io/dashboard?id=owncloud_files_classifier)

This app allows managing system tags based on document classes. It loads the 
custom.xml of MS Office files (docx, dotx, xlsx, xltx, pptx, ppsx and potx) and 
checks the value of a document class identified by a configurable xpath rule.
In addition, it allows logging the document id that is also identified by an 
xpath rule.

Configuration
-------------

For each tag that you want to assign based on a document class go to "Admin
settings", "Classification Tags" and:
1. add a new tag with the desired name
2. add an xpath rule for the document class, eg:
   ```xpath
        //property[translate(@name, 'ABCDEFGHJIKLMNOPQRSTUVWXYZ', 'abcdefghjiklmnopqrstuvwxyz')='klassifizierungs-id']/vt:lpwstr
   ```
   > Note: `translate(@name, 'ABCDEFGHJIKLMNOPQRSTUVWXYZ', 'abcdefghjiklmnopqrstuvwxyz')` lower-cases the value
3. add a value for the document class, eg:
   ```$xslt
        1234
   ```

4. optionally add an xpath for the document id, eg:
   ```xpath
        //property[translate(@name, 'ABCDEFGHJIKLMNOPQRSTUVWXYZ', 'abcdefghjiklmnopqrstuvwxyz')='dokumenten-id']/vt:lpwstr
   ```

Logging
-------
In the log you will be able to see what is going on, eg:
```
WARN  checking classified file 'Dok_Internal.docx' with document id JZOIFHQT02VMHL1B9C2TUFYKYK
DEBUG admin uploaded a classified file 'Dok_Internal.docx' with document class 1030
DEBUG assigning tag 'Internal' to 'Dok_Internal.docx'
```

Todo
----

- [x] remove dead code
- [x] policies based on tags
  - PUBLIC: public link sharing with indefinite link validity
    - sharing hook? if the default is indefinite link validity not necessary
    - default is 30 days, but PUBLIC is for marketing stuff -> exemption from the policy
  - INTERNAL: standard link validity dependent on password
    - sharing hook
    - dependent how? no pw -> normal expiry, with pw twice as long or indefinite?
    - nothing to do?
      - do not use link expiry option of password policy app,
      - [x] allow adding link expiry per tag, similar to the one from password policy app?
      - [x] in password policy app set max to 0 (infinite) when no password is set
            only add single input field for 'max days when no pw is set'
      - [x] implement verifyExpirationDate hook
  - CONFIDENTIAL: sharing via link shall not be possible for such files, or folders, that contain such files
    - sharing hook
    - requires checking the tags of all parents
    - [x] 'disallow link sharing' option  
    - [x] implement pre_shared hook
  - STRICTLY CONFIDENTIAL: the file will be deleted and the user shall be informed, that such classified files are not allowed to be uploaded.
    - [x] check what is possible with firewall and workflow
      - firewall: checks happen in a storage wrapper before the file has been tagged
      - workflow: retention works as a daily background job, auto tagging only tags ...
    - [x] can be configured as 'disallow upload' option in the tag settings
    - [x] implement check during tagging
  -> hard code policies in this app and allow config via admin settings?
    - better place for the actions?
    - exemption from normal expiry date needs some thought
      - delete on tag can be done with the EventDispatcher \OCP\SystemTag\MapperEvent::EVENT_ASSIGN event
      - link expiry based on tag needs to go into sharing
        - admin sharing settings can change default / max expiry
        - the password policy app adds another vector to this, uses a post share hook to enforce expiry date

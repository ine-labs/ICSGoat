# Metrics API for ownCloud Server
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_metrics&metric=alert_status&token=33e765a6b1d44c29d5e92b2b70dfb11359158dfd)](https://sonarcloud.io/dashboard?id=owncloud_metrics)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_metrics&metric=security_rating&token=33e765a6b1d44c29d5e92b2b70dfb11359158dfd)](https://sonarcloud.io/dashboard?id=owncloud_metrics)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_metrics&metric=coverage&token=33e765a6b1d44c29d5e92b2b70dfb11359158dfd)](https://sonarcloud.io/dashboard?id=owncloud_metrics)

## Overview

This extension adds 
- an API endpoint which allows querying snapshot values of the system as well as per-user metrics
- an API endpoint for downloading the metrics values in the CSV format
- a dashboard that displays the snapshot values (accessible by ownCloud administrators)

The following data is available:

**System data**
- Date/Time stamp - Server time of the request
- Storage
  - Used storage (this also includes storage for avatars and thumbnails)
  - Free storage
  - Total storage (used + free)
  - Number of files
- Number of users
  - registered (total number of known users)
  - active (number of users with `lastLogin` less than two weeks ago)
  - concurrent (number of users with at least one active session)
- Shares
  - Number of user shares
  - Number of group shares
  - Number of guest shares
  - Number of link shares
  - Number of federated shares

**Per-user data**
- User id
- Display name
- User backend
- Last login
- Active sessions
- Quota
  - Quota limit
  - Quota usage
- Number of files
- Shares
  - Number of user shares
  - Number of group shares
  - Number of guest shares
  - Number of link shares
  - Number of federated shares

## Usage
### Authorization
To get set up, you have to set a secret for authorization. This token is stored in config.php as `metrics_shared_secret` and needs to be sent with each request.

See the following example on how to set it:

```
./occ config:system:set "metrics_shared_secret" --value 1234
```

### Dashboard user interface
The dashboard is enabled by default.

See the following example on how to disable it:

```
./occ config:app:set metrics disable_dashboard --value=yes
```

### Endpoint and parameters
To query for the metrics you use the following endpoint:

```
https://<your owncloud>/ocs/v1.php/apps/metrics/api/v1/metrics
``` 
- Parameters
  - `users=true`
  - `shares=true`
  - `quota=true`
  - `userData=true`
- Header `"OC-MetricsApiKey: 1234"` (provide the one set during the step in Authorization)
  
Except the header all other parameters are optional. You can split the query into parts by setting the respective parameters to `false`.

### Example output
See the `curl` example to request the complete output listed below:
```
curl https://<your owncloud>/ocs/v1.php/apps/metrics/api/v1/metrics\?users\=true\&files\=true\&shares\=true\&quota\=true\&userData\=true\&format\=json -H "OC-MetricsApiKey: 1234"
```

```
{
   "ocs" : {
      "data" : {
         "files" : {
            "totalFilesCount" : 3
         },
         "users" : {
            "activeUsersCount" : 4,
            "concurrentUsersCount" : 2,
            "totalCount" : 4
         },
         "userData" : {
            "hari.sujith@gmail.com" : {
               "shares" : {
                  "userShareCount" : 0,
                  "guestShareCount" : 0,
                  "groupShareCount" : 0,
                  "linkShareCount" : 0
               },
               "activeSessions" : [],
               "quota" : {
                  "limit" : 68058894336,
                  "usage" : 0
               },
               "files" : {
                  "totalFiles" : 0
               },
               "displayName" : "hari.sujith@gmail.com"
            },
            "admin" : {
               "quota" : {
                  "usage" : 163,
                  "limit" : 68058894336
               },
               "files" : {
                  "totalFiles" : 1
               },
               "displayName" : "admin",
               "shares" : {
                  "userShareCount" : 1,
                  "guestShareCount" : 1,
                  "groupShareCount" : 1,
                  "linkShareCount" : 0
               },
               "activeSessions" : [
                  {
                     "agentName" : "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36"
                  }
               ]
            },
            "user2" : {
               "displayName" : "user2",
               "files" : {
                  "totalFiles" : 1
               },
               "quota" : {
                  "limit" : 3221225309,
                  "usage" : 163
               },
               "activeSessions" : [],
               "shares" : {
                  "groupShareCount" : 0,
                  "linkShareCount" : 0,
                  "guestShareCount" : 0,
                  "userShareCount" : 0
               }
            },
            "user1" : {
               "activeSessions" : [
                  {
                     "agentName" : "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:70.0) Gecko/20100101 Firefox/70.0"
                  }
               ],
               "shares" : {
                  "linkShareCount" : 1,
                  "groupShareCount" : 0,
                  "guestShareCount" : 0,
                  "userShareCount" : 0
               },
               "displayName" : "user1",
               "files" : {
                  "totalFiles" : 1
               },
               "quota" : {
                  "limit" : 5368708957,
                  "usage" : 163
               }
            }
         },
         "shares" : {
            "groupShareCount" : 1,
            "linkShareCount" : 1,
            "userShareCount" : 1,
            "guestShareCount" : 1
         },
         "timeStamp" : 1574346606
      },
      "meta" : {
         "statuscode" : 100,
         "totalitems" : "",
         "status" : "ok",
         "itemsperpage" : "",
         "message" : "OK"
      }
   }
}

```

## CSV user metrics download endpoint
Downloading the current user metrics as CSV file is possible through the web ui. However, if you want to set up 
a cronjob for downloading the metrics without admin permissions, there also is a public endpoint that requires
the configured token instead of admin privileges. The request looks like this:

`curl -H "Content-Type: application/csv" -H "OC-MetricsApiKey: <your-metrics-secret>" -X GET https://<your owncloud>/index.php/apps/metrics/download-api/users > /path/to/download/storage/user-metrics.csv`

Please replace `<your-metrics-secret>` with your respective system config value and `<your owncloud>` with the url of your ownCloud instance.


## CSV system metrics download endpoint
Downloading the current system metrics as CSV file is possible through the web ui. However, if you want to set up
a cronjob for downloading the metrics without admin permissions, there also is a public endpoint that requires
the configured token instead of admin privileges. The request looks like this:

`curl -H "Content-Type: application/csv" -H "OC-MetricsApiKey: <your-metrics-secret>" -X GET https://<your owncloud>/index.php/apps/metrics/download-api/system > /path/to/download/storage/system-metrics.csv`

Please replace `<your-metrics-secret>` with your respective system config value and `<your owncloud>` with the url of your ownCloud instance.


## Remarks
- ownCloud Clients authorized with OAuth2 always have an active session, unless their authorization is invalidated.

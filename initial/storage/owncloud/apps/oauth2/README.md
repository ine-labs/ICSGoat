# 🔐 OAuth 2.0
[![Build Status](https://drone.owncloud.com/api/badges/owncloud/oauth2/status.svg?branch=master)](https://drone.owncloud.com/owncloud/oauth2)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_oauth2&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_oauth2)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_oauth2&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_oauth2)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_oauth2&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_oauth2)

This app implements the [OAuth 2.0 Authorization Code Flow](https://tools.ietf.org/html/rfc6749#section-4.1).

## Installing the app
Place the content of this repository in **owncloud/apps/oauth2**.

## Using the app

### Endpoints
* Authorization URL: `/index.php/apps/oauth2/authorize`
* Access Token URL: `/index.php/apps/oauth2/api/v1/token`

### Protocol Flow
1. [Client registration](https://tools.ietf.org/html/rfc6749#section-2): First the clients have to be registered in the admin settings: `/index.php/settings/admin?sectionid=additional#oauth2`. You need to specify a name for the client (the name is unrelated to the OAuth 2.0 protocol and is just used to recognize it later) and the redirection URI. A client identifier and client secret is being generated when adding a new client. They both consist of 64 characters.

2. [Authorization Request](https://tools.ietf.org/html/rfc6749#section-4.1.1): For every registered client an Authorization Request can be made. The client redirects the resource owner to the [Authorization URL](#endpoints) and requests authorization. The following URL parameters have to be specified: 
    1. `response_type` (required): needs to be `code` because at this time only the Authorization Code Flow is implemented.
    2. `client_id` (required): the client identifier obtained when registering the client.
    3. `redirect_uri` (required): the redirection URI specified when registering the client.
    4. `state` (optional): can be set by the client "to maintain state between the request and callback" ([RFC 6749](https://tools.ietf.org/html/rfc6749#section-4.1.1)).
    5. `user` (optional): can be set to indicate the username of the resource owner 

3. [Authorization Response](https://tools.ietf.org/html/rfc6749#section-4.1.2): After the resource owner's authorization the app redirects to the `redirect_uri` specified in the Authorization Request and adds the Authorization Code as URL parameter `code`. An Authorization Code is valid for 10 minutes.

4. [Access Token Request](https://tools.ietf.org/html/rfc6749#section-4.1.3): With the Authorization Code the client can request an Access Token using the [Access Token URL](#endpoints). [Client Authentication](https://tools.ietf.org/html/rfc6749#section-2.3) is done using Basic Auth with the client identifier as username and the client secret as password. The following URL parameters have to be specified:
    1. `grant_type `: Either `authorization_code` or `refresh_token`.
    2. `code` and `redirect_uri` (if the grant type `authorization_code` is used).
    3. `refresh_token` (if the grant type `refresh_token` is used).

5. [Access Token Response](https://tools.ietf.org/html/rfc6749#section-4.1.4): The app responses to a valid Access Token Request with an JSON response like the following. An Access Token is valid for 1 hour and can be refreshed with a Refresh Token.

```json
{
    "access_token" : "1vtnuo1NkIsbndAjVnhl7y0wJha59JyaAiFIVQDvcBY2uvKmj5EPBEhss0pauzdQ",
    "token_type" : "Bearer",
    "expires_in" : 3600,
    "refresh_token" : "7y0wJuvKmj5E1vjVnhlPBEhha59JyaAiFIVQDvcBY2ss0pauzdQtnuo1NkIsbndA",
    "user_id" : "admin",
    "message_url" : "https://www.example.org/owncloud/index.php/apps/oauth2/authorization-successful"
}
```

## Limitations
Since no user passwords are handled by the app at all only master key encryption is working (similiar to the Shibboleth app).

## Possible improvements
- [ ] Add option for using different [scopes](https://tools.ietf.org/html/rfc6749#section-3.3).

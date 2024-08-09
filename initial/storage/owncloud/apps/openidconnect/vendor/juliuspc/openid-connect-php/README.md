PHP OpenID Connect Basic Client
========================
A simple library that allows an application to authenticate a user through the basic OpenID Connect flow.
This library hopes to encourage OpenID Connect use by making it simple enough for a developer with little knowledge of
the OpenID Connect protocol to setup authentication.

This library is a fork of [jumbojett/OpenID-Connect-PHP](https://github.com/jumbojett/OpenID-Connect-PHP), which seems to be discontinued. For progress being made on fixing bugs of the original library [see this wiki page](https://github.com/JuliusPC/OpenID-Connect-PHP/wiki/Progress-on-fixing-upstream-issues).

# Supported Specifications #

- [OpenID Connect Core 1.0](https://openid.net/specs/openid-connect-core-1_0.html)
- [OpenID Connect Discovery 1.0](https://openid.net/specs/openid-connect-discovery-1_0.html) ([finding the issuer is missing](https://github.com/jumbojett/OpenID-Connect-PHP/issues/2))
- [OpenID Connect RP-Initiated Logout 1.0 - draft 01](https://openid.net/specs/openid-connect-rpinitiated-1_0.html)
- [OpenID Connect Dynamic Client Registration 1.0](https://openid.net/specs/openid-connect-registration-1_0.html)
- [RFC 6749: The OAuth 2.0 Authorization Framework](https://tools.ietf.org/html/rfc6749)
- [RFC 7009: OAuth 2.0 Token Revocation](https://tools.ietf.org/html/rfc7009)
- [RFC 7636: Proof Key for Code Exchange by OAuth Public Clients](https://tools.ietf.org/html/rfc7636)
- [RFC 7662: OAuth 2.0 Token Introspection](https://tools.ietf.org/html/rfc7662)
- [RFC 8693: OAuth 2.0 Token Exchange](https://tools.ietf.org/html/rfc8693)
- [Draft: OAuth 2.0 Authorization Server Issuer Identifier in Authorization Response](https://tools.ietf.org/html/draft-ietf-oauth-iss-auth-resp-00)

# Requirements #
 1. PHP 5.4 or greater
 2. CURL extension
 3. JSON extension

## Install ##
 1. Install library using composer
```
composer require juliuspc/openid-connect-php
```
 2. Include composer autoloader
```php
require __DIR__ . '/vendor/autoload.php';
```

## Example 1: Basic Client ##

This example uses the Authorization Code flow and will also use PKCE if the OpenID Provider announces it in his Discovery document. If you are not sure, which flow you should choose: This one is the way to go. It is the most secure and versatile.

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecretHere');
$oidc->authenticate();
$name = $oidc->requestUserInfo('given_name');

```

[See OpenID Connect spec for available user attributes][1]

## Example 2: Dynamic Registration ##

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient("https://id.example.com");

$oidc->register();
$client_id = $oidc->getClientID();
$client_secret = $oidc->getClientSecret();

// Be sure to add logic to store the client id and client secret
```

## Example 3: Network and Security ##
```php
// Configure a proxy
$oidc->setHttpProxy("http://my.proxy.example.net:80/");

// Configure a cert
$oidc->setCertPath("/path/to/my.cert");
```

## Example 4: Request Client Credentials Token ##

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecretHere');
$oidc->providerConfigParam(array('token_endpoint'=>'https://id.example.com/connect/token'));
$oidc->addScope('my_scope');

// this assumes success (to validate check if the access_token property is there and a valid JWT) :
$clientCredentialsToken = $oidc->requestClientCredentialsToken()->access_token;

```

## Example 5: Request access token via ROPCG ##

Note: The Resource Owner Password Credentials Grant (ROPCG) will be obsoleted by OAuth 2.1.

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecretHere');
$oidc->providerConfigParam(array('token_endpoint'=>'https://id.example.com/connect/token'));
$oidc->addScope('my_scope');

//Add username and password
$oidc->addAuthParam(array('username'=>'<Username>'));
$oidc->addAuthParam(array('password'=>'<Password>'));

//Perform the auth and return the token (to validate check if the access_token property is there and a valid JWT) :
$token = $oidc->requestResourceOwnerToken(TRUE)->access_token;

```

## Example 6: Basic client for implicit flow (see https://openid.net/specs/openid-connect-core-1_0.html#ImplicitFlowAuth) ##

The implicit flow should be considered a legacy flow and not used if authorization code grant can be used. Due to its disadvantages and poor security, he implicit flow will be obsoleted with the upcoming OAuth 2.1 standard. See Example 1 for alternatives.

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecretHere');
$oidc->setResponseTypes(array('id_token'));
$oidc->addScope(array('openid'));
$oidc->setAllowImplicitFlow(true);
$oidc->addAuthParam(array('response_mode' => 'form_post'));
$oidc->authenticate();
$sub = $oidc->getVerifiedClaims('sub');

```

## Example 7: Introspection of an access token (see https://tools.ietf.org/html/rfc7662) ##

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecretHere');
$data = $oidc->introspectToken('an.access-token.as.given');
if (!$data->active) {
    // the token is no longer usable
}

```

## Example 8: PKCE Client ##

PKCE is already configured used in most szenarios in Example 1. This example shows two special things:

1. You may omit the client secret, if your OpenID Provider allows you to do so and if it is really needed for your use case. Applications written in PHP are typically confidential OAuth clients and thus don’t leak a client secret.
2. Explicitly setting the Code Challenge Method via `setCodeChallengeMethod()`. This enables PKCE in cas your OpenID Provider doesn’t announce support for it in the discovery document, but supports it anyway.

```php
use JuliusPC\OpenIDConnectClient;

$oidc = new OpenIDConnectClient('https://id.example.com',
                                'ClientIDHere',
                                'ClientSecret'); // you may obmit the client secret (set to null)
// for some reason we want to set S256 explicitly as Code Challenge Method
// maybe your OP doesn’t announce support for PKCE in its discovery document
$oidc->setCodeChallengeMethod('S256');
$oidc->authenticate();
$name = $oidc->requestUserInfo('given_name');

```


## Development Environments ##
In some cases you may need to disable SSL security on on your development systems.
Note: This is not recommended on production systems.

```php
$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);
```

Also, your local system might not support HTTPS, so you might disable uprading to it:

```php
$oidc->httpUpgradeInsecureRequests(false);
```

### Todo ###
- Dynamic registration does not support registration auth tokens and endpoints
- improving tests and test coverage of this library

  [1]: https://openid.net/specs/openid-connect-basic-1_0-15.html#id_res

## Contributing ###
 - All pull requests, once merged, should be added to the CHANGELOG.md file.

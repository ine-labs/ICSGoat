# graphapi
:link: Graph API for ownCloud 10

Graph API is a flexible way to access all kinds of data.

## Implemented Scenarios

### Access Users via an IDP
oc10 users can be accessed via the graphapi for authentication. There is an oCIS service "glauth" which translates graph to ldap, so that an IDP can connect to the userbase.

Flow: oc10 users -> graphapi -> glauth ldap -> IDP

See https://owncloud.github.io/ocis/bridge/ for documentation on this.

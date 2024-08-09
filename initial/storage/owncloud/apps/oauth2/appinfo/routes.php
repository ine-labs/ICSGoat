<?php
/**
 * @author Project Seminar "sciebo@Learnweb" of the University of Muenster
 * @copyright Copyright (c) 2017, University of Muenster
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

return [
	'routes' => [
		# Routes for the authorize view
		['name' => 'page#authorize', 'url' => '/authorize', 'verb' => 'GET'],
		['name' => 'page#generate_authorization_code', 'url' => '/authorize', 'verb' => 'POST'],
		['name' => 'page#logout', 'url' => '/logout', 'verb' => 'GET'],
		# API endpoint for requesting a token
		['name' => 'o_auth_api#generate_token', 'url' => '/api/v1/token', 'verb' => 'POST'],
		['name' => 'o_auth_api#preflighted_cors', 'url' => '/api/v1/{path}', 'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']],
		# OpenID connect
		['name' => 'OpenIdConnect#userinfo', 'url' => '/api/v1/userinfo', 'verb' => 'GET'],
		# Routes for authorization successful message
		['name' => 'page#authorizationSuccessful', 'url' => '/authorization-successful', 'verb' => 'GET'],
		# Routes for admin settings
		['name' => 'settings#addClient', 'url' => '/clients', 'verb' => 'POST'],
		['name' => 'settings#deleteClient', 'url' => '/clients/{id}/delete', 'verb' => 'POST'],
		# Routes for personal settings
		['name' => 'settings#revokeAuthorization', 'url' => '/clients/{id}/revoke', 'verb' => 'POST']
	]
];

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

/** @var \OCA\OAuth2\Db\Client $client */
script('oauth2', 'settings-admin');
style('oauth2', 'main');
style('oauth2', 'settings-admin');

if (!empty($_['clients'])) {
	$noClientsExtraClass = 'hidden';
	$clientTableExtraClass = '';
} else {
	$noClientsExtraClass = '';
	$clientTableExtraClass = 'hidden';
}
?>

<div class="section" id="oauth2">
	<h2 class="app-name"><?php p($l->t('OAuth 2.0')); ?></h2>

	<h3><?php p($l->t('Registered clients')); ?></h3>
	<p class="no-clients-message <?php p($noClientsExtraClass) ?>">
		<?php p($l->t('No clients registered.')); ?>
	</p>
	<table class="grid <?php p($clientTableExtraClass) ?>">
		<thead>
			<tr>
				<th id="headerName" scope="col"><?php p($l->t('Name')); ?></th>
				<th id="headerRedirectUri" scope="col"><?php p($l->t('Redirection URI')); ?></th>
				<th id="headerClientIdentifier" scope="col"><?php p($l->t('Client Identifier')); ?></th>
				<th id="headerSecret" scope="col"><?php p($l->t('Secret')); ?></th>
				<th id="headerSubdomains" scope="col"><?php p($l->t('Subdomains allowed')); ?></th>
				<th id="headerTrusted" scope="col"><?php p($l->t('Trusted client')); ?></th>
				<th id="headerRemove">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php
			foreach ($_['clients'] as $client):
				include('client.part.php');
			endforeach;
		?>
		</tbody>
	</table>

	<h3><?php p($l->t('Add client')); ?></h3>
	<form id="oauth2-new-client">
		<input name="name" type="text" placeholder="<?php p($l->t('Name')); ?>">
		<input name="redirect_uri" type="text" placeholder="<?php p($l->t('Redirection URI')); ?>">
		<input name="allow_subdomains" id="allow_subdomains" type="checkbox" class="checkbox" value="1"/>
		<label for="allow_subdomains"><?php p($l->t('Allow subdomains'));?></label>
		<input name="trusted" id="trusted" type="checkbox" class="checkbox" value="1"/>
		<label for="trusted"><?php p($l->t('Trusted client'));?></label>
	</form>
	<button id="oauth2_submit" type="button" class="button"><?php p($l->t('Add')); ?></button>
	<span id="oauth2_save_msg"></span>
</div>

<?php
/**
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 *
 * @copyright Copyright (c) 2021, ownCloud GmbH
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
 *
 */

namespace OCA\windows_network_drive\lib\fs_backend;

use OCP\IL10N;
use OCP\Files\External\DefinitionParameter;
use OCP\Files\External\Auth\AuthMechanism;
use OCP\Files\External\Backend\Backend;
use OCP\Files\External\IStoragesBackendService;

class WND2 extends Backend {
	public function __construct(IL10N $l) {
		$this
			->setIdentifier('windows_network_drive2')
			->setStorageClass(\OCA\windows_network_drive\lib\WND2::class)
			->setText($l->t('Windows Network Drive (collaborative)'))
			->addParameters([
				(new DefinitionParameter('host', $l->t('Host'))),
				(new DefinitionParameter('share', $l->t('Share'))),
				(new DefinitionParameter('root', $l->t('Remote subfolder')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('domain', $l->t('Domain')))
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
				(new DefinitionParameter('service-account', $l->t('Service Account'))),
				(new DefinitionParameter('service-account-password', $l->t('Service Account Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD),
			])
			->addAuthScheme(AuthMechanism::SCHEME_PASSWORD)
			->setVisibility(IStoragesBackendService::VISIBILITY_ADMIN)
			->setAllowedVisibility(IStoragesBackendService::VISIBILITY_ADMIN)
		;
	}
}

<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

namespace OCA\CustomGroups\Dav;

use OCA\CustomGroups\CustomGroupsDatabaseHandler;
use OCP\IUserSession;
use OCA\CustomGroups\Search;
use OCA\CustomGroups\Dav\ReportRequest;
use OCA\CustomGroups\Dav\RootCollection;
use OCA\CustomGroups\Dav\MembershipNode;
use OCA\CustomGroups\Dav\GroupsCollection;
use Sabre\DAV\ServerPlugin;
use OCA\CustomGroups\Dav\CustomGroupMemberNode;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Xml\Element\Response;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\PropFind;

/**
 * Sabre plugin to handle custom groups
 */
class CustomGroupsPlugin extends ServerPlugin {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';

	public const REPORT_NAME = '{http://owncloud.org/ns}search-query';

	/**
	 * Sabre server
	 *
	 * @var \Sabre\DAV\Server $server
	 */
	private $server;

	/**
	 * User session
	 *
	 * @var \OCP\IUserSession
	 */
	protected $userSession;

	/**
	 * Custom groups plugin
	 *
	 * @param IUserSession $userSession user session
	 */
	public function __construct(IUserSession $userSession) {
		$this->userSession = $userSession;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;

		$uri = $this->server->getRequestUri();
		$uri = '/' . \trim($uri) . '/';
		if (\strpos($uri, '/customgroups/') === false) {
			return;
		}

		if ($this->userSession === null || $this->userSession->getUser() === null) {
			return;
		}

		$this->server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		$ns = '{' . self::NS_OWNCLOUD . '}';
		$this->server->resourceTypeMapping[MembershipNode::class] = $ns . 'customgroups-membership';
		$this->server->resourceTypeMapping[GroupsCollection::class] = $ns . 'customgroups-groups';
		$this->server->resourceTypeMapping[GroupMembershipCollection::class] = $ns . 'customgroups-group';
		$this->server->protectedProperties[] = $ns . 'user-id';
		$this->server->protectedProperties[] = $ns . 'group-uri';

		$this->server->xml->elementMap[self::REPORT_NAME] = ReportRequest::class;

		$this->server->on('report', [$this, 'onReport']);
	}

	/**
	 * Returns a list of reports this plugin supports.
	 *
	 * This will be used in the {DAV:}supported-report-set property.
	 *
	 * @param string $uri URI
	 * @return array
	 */
	public function getSupportedReportSet($uri) {
		$node = $this->server->tree->getNodeForPath($uri);
		if ($this->isSupportedNode($node)) {
			return [self::REPORT_NAME];
		}
		return [];
	}

	private function isSupportedNode($node) {
		return (
			$node instanceof GroupsCollection
			|| $node instanceof GroupMembershipCollection
		);
	}

	/**
	 * REPORT operations to look for comments
	 *
	 * @param string $reportName report name
	 * @param ReportRequest $report report data
	 * @param string $uri URI
	 * @return bool true if processed
	 */
	public function onReport($reportName, $report, $uri) {
		$node = $this->server->tree->getNodeForPath($uri);
		if (!$this->isSupportedNode($node) || $reportName !== self::REPORT_NAME) {
			return;
		}

		$results = $node->search($report->getSearch());

		$responses = [];
		$nodeProps = [];
		foreach ($results as $result) {
			$nodePath = $this->server->getRequestUri() . '/' . $result->getName();
			$propFind = new PropFind($nodePath, $report->getProperties());
			$this->server->getPropertiesByNode($propFind, $result);

			$resultSet = $propFind->getResultForMultiStatus();
			$resultSet['href'] = $nodePath;

			$nodeProps[] = $resultSet;
		}

		$xml = $this->server->generateMultiStatus($nodeProps);

		$this->server->httpResponse->setStatus(207);
		$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$this->server->httpResponse->setBody($xml);

		return false;
	}
}

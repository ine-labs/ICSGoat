<?php

namespace OCA\CustomGroups\Dav;

use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;

class CSVExportPlugin extends DAV\ServerPlugin {
	/**
	 * Reference to Server class.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	public function initialize(DAV\Server $server) {
		$this->server = $server;
		$server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * Intercepts GET requests on calendar urls ending with ?export.
	 *
	 * @throws BadRequest
	 * @throws DAV\Exception\NotFound
	 * @throws VObject\InvalidDataException
	 *
	 * @return bool
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response) {
		$queryParams = $request->getQueryParameters();
		if (!\array_key_exists('export', $queryParams)) {
			return;
		}

		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		if (!$node instanceof GroupMembershipCollection) {
			return;
		}

		// Marking the transactionType, for logging purposes.
		$this->server->transactionType = 'get-customgroups-export';

		$csvContents = $this->buildCSV($node);

		$filename = \preg_replace(
			'/[^a-zA-Z0-9-_ ]/um',
			'',
			$node->getName()
		);

		$filename .= '-'.\date('Y-m-d').'.csv';

		$response->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
		$response->setHeader('Content-Type', 'text/csv');

		$response->setStatus(200);
		$response->setBody($csvContents);

		// Returning false to break the event chain
		return false;
	}

	private function buildCSV(GroupMembershipCollection $node) : string {
		$f = \fopen('php://memory', 'r+');
		foreach ($node->getChildren() as $userInGroup) {
			/** @var MembershipNode $userInGroup */
			if (\fputcsv($f, [
					$userInGroup->getUserId(),
					$userInGroup->getRole(),
				]) === false) {
				return false;
			}
		}
		\rewind($f);
		return \stream_get_contents($f);
	}
}

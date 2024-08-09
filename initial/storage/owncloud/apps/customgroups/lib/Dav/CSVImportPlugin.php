<?php

namespace OCA\CustomGroups\Dav;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class CSVImportPlugin extends DAV\ServerPlugin {
	/**
	 * Reference to Server class.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	public function initialize(DAV\Server $server) {
		$this->server = $server;
		$server->on('method:POST', [$this, 'httpPost'], 90);
	}

	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		if (!$node instanceof GroupMembershipCollection) {
			return null;
		}

		$result = [];

		$data = $request->getBodyAsString();
		$data = $this->csv_to_array($data);
		foreach ($data as $user => $role) {
			if ($node->childExists($user)) {
				$result[$user] = 'already-member';
				continue;
			}

			try {
				$node->createFile($user);
				$child = $node->getChild($user);
				if ($child->updateRole($role) !== true) {
					$result[$user] = "apply-role-failed";
				} else {
					$result[$user] = "success";
				}
			} catch (\Exception $ex) {
				$result[$user] = $ex->getMessage();
			}
		}

		// created
		$response->setStatus(201);
		$response->setHeader('Content-Type', 'application/json');
		$response->setBody(\json_encode($result));
		return false;
	}

	public function csv_to_array($data, $delimiter = ','): array {
		$stream = \fopen('php://memory', 'rb+');
		\fwrite($stream, $data);
		\rewind($stream);

		$header = null;
		$data = [];
		while (($row = \fgetcsv($stream, 1000, $delimiter)) !== false) {
			# TODO: add some verification here
			$data[\trim($row[0])] = \trim($row[1]);
		}
		\fclose($stream);

		return $data;
	}
}

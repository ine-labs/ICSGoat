<?php

namespace OCA\windows_network_drive\AppInfo;

$app = new Application();
$app->registerRoutes(
	$this,
	[
		'routes' => [
			[
				'name' => 'GlobalCredentials#save',
				'url' => '/globalcredentials',
				'verb' => 'POST',
			]
		]
	]
);

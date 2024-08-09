<?php

namespace OCA\Activity;

use OCP\Settings\ISettings;

class PersonalPanel implements ISettings {

	/** @var AppInfo\Application  */
	protected $app;

	public function __construct(\OCA\Activity\AppInfo\Application $app) {
		$this->app = $app;
	}

	public function getPanel() {
		return $this->app->getContainer()->query('SettingsController')->displayPanel();
	}
	public function getPriority() {
		return 10;
	}
	public function getSectionID() {
		return 'general';
	}
}

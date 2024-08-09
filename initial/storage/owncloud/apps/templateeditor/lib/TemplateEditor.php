<?php
/**
 * @author Philipp Schaffrath <github@philipp.schaffrath.email>
 * @author Viktar Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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
namespace OCA\TemplateEditor;

use OC\Helper\EnvironmentHelper;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\Theme\IThemeService;

class TemplateEditor {

	/**
	 * @var IThemeService
	 */
	private $themeService;

	/**
	 * @var IAppManager
	 */
	private $appManager;

	/**
	 * @var EnvironmentHelper
	 */
	private $environmentHelper;

	/**
	 * @var IL10N
	 */
	private $l10n;

	/**
	 * TemplateEditor constructor.
	 *
	 * @param IThemeService $themeService
	 * @param IAppManager $appManager
	 * @param EnvironmentHelper $environmentHelper
	 * @param IL10N $l10n
	 */
	public function __construct(IThemeService $themeService,
								IAppManager $appManager,
								EnvironmentHelper $environmentHelper,
								IL10N $l10n
	) {
		$this->themeService = $themeService;
		$this->appManager = $appManager;
		$this->environmentHelper = $environmentHelper;
		$this->l10n = $l10n;
	}

	/**
	 * @return string[]
	 */
	public function getAllThemeNames() {
		$themes = $this->themeService->getAllThemes();
		
		$themeNames = [];
		foreach ($themes as $theme) {
			$themeName = $theme->getName();
			if (\is_array($this->appManager->getAppInfo($themeName))
				&& !$this->appManager->isInstalled($themeName)
			) {
				continue;
			}
			$themeNames[] = $themeName;
		}
		return $themeNames;
	}

	/**
	 * @return array
	 */
	public function getEditableTemplates() {
		$templates = [
			'core/templates/mail.php' =>
				$this->l10n->t('Sharing email - public link shares (HTML)'),
			'core/templates/altmail.php' =>
				$this->l10n->t('Sharing email - public link shares (plain text fallback)'),
			'core/templates/internalmail.php' =>
				$this->l10n->t('Sharing email (HTML)'),
			'core/templates/internalaltmail.php' =>
				$this->l10n->t('Sharing email (plain text fallback)'),
			'core/templates/lostpassword/email.php' =>
				$this->l10n->t('Lost password mail'),
			'settings/templates/email.new_user.php' =>
				$this->l10n->t('New user email (HTML)'),
			'settings/templates/email.new_user_plain_text.php' =>
				$this->l10n->t('New user email (plain text fallback)'),
		];

		$additionalCoreTemplates = $this->getAdditionalCoreTemplates(
			[
				'core/templates/html.mail.footer.php' =>
					$this->l10n->t('Common email footer (HTML)'),
				'core/templates/plain.mail.footer.php' =>
					$this->l10n->t('Common email footer (plain text)'),
			]
		);

		$activityTemplates = $this->getAppTemplates(
			'activity',
			[
				'/templates/html.notification.php' =>
					$this->l10n->t('Activity notification mail (HTML)'),
				'/templates/email.notification.php' =>
					$this->l10n->t('Activity notification mail (plain text)')
			]
		);

		$notificationsTemplates = $this->getAppTemplates(
			'notifications',
			[
				'/templates/mail/htmlmail.php' =>
					$this->l10n->t('Notifications app mail (HTML)'),
				'/templates/mail/plaintextmail.php' =>
					$this->l10n->t('Notifications app mail (plain text)')
			]
		);
		$templates = \array_merge(
			$templates,
			$additionalCoreTemplates,
			$activityTemplates,
			$notificationsTemplates
		);

		return $templates;
	}

	/**
	 * @param string[] $templates
	 * @return string[]
	 */
	public function getAdditionalCoreTemplates($templates) {
		$serverRoot = $this->environmentHelper->getServerRoot();
		$existingTemplates = [];
		foreach ($templates as $templatePath => $templateTitle) {
			if ($this->fileExists($serverRoot . '/' . $templatePath)) {
				$existingTemplates[$templatePath] = $templateTitle;
			}
		}
		return $existingTemplates;
	}

	/**
	 * @param string $appId
	 * @param string[] $templates
	 * @return string[]
	 */
	protected function getAppTemplates($appId, $templates) {
		$appTemplates = [];
		if ($this->appManager->isInstalled($appId)) {
			$appPath = $this->appManager->getAppPath($appId);
			$relativeAppPath = $this->getRelativePath($appPath);
			foreach ($templates as $templatePath => $templateTitle) {
				$fullPath = $appPath . $templatePath;
				if ($this->fileExists($fullPath)) {
					$appTemplates[$relativeAppPath . $templatePath] = $templateTitle;
				}
			}
		}
		return $appTemplates;
	}

	/**
	 * @param string $absolutePath
	 * @return string
	 */
	protected function getRelativePath($absolutePath) {
		$serverRoot = $this->environmentHelper->getServerRoot();
		return \substr($absolutePath, \strlen($serverRoot) + 1);
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	protected function fileExists($path) {
		return \file_exists($path);
	}

	/**
	 * @param string $themeName
	 * @param string $template
	 * @return MailTemplate
	 */
	public function getMailTemplate($themeName, $template) {
		return new MailTemplate(
			$this->themeService->findTheme($themeName),
			$template
		);
	}
}

<?php
/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
Util::addScript($appId, $appId . '-settings');
Util::addStyle($appId, 'main');

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div id="admin-settings" class="section"></div>
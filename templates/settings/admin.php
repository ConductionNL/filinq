<?php
/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
// Shared chunks must load before the entry — see webpack.config.js splitChunks.
Util::addScript($appId, $appId . '-shared-vendor');
Util::addScript($appId, $appId . '-shared-nc-vue');
Util::addScript($appId, $appId . '-settings');
Util::addStyle($appId, 'main');

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div id="admin-settings" class="section" data-version="<?php p($_['version'] ?? ''); ?>"></div>
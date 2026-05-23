<?php
/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
// Shared chunks must load before the entry — settings expects Vue /
// @nextcloud/vue / @conduction/nextcloud-vue / pinia / vue-material-design-icons
// to be resolved by the time its chunkOnLoad callback runs.
// See webpack.config.js `splitChunks.cacheGroups`.
Util::addScript($appId, $appId . '-shared-vendor');
Util::addScript($appId, $appId . '-shared-nc-vue');
Util::addScript($appId, $appId . '-settings');
Util::addStyle($appId, 'main');

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>

<div id="admin-settings" class="section" data-version="<?php p($_['version'] ?? ''); ?>"></div>
<?php

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
// Shared chunks must load before the entry — main expects Vue / @nextcloud/vue
// / @conduction/nextcloud-vue / pinia / vue-material-design-icons to be
// resolved by the time its chunkOnLoad callback runs. See webpack.config.js
// `splitChunks.cacheGroups`.
Util::addScript($appId, $appId . '-shared-vendor');
Util::addScript($appId, $appId . '-shared-nc-vue');
Util::addScript($appId, $appId . '-main');
Util::addStyle($appId, 'main');
?>
<div id="content"></div>



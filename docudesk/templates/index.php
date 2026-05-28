<?php

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
Util::addScript($appId, $appId . '-main');
Util::addStyle($appId, 'main');
?>
<div id="docudesk"></div>



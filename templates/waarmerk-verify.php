<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

use OCP\Util;

$appId = OCA\Filinq\AppInfo\Application::APP_ID;
// Standalone public entry: no app shell, no shared framework chunks.
Util::addScript($appId, $appId . '-waarmerkVerify');
?>
<div id="filinq-waarmerk-verify"></div>

<?php

/**
 * The public signature verification page (signature-verification-portal REQ-DDSVP-001).
 *
 * Rendered for a visitor with no account. The page reads the token from its
 * own address and asks the public verify endpoint for the verdict.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

use OCP\Util;

$appId = OCA\Filinq\AppInfo\Application::APP_ID;
// Shared chunks must load before the entry; see webpack.config.js `splitChunks`.
Util::addScript($appId, $appId . '-shared-vendor');
Util::addScript($appId, $appId . '-shared-nc-vue');
Util::addScript($appId, $appId . '-verify');
?>
<div id="filinq-verify"></div>

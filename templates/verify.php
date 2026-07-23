<?php

/**
 * Public signature verification portal — guest layout.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Served by PublicVerificationController::page() (#[PublicPage], no NC
 * session). Deliberately standalone: renders its OWN `docudesk-verify`
 * bundle, NOT the authenticated manifest shell (main.js / CnPageRenderer /
 * VueRouter / registry.js) — the internal, logged-in operator verify page is
 * a separate surface owned by the parallel orphaned-surface-restoration
 * change (openspec/changes/signature-verification-portal/proposal.md).
 *
 * @spec openspec/changes/signature-verification-portal/specs/signature-verification-portal/spec.md#requirement-public-verification-portal-page-req-ddsvp-001
 */

use OCP\Util;

$appId = OCA\DocuDesk\AppInfo\Application::APP_ID;
// Shared chunks must load before the entry — same baseline as templates/index.php.
Util::addScript($appId, $appId . '-shared-vendor');
Util::addScript($appId, $appId . '-shared-nc-vue');
Util::addScript($appId, $appId . '-verify');
Util::addStyle($appId, 'main');
?>
<div id="docudesk-verify-portal"></div>

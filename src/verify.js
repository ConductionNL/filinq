/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The `filinq-verify` bundle: the public signature verification page a QR
 * stamp points at, for a visitor with no account. It is its own entry
 * because `filinq-main.js` is the signed-in app shell, which an anonymous
 * visitor cannot load.
 *
 * @spec openspec/changes/archive/2026-11-25-signature-verification-portal/tasks.md#task-3.1
 */
import { loadTranslations } from '@nextcloud/l10n'
import { createApp } from 'vue'
import PublicSignatureVerification from './views/verify/PublicSignatureVerification.vue'
import { tokenFromPath } from './views/verify/verdict.js'

// Must stay first among the side effects: sets __webpack_public_path__ / __webpack_nonce__.
import './setPublicPath.js'

const el = document.getElementById('filinq-verify')
if (el) {
	const mount = () => createApp(PublicSignatureVerification, { token: tokenFromPath(window.location.pathname) }).mount(el)
	// A missing catalogue rejects; the page then reads in English.
	loadTranslations('filinq').then(mount, mount)
}

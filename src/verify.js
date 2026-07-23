/**
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * Public signature verification portal entry point
 * (openspec/changes/signature-verification-portal).
 *
 * Deliberately standalone: a plain Vue instance mounted directly onto
 * `#docudesk-verify-portal` (see templates/verify.php) — NOT the manifest V2
 * shell (main.js / CnPageRenderer / VueRouter / registry.js). The page is
 * served with no Nextcloud session (PublicVerificationController::page(),
 * `#[PublicPage]`), so it must not depend on anything the authenticated app
 * shell assumes (navigation, initial-state seeded by an app route, etc.).
 */

// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ — see setPublicPath.js.
import './setPublicPath.js'
import Vue from 'vue'
import { translate as t, translatePlural as n, loadTranslations } from '@nextcloud/l10n'
import PublicVerificationPage from './views/verify/PublicVerificationPage.vue'

// Library CSS + app CSS — same baseline as the authenticated shell so NL
// Design tokens / Cn component styling render identically for a guest.
import '@conduction/nextcloud-vue/css/index.css'
import './assets/fonts.css'
import './assets/app.css'

Vue.mixin({ methods: { t, n } })

// Fire-and-forget translation load — mirrors main.js; strings fall back to
// English source on miss (some installs 404 the l10n JSON for guests).
try {
	const result = loadTranslations('docudesk', () => {})
	if (result && typeof result.then === 'function') {
		result.then(() => {}, () => {})
	}
} catch {
	// no-op
}

new Vue({
	render: (h) => h(PublicVerificationPage),
}).$mount('#docudesk-verify-portal')

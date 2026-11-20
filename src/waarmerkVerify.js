/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The public waarmerk verification page (document-waarmerk-certification
 * REQ-DDWMK-004): a standalone entry without the app shell, for citizens
 * without a Nextcloud account.
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp, h } from 'vue'
import WaarmerkVerifyPage from './views/waarmerk/WaarmerkVerifyPage.vue'

// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ — see setPublicPath.js.
import './setPublicPath.js'

const app = createApp({
	render: () => h(WaarmerkVerifyPage),
})

app.mixin({ methods: { t } })
app.mount('#filinq-waarmerk-verify')

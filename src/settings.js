import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { createApp, h } from 'vue'
import AdminSettings from './views/settings/Settings.vue'
import pinia from './pinia.js'

// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ — see setPublicPath.js.
import './setPublicPath.js'
import './assets/fonts.css'

// `t` / `n` used to be read off Nextcloud's window globals — the old
// `Vue.mixin({ methods: { t, n } })` referenced undeclared identifiers that
// only resolved because @nextcloud/eslint-config declares them as globals.
// Importing them explicitly matches src/main.js and drops the load-order
// dependency.
//
// pinia is installed even though Settings.vue holds no store of its own:
// the panel renders CnAdminSettingsShell, and nc-vue components reach for
// the shared object store. Without an active pinia that throws at setup
// instead of degrading, and Vue 2's global PiniaVuePlugin no longer exists
// to cover it.
//
// Mount target stays `#admin-settings` (templates/settings/admin.php). Vue 3
// renders INSIDE that div rather than replacing it, so the `section` class the
// template sets is preserved instead of being discarded by `$mount()`.
const app = createApp({
	render: () => h(AdminSettings),
})

app.mixin({ methods: { t, n } })
app.use(pinia)
app.mount('#admin-settings')

// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ — see setPublicPath.js.
import './setPublicPath.js'
import Vue from 'vue'
import AdminSettings from './views/settings/Settings.vue'

Vue.mixin({ methods: { t, n } })

new Vue(
	{
		render: h => h(AdminSettings),
	},
).$mount('#admin-settings')

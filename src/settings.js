// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ before
// any CSS or asset/resource (font) URL is evaluated. See setPublicPath.js.
import './setPublicPath.js'
import Vue from 'vue'
import AdminSettings from './views/settings/Settings.vue'
import './assets/fonts.css'

Vue.mixin({ methods: { t, n } })

new Vue(
	{
		render: h => h(AdminSettings),
	},
).$mount('#admin-settings')

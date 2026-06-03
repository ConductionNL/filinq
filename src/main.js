/* eslint-disable camelcase, no-undef */
// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ before
// any CSS or asset/resource (font) URL is evaluated. See setPublicPath.js.
import './setPublicPath.js'
import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import router from './router/index.js'
import App from './App.vue'
import '@conduction/nextcloud-vue/css/index.css'
import './assets/fonts.css'
import './assets/app.css'

Vue.mixin({ methods: { t, n } })

Vue.use(PiniaVuePlugin)

new Vue(
	{
		pinia,
		router,
		render: h => h(App),
	},
).$mount('#content')

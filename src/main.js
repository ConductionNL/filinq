/* eslint-disable camelcase, no-undef */
import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { generateFilePath } from '@nextcloud/router'
import pinia from './pinia.js'
import router from './router/index.js'
import App from './App.vue'
import '@conduction/nextcloud-vue/css/index.css'
import './assets/app.css'

// Set the CSP nonce on dynamically injected webpack chunks (e.g. lazy-loaded
// pdfjs / mammoth bundles), otherwise Nextcloud's strict CSP blocks them.
__webpack_nonce__ = btoa(OC.requestToken)

// Override webpack's hardcoded publicPath with whatever Nextcloud actually
// serves the app's js/ folder from. Required when the app lives in a
// non-default apps path (e.g. apps-extra) so lazy-loaded chunks resolve.
__webpack_public_path__ = generateFilePath('docudesk', '', 'js/')

Vue.mixin({ methods: { t, n } })

Vue.use(PiniaVuePlugin)

new Vue(
	{
		pinia,
		router,
		render: h => h(App),
	},
).$mount('#content')

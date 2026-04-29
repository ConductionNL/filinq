/* eslint-disable camelcase, no-undef */
// Set the CSP nonce on dynamically injected webpack chunks (e.g. lazy-loaded
// pdfjs / mammoth bundles), otherwise Nextcloud's strict CSP blocks them.
__webpack_nonce__ = btoa(OC.requestToken)

// Override webpack's hardcoded publicPath with whatever Nextcloud actually
// serves the app's js/ folder from. Required when the app lives in a
// non-default apps path (e.g. apps-extra) so lazy-loaded chunks resolve.
import { generateFilePath } from '@nextcloud/router'
__webpack_public_path__ = generateFilePath('docudesk', '', 'js/')

import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import App from './App.vue'
import '@conduction/nextcloud-vue/src/css/index.css'
import './assets/app.css'
Vue.mixin({ methods: { t, n } })

Vue.use(PiniaVuePlugin)

new Vue(
	{
		pinia,
		render: h => h(App),
	},
).$mount('#content')

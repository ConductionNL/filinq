// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ before
// any CSS or asset/resource (font) URL is evaluated. See setPublicPath.js.
import './setPublicPath.js'
import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import AnonymizationDashboardWidget from './views/widgets/AnonymizationDashboardWidget.vue'
import FileEntitiesDashboardWidget from './views/widgets/FileEntitiesDashboardWidget.vue'
import './assets/fonts.css'

Vue.use(PiniaVuePlugin)

OCA.Dashboard.register(
	'docudesk-anonymization',
	async (el, { widget }) => {
		Vue.mixin({ methods: { t, n } })
		const View = Vue.extend(AnonymizationDashboardWidget)
		new View(
			{
				pinia,
				propsData: { title: widget.title },
			},
		).$mount(el)
	},
)

OCA.Dashboard.register(
	'docudesk-file-entities',
	async (el, { widget }) => {
		Vue.mixin({ methods: { t, n } })
		const View = Vue.extend(FileEntitiesDashboardWidget)
		new View(
			{
				pinia,
				propsData: { title: widget.title },
			},
		).$mount(el)
	},
)

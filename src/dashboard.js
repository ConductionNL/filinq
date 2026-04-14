import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import AnonymizationDashboardWidget from './views/widgets/AnonymizationDashboardWidget.vue'
import FileEntitiesDashboardWidget from './views/widgets/FileEntitiesDashboardWidget.vue'

Vue.use(PiniaVuePlugin)

console.info('[DocuDesk] Registering anonymization widget callback')
if (window.OCA) {
	console.info('[DocuDesk] OCA.Dashboard:', window.OCA.Dashboard)
} else {
	console.info('[DocuDesk] OCA.Dashboard: undefined')
}

OCA.Dashboard.register(
	'docudesk-anonymization',
	async (el, { widget }) => {
		console.info('[DocuDesk] Widget callback called!', el, widget)
		Vue.mixin({ methods: { t, n } })
		const View = Vue.extend(AnonymizationDashboardWidget)
		const instance = new View(
			{
				pinia,
				propsData: { title: widget.title },
			},
		).$mount(el)
		console.info('[DocuDesk] Widget mounted:', instance)
	},
)

OCA.Dashboard.register(
	'docudesk-file-entities',
	async (el, { widget }) => {
		console.info('[DocuDesk] File entities widget callback called!', el, widget)
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

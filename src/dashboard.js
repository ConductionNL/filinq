import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import pinia from './pinia.js'
import AnonymizationDashboardWidget from './views/widgets/AnonymizationDashboardWidget.vue'

Vue.use(PiniaVuePlugin)

OCA.Dashboard.register('docudesk-anonymization', async (el, { widget }) => {
	Vue.mixin({ methods: { t, n } })
	const View = Vue.extend(AnonymizationDashboardWidget)
	new View({
		pinia,
		propsData: { title: widget.title },
	}).$mount(el)
})

import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import pinia from './pinia.js'
import AnonymizationDashboardWidget from './views/widgets/AnonymizationDashboardWidget.vue'
import FileEntitiesDashboardWidget from './views/widgets/FileEntitiesDashboardWidget.vue'

Vue.use(PiniaVuePlugin)

console.log('[DocuDesk] Registering anonymization widget callback')
console.log('[DocuDesk] OCA.Dashboard:', window.OCA ? .Dashboard)

OCA.Dashboard.register(
        'docudesk-anonymization',
        async(el, { widget }) => {
            console.log('[DocuDesk] Widget callback called!', el, widget)
            Vue.mixin({ methods: { t, n } })
            const View     = Vue.extend(AnonymizationDashboardWidget)
            const instance = new View(
            {
                pinia,
                propsData: { title: widget.title },
            }
         ).$mount(el)
        console.log('[DocuDesk] Widget mounted:', instance)
}
        )

OCA.Dashboard.register(
        'docudesk-file-entities',
        async(el, { widget }) => {
            console.log('[DocuDesk] File entities widget callback called!', el, widget)
            Vue.mixin({ methods: { t, n } })
            const View = Vue.extend(FileEntitiesDashboardWidget)
            new View(
            {
                pinia,
                propsData: { title: widget.title },
            }
         ).$mount(el)
}
        )

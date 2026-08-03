// Must stay first: sets __webpack_public_path__ / __webpack_nonce__ — see setPublicPath.js.
import './setPublicPath.js'
import { createApp, h } from 'vue'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import pinia from './pinia.js'
import './assets/fonts.css'
import AnonymizationDashboardWidget from './views/widgets/AnonymizationDashboardWidget.vue'
import FileEntitiesDashboardWidget from './views/widgets/FileEntitiesDashboardWidget.vue'

/**
 * Mount one Nextcloud dashboard widget into the element the Dashboard app
 * hands us.
 *
 * Vue 3 has no `Vue.extend()` + `new View({ propsData }).$mount(el)` path:
 * `createApp` takes the props directly and plugins are registered per app
 * instance rather than globally. Registering `t`/`n` and pinia per widget is
 * therefore required — there is no global `Vue.mixin` left to leak them in.
 *
 * @param {object} component The widget SFC to render.
 * @param {HTMLElement} el The element supplied by OCA.Dashboard.
 * @param {string} title The widget title from the Dashboard registration.
 * @return {void}
 */
function mountWidget(component, el, title) {
	const app = createApp({
		render: () => h(component, { title }),
	})
	app.mixin({ methods: { t, n } })
	app.use(pinia)
	app.mount(el)
}

OCA.Dashboard.register(
	'docudesk-anonymization',
	(el, { widget }) => mountWidget(AnonymizationDashboardWidget, el, widget.title),
)

OCA.Dashboard.register(
	'docudesk-file-entities',
	(el, { widget }) => mountWidget(FileEntitiesDashboardWidget, el, widget.title),
)

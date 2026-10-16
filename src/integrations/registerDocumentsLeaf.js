/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Filinq's documents leaf: what Filinq holds for ANY OpenRegister object.
 *
 * Filinq owns documents, so Filinq renders them. A consuming app places this
 * leaf and passes the object context instead of reading Filinq's register from
 * its own manifest, so a record on a case page shows the letters, generated
 * documents and uploads that belong to it without that app knowing anything
 * about Filinq's schemas.
 *
 * The render half of ONE registration whose server half is
 * `lib/EventListener/RegisterDocumentsLeafListener.php`. Both halves carry the
 * same id, the same surfaces and the same render mode; a difference between
 * them is a leaf whose behaviour depends on which half the consumer read.
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import CnFilinqDocumentsWidget from './CnFilinqDocumentsWidget.vue'

/**
 * The integration id a consuming app references to place this leaf.
 *
 * @type {string}
 */
export const DOCUMENTS_INTEGRATION_ID = 'filinq-documents'

/**
 * Every render surface this leaf targets, written out rather than left to the
 * host's default.
 *
 * Duplicated verbatim by `RegisterDocumentsLeafListener::SURFACES`. A half that
 * declares its surfaces by OMISSION is how two halves of one leaf drift apart
 * with every gate still green.
 *
 * @type {string[]}
 */
export const SURFACES = ['detail-page', 'single-entity']

/**
 * Vue apps this leaf has mounted, keyed by the host-owned element.
 *
 * Keyed by ELEMENT, not by leaf id: one page may show the leaf as a tab and as
 * a widget at once, and each needs to unmount independently.
 *
 * @type {Map<Element, import('vue').App>}
 */
const mountedApps = new Map()

/**
 * Root a Filinq-owned Vue app at a host-owned element.
 *
 * Filinq is Vue 3 and a consuming host may be Vue 2.7. A Vue 3 SFC handed to
 * such a host renders blank under its incompatible runtime, so the host hands
 * over a bare DOM element and each side runs its own framework across that
 * neutral boundary. Idempotent per element.
 *
 * @param {Element} el    Host-owned container element.
 * @param {object}  props Forwarded context: { register, schema, objectId, … }.
 *
 * @return {void}
 */
export function mount(el, props) {
	if (el === undefined || el === null || mountedApps.has(el) === true) {
		return
	}
	const app = createApp(CnFilinqDocumentsWidget, { ...(props || {}) })
	// The SFC calls `t(...)` as a global, which main.js installs for the app
	// bundle; this leaf mounts its own instance, so install it here too.
	app.config.globalProperties.t = t
	app.mount(el)
	mountedApps.set(el, app)
}

/**
 * Destroy the app rooted at `el` and release the map entry.
 *
 * @param {Element} el The element previously passed to `mount`.
 *
 * @return {void}
 */
export function unmount(el) {
	const app = mountedApps.get(el)
	if (app === undefined) {
		return
	}
	mountedApps.delete(el)
	app.unmount()
}

/**
 * The integration descriptor for the documents leaf.
 *
 * @type {object}
 */
export const documentsLeafDescriptor = {
	id: DOCUMENTS_INTEGRATION_ID,
	label: t('filinq', 'Documents'),
	icon: 'FileDocumentMultipleOutline',
	requiredApp: 'filinq',
	order: 30,
	group: 'documents',
	surfaces: SURFACES,
	referenceType: DOCUMENTS_INTEGRATION_ID,
	renderMode: 'mount',
	mount,
	unmount,
	defaultSize: { w: 4, h: 3 },
}

/**
 * Register the leaf on the shared OpenRegister integration registry.
 *
 * Installs a load-order-safe queue stub when OpenRegister's own bundle has not
 * installed the real registry yet, so a Filinq bundle that happens to load
 * first is not simply lost.
 *
 * @param {object} [globalRef] Global to attach to (defaults to `window`).
 *
 * @return {void}
 */
export function registerDocumentsLeaf(globalRef) {
	const target = globalRef || (typeof window !== 'undefined' ? window : null)
	if (target === null) {
		return
	}

	target.OCA = target.OCA || {}
	target.OCA.OpenRegister = target.OCA.OpenRegister || {}
	target.OCA.OpenRegister.integrations = target.OCA.OpenRegister.integrations || {
		_queue: [],
		register(entry) {
			this._queue.push(entry)
		},
	}

	target.OCA.OpenRegister.integrations.register(documentsLeafDescriptor)
}

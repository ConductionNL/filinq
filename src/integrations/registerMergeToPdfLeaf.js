/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-merge-to-pdf` leaf. The server half is
 * `lib/EventListener/RegisterMergeToPdfLeafListener.php`; both declare the same
 * id, icon, surfaces and render mode, and a PHPUnit test compares them.
 *
 * A render surface on one object: the widget lists that object's documents,
 * lets the handler choose and order them, and asks Filinq for one PDF.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import CnFilinqMergeToPdfWidget from './CnFilinqMergeToPdfWidget.vue'

export const MERGE_INTEGRATION_ID = 'filinq-merge-to-pdf'

export const MERGE_SURFACES = ['detail-page', 'single-entity']

const mountedApps = new Map()

/**
 * Mount the widget into a host element.
 *
 * @param {HTMLElement} el    The element the host hands over.
 * @param {object}      props The host object context.
 *
 * @return {void}
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
export function mount(el, props) {
	if (el === undefined || el === null || mountedApps.has(el) === true) {
		return
	}
	const app = createApp(CnFilinqMergeToPdfWidget, { ...(props || {}) })
	app.config.globalProperties.t = t
	app.mount(el)
	mountedApps.set(el, app)
}

/**
 * Unmount the widget from a host element.
 *
 * @param {HTMLElement} el The element the widget was mounted in.
 *
 * @return {void}
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
export function unmount(el) {
	const app = mountedApps.get(el)
	if (app === undefined) {
		return
	}
	mountedApps.delete(el)
	app.unmount()
}

export const mergeToPdfLeafDescriptor = {
	id: MERGE_INTEGRATION_ID,
	label: t('filinq', 'Merge to PDF'),
	icon: 'FilePdfBox',
	requiredApp: 'filinq',
	order: 31,
	group: 'documents',
	surfaces: MERGE_SURFACES,
	referenceType: MERGE_INTEGRATION_ID,
	renderMode: 'mount',
	mount,
	unmount,
	defaultSize: { w: 4, h: 4 },
}

/**
 * Register the leaf with OpenRegister's integration registry, or queue it
 * until the registry loads.
 *
 * @param {object} [globalRef] The global object, for tests.
 *
 * @return {void}
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
export function registerMergeToPdfLeaf(globalRef) {
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

	target.OCA.OpenRegister.integrations.register(mergeToPdfLeafDescriptor)
}

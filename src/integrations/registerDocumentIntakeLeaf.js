/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-document-intake` leaf. The server half is
 * `lib/EventListener/RegisterDocumentIntakeLeafListener.php`; a PHPUnit test
 * compares the two. The widget lists the documents waiting in filinq's intake
 * inbox on another app's record and assigns one to that record.
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import CnFilinqDocumentIntakeWidget from './CnFilinqDocumentIntakeWidget.vue'

export const DOCUMENT_INTAKE_INTEGRATION_ID = 'filinq-document-intake'

export const DOCUMENT_INTAKE_SURFACES = ['detail-page', 'single-entity']

const mountedApps = new Map()

/**
 * Mount the widget into a host element.
 *
 * @param {HTMLElement} el    The element the host hands over.
 * @param {object}      props The host record: { register, schema, objectId, … }.
 *
 * @return {void}
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
export function mount(el, props) {
	if (el === undefined || el === null || mountedApps.has(el) === true) {
		return
	}
	const app = createApp(CnFilinqDocumentIntakeWidget, { ...(props || {}) })
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
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
export function unmount(el) {
	const app = mountedApps.get(el)
	if (app === undefined) {
		return
	}
	mountedApps.delete(el)
	app.unmount()
}

export const documentIntakeLeafDescriptor = {
	id: DOCUMENT_INTAKE_INTEGRATION_ID,
	label: t('filinq', 'Documents waiting to be filed'),
	icon: 'InboxArrowDown',
	requiredApp: 'filinq',
	order: 33,
	group: 'documents',
	surfaces: DOCUMENT_INTAKE_SURFACES,
	referenceType: DOCUMENT_INTAKE_INTEGRATION_ID,
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
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
export function registerDocumentIntakeLeaf(globalRef) {
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

	target.OCA.OpenRegister.integrations.register(documentIntakeLeafDescriptor)
}

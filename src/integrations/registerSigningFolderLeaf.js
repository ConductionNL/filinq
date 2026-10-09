/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-signing-folder` leaf. The server half is
 * `lib/EventListener/RegisterSigningFolderLeafListener.php`; a PHPUnit test
 * compares the two. The widget shows what is waiting for the signer's
 * signature across every record, on a dashboard, and links to the full folder.
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import CnFilinqSigningFolderWidget from './CnFilinqSigningFolderWidget.vue'

export const SIGNING_FOLDER_INTEGRATION_ID = 'filinq-signing-folder'

export const SIGNING_FOLDER_SURFACES = ['user-dashboard', 'app-dashboard']

const mountedApps = new Map()

/**
 * Mount the widget into a host element.
 *
 * @param {HTMLElement} el    The element the host hands over.
 * @param {object}      props The host context (unused: the folder is per signer).
 *
 * @return {void}
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export function mount(el, props) {
	if (el === undefined || el === null || mountedApps.has(el) === true) {
		return
	}
	const app = createApp(CnFilinqSigningFolderWidget, { ...(props || {}) })
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
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export function unmount(el) {
	const app = mountedApps.get(el)
	if (app === undefined) {
		return
	}
	mountedApps.delete(el)
	app.unmount()
}

export const signingFolderLeafDescriptor = {
	id: SIGNING_FOLDER_INTEGRATION_ID,
	label: t('filinq', 'Waiting for your signature'),
	icon: 'FileSign',
	requiredApp: 'filinq',
	order: 40,
	group: 'signing',
	surfaces: SIGNING_FOLDER_SURFACES,
	referenceType: SIGNING_FOLDER_INTEGRATION_ID,
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
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export function registerSigningFolderLeaf(globalRef) {
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

	target.OCA.OpenRegister.integrations.register(signingFolderLeafDescriptor)
}

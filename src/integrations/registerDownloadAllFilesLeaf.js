/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-download-all-files` leaf. The server half is
 * `lib/EventListener/RegisterDownloadAllFilesLeafListener.php`; a PHPUnit test
 * compares the two. The widget downloads every file on one object as one
 * archive with a manifest, and warns first when the files exceed the ceiling.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
import { translate as t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import CnFilinqDownloadAllFilesWidget from './CnFilinqDownloadAllFilesWidget.vue'

export const DOWNLOAD_ALL_INTEGRATION_ID = 'filinq-download-all-files'

export const DOWNLOAD_ALL_SURFACES = ['detail-page', 'single-entity']

const mountedApps = new Map()

/**
 * Mount the widget into a host element.
 *
 * @param {HTMLElement} el    The element the host hands over.
 * @param {object}      props The host object context.
 *
 * @return {void}
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function mount(el, props) {
	if (el === undefined || el === null || mountedApps.has(el) === true) {
		return
	}
	const app = createApp(CnFilinqDownloadAllFilesWidget, { ...(props || {}) })
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function unmount(el) {
	const app = mountedApps.get(el)
	if (app === undefined) {
		return
	}
	mountedApps.delete(el)
	app.unmount()
}

export const downloadAllFilesLeafDescriptor = {
	id: DOWNLOAD_ALL_INTEGRATION_ID,
	label: t('filinq', 'Download all files'),
	icon: 'FolderZipOutline',
	requiredApp: 'filinq',
	order: 32,
	group: 'documents',
	surfaces: DOWNLOAD_ALL_SURFACES,
	referenceType: DOWNLOAD_ALL_INTEGRATION_ID,
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
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
export function registerDownloadAllFilesLeaf(globalRef) {
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

	target.OCA.OpenRegister.integrations.register(downloadAllFilesLeafDescriptor)
}

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-download-all-files` leaf. The server half is
 * `lib/EventListener/RegisterDownloadAllFilesLeafListener.php`; a PHPUnit test
 * compares the two. The widget downloads every file on one object as one
 * archive with a manifest, and warns first when the files exceed the ceiling.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
import { translate as t } from '@nextcloud/l10n'
import CnFilinqDownloadAllFilesWidget from './CnFilinqDownloadAllFilesWidget.vue'
import { leafMounter, registerLeaf } from './leafHost.js'

export const DOWNLOAD_ALL_INTEGRATION_ID = 'filinq-download-all-files'

export const DOWNLOAD_ALL_SURFACES = ['detail-page', 'single-entity']

/**
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqDownloadAllFilesWidget)

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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
export function registerDownloadAllFilesLeaf(globalRef) {
	registerLeaf(downloadAllFilesLeafDescriptor, globalRef)
}

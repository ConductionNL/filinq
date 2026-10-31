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
 * @spec openspec/specs/document-merge/spec.md
 */
import { translate as t } from '@nextcloud/l10n'
import CnFilinqMergeToPdfWidget from './CnFilinqMergeToPdfWidget.vue'
import { leafMounter, registerLeaf } from './leafHost.js'

export const MERGE_INTEGRATION_ID = 'filinq-merge-to-pdf'

export const MERGE_SURFACES = ['detail-page', 'single-entity']

/**
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqMergeToPdfWidget)

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
 * @spec openspec/specs/document-merge/spec.md
 */
export function registerMergeToPdfLeaf(globalRef) {
	registerLeaf(mergeToPdfLeafDescriptor, globalRef)
}

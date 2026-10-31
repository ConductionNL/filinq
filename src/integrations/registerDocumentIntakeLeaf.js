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
import CnFilinqDocumentIntakeWidget from './CnFilinqDocumentIntakeWidget.vue'
import { leafMounter, registerLeaf } from './leafHost.js'

export const DOCUMENT_INTAKE_INTEGRATION_ID = 'filinq-document-intake'

export const DOCUMENT_INTAKE_SURFACES = ['detail-page', 'single-entity']

/**
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqDocumentIntakeWidget)

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
	registerLeaf(documentIntakeLeafDescriptor, globalRef)
}

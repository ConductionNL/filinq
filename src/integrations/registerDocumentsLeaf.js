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
import CnFilinqDocumentsWidget from './CnFilinqDocumentsWidget.vue'
import { leafMounter, registerLeaf } from './leafHost.js'

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
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqDocumentsWidget)

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
	// No card of its own: a detail page hosts it in the library card and hands
	// it `onHeader`; a "linked" card hands it `onSummary`.
	chrome: 'card',
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
	registerLeaf(documentsLeafDescriptor, globalRef)
}

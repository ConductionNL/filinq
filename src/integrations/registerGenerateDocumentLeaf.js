/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The JS half of the `filinq-generate-document` leaf. The server half is
 * `lib/EventListener/RegisterGenerateDocumentLeafListener.php`; both declare
 * the same id, icon, surfaces and render mode, and a PHPUnit test compares them.
 *
 * A render surface on one object: the widget lists the templates that apply to
 * that object and generates a document from one with the object as its data.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
import { translate as t } from '@nextcloud/l10n'
import CnFilinqGenerateDocumentWidget from './CnFilinqGenerateDocumentWidget.vue'
import { integrationRegistry, leafMounter } from './leafHost.js'

export const GENERATE_INTEGRATION_ID = 'filinq-generate-document'

export const GENERATE_SURFACES = ['detail-page', 'single-entity']

/**
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqGenerateDocumentWidget)

export const generateDocumentLeafDescriptor = {
	id: GENERATE_INTEGRATION_ID,
	label: t('filinq', 'Generate document'),
	icon: 'FileDocumentEditOutline',
	requiredApp: 'filinq',
	order: 34,
	group: 'documents',
	surfaces: GENERATE_SURFACES,
	referenceType: GENERATE_INTEGRATION_ID,
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
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
export function registerGenerateDocumentLeaf(globalRef) {
	const integrations = integrationRegistry(globalRef)
	integrations.register(generateDocumentLeafDescriptor)
}

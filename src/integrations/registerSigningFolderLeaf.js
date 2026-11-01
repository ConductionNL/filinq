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
import CnFilinqSigningFolderWidget from './CnFilinqSigningFolderWidget.vue'
import { integrationRegistry, leafMounter } from './leafHost.js'

export const SIGNING_FOLDER_INTEGRATION_ID = 'filinq-signing-folder'

export const SIGNING_FOLDER_SURFACES = ['user-dashboard', 'app-dashboard']

/**
 * Mount and unmount the widget at a host-owned element; see `leafMounter`.
 */
export const { mount, unmount } = leafMounter(CnFilinqSigningFolderWidget)

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
	const integrations = integrationRegistry(globalRef)
	integrations.register(signingFolderLeafDescriptor)
}

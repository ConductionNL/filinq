/**
 * Which file's notes the viewer on My documents and a dossier's document
 * view show: the file that is open, as a string id, and none while nothing
 * is open or the dossier's document is missing.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/work-document-notes/specs/document-notes/spec.md#requirement-notes-sit-beside-the-document-on-filinqs-screens-req-wdn-002
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

const stores = vi.hoisted(() => ({ fileViewerStore: { currentFile: null }, dossierStore: {} }))

vi.mock('../../src/store/store.js', () => stores)
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {} }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text, translatePlural: (app, one) => one }))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))

/**
 * The computed options of a view.
 *
 * @param {string} path The view, relative to src/views.
 * @return {Promise<object>} Its computed functions.
 */
async function computedOf(path) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/views', path))
	return options.computed
}

describe('the notes panel of the file viewer', () => {
	it('shows the notes of the open file, and none while nothing is open', async () => {
		const { notesFileId } = await computedOf('fileViewer/FileViewerPage.vue')

		stores.fileViewerStore.currentFile = { fileId: 4711, fileName: 'contract.pdf' }
		expect(notesFileId.call({})).toBe('4711')

		stores.fileViewerStore.currentFile = null
		expect(notesFileId.call({})).toBeNull()

		stores.fileViewerStore.currentFile = { fileName: 'no id' }
		expect(notesFileId.call({})).toBeNull()
	})
})

describe('the notes panel of a dossier', () => {
	it('shows the selected document\'s own notes, and none for a missing one', async () => {
		const { notesFileId } = await computedOf('dossier/DossierDetail.vue')

		expect(notesFileId.call({ dossierStore: { selectedDocument: { id: 21 } } })).toBe('21')
		expect(notesFileId.call({ dossierStore: { selectedDocument: { id: 22, missing: true } } })).toBeNull()
		expect(notesFileId.call({ dossierStore: { selectedDocument: null } })).toBeNull()
	})
})

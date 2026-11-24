/**
 * The AI companion knows which document or folder a filinq page shows: the
 * file viewer points it at the open file, a dossier at its home folder, and
 * leaving the page takes the file back out.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { pointCompanionAt, releaseCompanion } from '../../src/utils/companionContext.js'
import { loadSfcOptions } from './sfc/sfcOptions.js'

const stores = vi.hoisted(() => ({ fileViewerStore: { currentFile: null }, dossierStore: {} }))

vi.mock('../../src/store/store.js', () => stores)
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {} }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text, translatePlural: (app, one) => one }))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))

/**
 * The options of a view.
 *
 * @param {string} path The view, relative to src/views.
 * @return {Promise<object>} Its component options.
 */
function optionsOf(path) {
	return loadSfcOptions(join(import.meta.dirname, '../../src/views', path))
}

describe('pointCompanionAt', () => {
	it('points the context at a document or folder', () => {
		const context = { appId: 'filinq', pageKind: 'custom' }
		pointCompanionAt(context, 'document', '4711')
		expect(context).toMatchObject({ pageKind: 'document', fileId: 4711 })
		pointCompanionAt(context, 'folder', 12)
		expect(context).toMatchObject({ pageKind: 'folder', fileId: 12 })
	})

	it('clears the scope when there is no id', () => {
		const context = { pageKind: 'document', fileId: 4711 }
		pointCompanionAt(context, 'document', null)
		expect(context.pageKind).toBe('custom')
		expect(context.fileId).toBeUndefined()
	})

	it('does nothing outside CnAppRoot', () => {
		expect(() => pointCompanionAt(null, 'document', 1)).not.toThrow()
		expect(() => releaseCompanion(null)).not.toThrow()
	})
})

describe('releaseCompanion', () => {
	it('takes the file back out and leaves another page kind alone', () => {
		const context = { pageKind: 'detail', fileId: 5 }
		releaseCompanion(context)
		expect(context).toEqual({ pageKind: 'detail', fileId: undefined })
	})
})

describe('the file viewer', () => {
	it('points the companion at the open file, and releases it on leaving', async () => {
		const options = await optionsOf('fileViewer/FileViewerPage.vue')
		expect(options.inject.cnAiContext).toEqual({ from: 'cnAiContext', default: null })

		const vm = {
			cnAiContext: { appId: 'filinq', pageKind: 'custom' },
			notesFileId: '4711',
			loadOcrStatus: () => {},
			loadSourceBadge: () => {},
		}
		options.watch['fileViewerStore.currentFile.fileId'].handler.call(vm)
		expect(vm.cnAiContext).toMatchObject({ pageKind: 'document', fileId: 4711 })

		options.beforeUnmount.call(vm)
		expect(vm.cnAiContext.fileId).toBeUndefined()
		expect(vm.cnAiContext.pageKind).toBe('custom')
	})
})

describe('a dossier', () => {
	it('points the companion at its home folder, and releases it on leaving', async () => {
		const options = await optionsOf('dossier/DossierDetail.vue')
		const { companionFolderId } = options.computed
		expect(companionFolderId.call({ dossierStore: { dossier: { folderId: 88 } } })).toBe(88)
		expect(companionFolderId.call({ dossierStore: { dossier: { folderId: null } } })).toBeNull()
		expect(companionFolderId.call({ dossierStore: { dossier: null } })).toBeNull()

		const vm = { cnAiContext: { appId: 'filinq', pageKind: 'custom' } }
		options.watch.companionFolderId.handler.call(vm, 88)
		expect(vm.cnAiContext).toMatchObject({ pageKind: 'folder', fileId: 88 })

		options.beforeUnmount.call(vm)
		expect(vm.cnAiContext.fileId).toBeUndefined()
	})
})

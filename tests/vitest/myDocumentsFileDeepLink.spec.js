// @vitest-environment jsdom
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * My documents opens the viewer on the document the `file` route parameter
 * names, on mount and when the parameter changes (work-content-search
 * REQ-WCS-004).
 *
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

const viewer = vi.hoisted(() => ({ currentFile: null }))
const openLinkedFile = vi.hoisted(() => vi.fn())
const showError = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: vi.fn() }))
vi.mock('../../src/store/store.js', () => ({ fileViewerStore: viewer, myDocumentsStore: {} }))
vi.mock('../../src/services/fileDeepLink.js', () => ({ openLinkedFile }))
vi.mock('@nextcloud/dialogs', () => ({ showError, showSuccess: vi.fn() }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
	translatePlural: (app, one) => one,
}))

const options = await loadSfcOptions(join(process.cwd(), 'src/views/myDocuments/MyDocumentsIndex.vue'))

/**
 * Run `openFromRoute()` with this route query.
 *
 * @param {object} query The route query.
 * @return {Promise<void>}
 */
function openWith(query) {
	return options.methods.openFromRoute.call({ $route: { query } })
}

describe('the file route parameter on My documents', () => {
	beforeEach(() => {
		openLinkedFile.mockReset()
		showError.mockReset()
	})

	it('hands the route query and the viewer to the deep link', async () => {
		openLinkedFile.mockResolvedValue('opened')
		await openWith({ file: '42' })
		expect(openLinkedFile).toHaveBeenCalledWith({ file: '42' }, viewer)
		expect(showError).not.toHaveBeenCalled()
	})

	it('says so when the document cannot be opened', async () => {
		openLinkedFile.mockResolvedValue('missing')
		await openWith({ file: '42' })
		expect(showError).toHaveBeenCalledWith('The linked document could not be found, or you do not have access to it.')
	})

	it('stays quiet when there is nothing to open', async () => {
		openLinkedFile.mockResolvedValue('none')
		await options.methods.openFromRoute.call({})
		expect(openLinkedFile).toHaveBeenCalledWith(undefined, viewer)
		expect(showError).not.toHaveBeenCalled()
	})

	it('runs on mount and when the parameter changes', () => {
		expect(options.mounted.toString()).toContain('this.openFromRoute()')
		const self = { openFromRoute: vi.fn() }
		options.watch['$route.query.file'].call(self)
		expect(self.openFromRoute).toHaveBeenCalledTimes(1)
	})
})

/**
 * The header icon of the file viewer page per viewer kind.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('../../src/store/store.js', () => ({ fileViewerStore: {} }))
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {} }))
vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('@nextcloud/axios', () => ({ default: {} }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))

/**
 * The page's fileTypeIcon computed for one viewer kind.
 *
 * @param {string|null|undefined} viewerKind The detected viewer kind.
 * @return {Promise<string>} The icon component name.
 */
async function iconFor(viewerKind) {
	const options = await loadSfcOptions(
		join(import.meta.dirname, '../../src/views/fileViewer/FileViewerPage.vue'),
	)
	return options.computed.fileTypeIcon.call({ viewerKind })
}

describe('FileViewerPage fileTypeIcon', () => {
	it.each([
		['pdf', 'FilePdfBox'],
		['word', 'FileWordBox'],
		['odt', 'FileWordBox'],
		['text', 'FileDocumentOutline'],
		['', 'FileDocumentOutline'],
		[null, 'FileDocumentOutline'],
		[undefined, 'FileDocumentOutline'],
		['constructor', 'FileDocumentOutline'],
	])('kind %s shows %s', async (kind, icon) => {
		expect(await iconFor(kind)).toBe(icon)
	})
})

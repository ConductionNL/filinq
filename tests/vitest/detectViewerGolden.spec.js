// @vitest-environment jsdom
/**
 * Golden: which viewer opens a file, recorded on the if-chain implementation.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-7
 */

import { describe, it } from 'vitest'
import { detectViewer } from '../../src/services/viewerRouting.js'
import { golden } from './golden/golden.js'

const MIMES = [
	undefined,
	'',
	'application/pdf',
	'APPLICATION/X-PDF',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'application/msword',
	'application/vnd.oasis.opendocument.text',
	'application/vnd.oasis.opendocument.spreadsheet',
	'message/rfc822',
	'text/plain',
	'TEXT/HTML',
	'image/png',
]

const NAMES = [
	undefined,
	'',
	'a.PDF',
	'b.docx',
	'c.doc',
	'd.ODT',
	'e.eml',
	'f.txt',
	'g.md',
	'h.markdown',
	'i.log',
	'j.CSV',
	'k.txt.bak',
	'l.png',
	'pdf',
]

describe('detectViewer golden', () => {
	it('picks the recorded viewer for every MIME type and name', () => {
		const out = [detectViewer(null), detectViewer(undefined), detectViewer({})]
		for (const mimeType of MIMES) {
			for (const fileName of NAMES) {
				out.push(detectViewer({ mimeType, fileName }))
			}
		}
		golden('detectViewer', out)
	})
})

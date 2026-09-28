/**
 * The download-all-files leaf: what the preflight warns about, and how the
 * manifest is read back.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Fixtures are the shapes `CaseArchiveService::preflight()` and `build()`
 * return: `{files, bytes, ceilingBytes, exceedsCeiling}` and
 * `{included, excluded: [{name, reason}], bytes, archive: {fileId, path}}`.
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	archiveFileId,
	excludedByReason,
	megabytes,
	needsWarning,
} from '../../src/services/archiveBundle.js'

describe('archiveBundle', () => {
	it('writes a size in megabytes with one decimal', () => {
		expect(megabytes(0)).toBe('0.0')
		expect(megabytes(1048576)).toBe('1.0')
		expect(megabytes(1572864)).toBe('1.5')
	})

	it('warns before the job only when the selection exceeds the ceiling', () => {
		expect(
			needsWarning({
				files: 3,
				bytes: 10,
				ceilingBytes: 100,
				exceedsCeiling: false,
			}),
		).toBe(false)
		expect(
			needsWarning({
				files: 3,
				bytes: 200,
				ceilingBytes: 100,
				exceedsCeiling: true,
			}),
		).toBe(true)
		expect(needsWarning(null)).toBe(false)
	})

	it('groups every left-out file by its reason, so none is dropped silently', () => {
		const grouped = excludedByReason({
			included: [{ name: 'a.pdf' }],
			excluded: [
				{ name: 'groot.mp4', reason: 'ceiling' },
				{ name: 'geheim.pdf', reason: 'permission' },
				{ name: 'weg.pdf', reason: 'missing' },
				{ name: 'raar.pdf', reason: 'something-new' },
			],
		})

		expect(grouped).toEqual({
			ceiling: ['groot.mp4'],
			permission: ['geheim.pdf'],
			missing: ['weg.pdf'],
			other: ['raar.pdf'],
		})
	})

	it('finds the written archive, or says there is none', () => {
		expect(archiveFileId({ archive: { fileId: 77, path: '/x.zip' } })).toBe(77)
		expect(archiveFileId({ included: [] })).toBe(0)
	})
})

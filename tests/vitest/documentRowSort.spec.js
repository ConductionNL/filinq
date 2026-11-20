/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-the-flat-file-list-sorts-on-any-column-req-scr-005
 */
import { describe, expect, it } from 'vitest'
import { nextSort, sortDocumentRows } from '../../src/integrations/documentRowSort.js'

const rows = [
	{ fileId: 1, name: 'Besluit 10.pdf', mtime: 1700000200, size: 30, record: { status: 'final' } },
	{ fileId: 2, name: 'Aanvraag.pdf', mtime: 1700000300, size: 10, record: { status: 'draft' } },
	{ fileId: 3, name: 'Besluit 9.pdf', mtime: 1700000100, size: 20 },
]

const ids = (list) => list.map((row) => row.fileId)

describe('sorting the flat file list', () => {
	it('sorts on date newest first, then oldest first when chosen again', () => {
		const first = nextSort({ key: 'name', direction: 'asc' }, 'mtime')
		expect(first).toEqual({ key: 'mtime', direction: 'desc' })
		expect(ids(sortDocumentRows(rows, first))).toEqual([2, 1, 3])

		const again = nextSort(first, 'mtime')
		expect(again).toEqual({ key: 'mtime', direction: 'asc' })
		expect(ids(sortDocumentRows(rows, again))).toEqual([3, 1, 2])
	})

	it('sorts names naturally and status with the empty one first', () => {
		expect(ids(sortDocumentRows(rows, { key: 'name', direction: 'asc' }))).toEqual([2, 3, 1])
		expect(ids(sortDocumentRows(rows, { key: 'status', direction: 'asc' }))).toEqual([3, 2, 1])
		expect(ids(sortDocumentRows(rows, { key: 'size', direction: 'desc' }))).toEqual([1, 3, 2])
	})

	it('leaves the input alone and falls back to name for an unknown column', () => {
		const copy = [...rows]
		expect(ids(sortDocumentRows(rows, { key: 'nope', direction: 'asc' }))).toEqual([2, 3, 1])
		expect(rows).toEqual(copy)
		expect(sortDocumentRows(null, { key: 'name' })).toEqual([])
	})
})

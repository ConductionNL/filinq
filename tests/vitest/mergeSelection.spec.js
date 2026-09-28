/**
 * The merge-to-PDF leaf: ordering a selection and building the merge request.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The request is the body `MergeController::create()` reads: `inputs` in the
 * order they go in, each `{fileId, label}`, and `options` with the cover
 * template, the bookmarks toggle and the name. No target folder: the server
 * puts the result beside the first input.
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	buildMergeRequest,
	canMerge,
	moveInSelection,
	toggleInSelection,
} from '../../src/services/mergeSelection.js'

const ROWS = [
	{ fileId: 11, name: 'aanvraag.pdf' },
	{ fileId: 12, name: 'besluit.docx' },
	{ fileId: 13, name: 'bijlage.pdf' },
]

describe('mergeSelection', () => {
	it('adds a document at the end and removes it again', () => {
		const once = toggleInSelection([], 12)
		expect(once).toEqual([12])
		expect(toggleInSelection([11, 12], 12)).toEqual([11])
		expect(toggleInSelection(once, 11)).toEqual([12, 11])
	})

	it('moves a document up and down, and stops at the ends', () => {
		expect(moveInSelection([11, 12, 13], 2, -1)).toEqual([11, 13, 12])
		expect(moveInSelection([11, 12, 13], 0, 1)).toEqual([12, 11, 13])
		expect(moveInSelection([11, 12, 13], 0, -1)).toEqual([11, 12, 13])
		expect(moveInSelection([11, 12, 13], 2, 1)).toEqual([11, 12, 13])
	})

	it('needs at least two documents', () => {
		expect(canMerge([])).toBe(false)
		expect(canMerge([11])).toBe(false)
		expect(canMerge([11, 12])).toBe(true)
	})

	it('builds the request in the chosen order, with the options the dialog set', () => {
		const request = buildMergeRequest({
			rows: ROWS,
			selection: [13, 11],
			coverTemplateRef: 'tpl-1',
			bookmarks: false,
			name: '  Bundel zaak 42  ',
			host: { register: 'dossiq', schema: 'case', objectId: 'case-42' },
		})

		expect(request).toEqual({
			inputs: [
				{ fileId: 13, label: 'bijlage.pdf' },
				{ fileId: 11, label: 'aanvraag.pdf' },
			],
			options: {
				bookmarks: false,
				coverTemplateRef: 'tpl-1',
				name: 'Bundel zaak 42',
			},
			hostObject: { register: 'dossiq', schema: 'case', id: 'case-42' },
		})
	})

	it('leaves out an empty cover and an empty name, so the server defaults apply', () => {
		const request = buildMergeRequest({
			rows: ROWS,
			selection: [11, 12],
			coverTemplateRef: '',
			bookmarks: true,
			name: ' ',
			host: { register: '', schema: '', objectId: '' },
		})

		expect(request.options).toEqual({ bookmarks: true })
		expect(request.hostObject).toEqual({})
	})

	it('drops a selected id that is no longer in the list', () => {
		const request = buildMergeRequest({
			rows: ROWS,
			selection: [99, 12],
			coverTemplateRef: '',
			bookmarks: true,
			name: '',
			host: { register: '', schema: '', objectId: '' },
		})

		expect(request.inputs).toEqual([{ fileId: 12, label: 'besluit.docx' }])
	})
})

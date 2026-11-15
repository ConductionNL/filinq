/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The selection behind the merge-to-PDF leaf: which documents go in, in what
 * order, and the request body `POST /apps/filinq/api/merge` reads.
 *
 * Kept free of Vue so the order and the request can be tested without
 * mounting anything, and so the leaf bundle stays thin.
 */

/**
 * Add a document to the end of the selection, or take it out.
 *
 * @param {number[]} selection The selected file ids, in merge order.
 * @param {number}   fileId    The document to toggle.
 *
 * @return {number[]} A new selection.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
export function toggleInSelection(selection, fileId) {
	if (selection.includes(fileId) === true) {
		return selection.filter((id) => id !== fileId)
	}

	return [...selection, fileId]
}

/**
 * Move one selected document up (-1) or down (+1); a move past an end is a no-op.
 *
 * @param {number[]} selection The selected file ids, in merge order.
 * @param {number}   index     The position of the document to move.
 * @param {number}   delta     -1 to move up, 1 to move down.
 *
 * @return {number[]} A new selection.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
export function moveInSelection(selection, index, delta) {
	const target = index + delta
	if (target < 0 || target >= selection.length) {
		return selection.slice()
	}

	const moved = selection.slice()
	const [item] = moved.splice(index, 1)
	moved.splice(target, 0, item)

	return moved
}

/**
 * Whether the selection is worth merging: one document is not a bundle.
 *
 * @param {number[]} selection The selected file ids.
 *
 * @return {boolean} True with two or more documents.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
export function canMerge(selection) {
	return selection.length >= 2
}

/**
 * The body for `POST /apps/filinq/api/merge`.
 *
 * No target folder is sent: the server writes the result beside the first
 * input, which on a case page is the case folder.
 *
 * @param {object}   args                  The dialog state.
 * @param {object[]} args.rows             The listed documents, `{fileId, name}`.
 * @param {number[]} args.selection        The selected file ids, in merge order.
 * @param {string}   args.coverTemplateRef The cover template, or ''.
 * @param {boolean}  args.bookmarks        Whether each document gets a bookmark.
 * @param {string}   args.name             The result name, or ''.
 * @param {object}   args.host             The host object `{register, schema, objectId}`.
 *
 * @return {{inputs: object[], options: object, hostObject: object}} The request body.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
export function buildMergeRequest({
	rows,
	selection,
	coverTemplateRef,
	bookmarks,
	name,
	host,
}) {
	const byId = new Map(rows.map((row) => [row.fileId, row]))
	const inputs = selection
		.filter((fileId) => byId.has(fileId))
		.map((fileId) => ({ fileId, label: byId.get(fileId).name }))

	const options = { bookmarks: bookmarks === true }
	if (coverTemplateRef !== '') {
		options.coverTemplateRef = coverTemplateRef
	}
	const trimmed = name.trim()
	if (trimmed !== '') {
		options.name = trimmed
	}

	const hostObject = {}
	if (host.objectId !== '') {
		hostObject.register = host.register
		hostObject.schema = host.schema
		hostObject.id = host.objectId
	}

	return { inputs, options, hostObject }
}

/**
 * Tell the AI companion which document or folder the page shows.
 *
 * CnAppRoot provides one reactive `cnAiContext` for the whole app. A page that
 * shows one file sets `pageKind: 'document'` and that file's id; a dossier
 * sets `pageKind: 'folder'` and its home folder's id. The companion then
 * offers "About this document" / "About this folder" and scopes the question
 * to it. On leaving, the page takes its file back out, so the next page does
 * not inherit a scope it never showed.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @see https://github.com/ConductionNL/hermiq/blob/development/openspec/changes/chat-about-a-document-cites-its-sources/tasks.md
 */

/**
 * The positive integer id in a value, else null.
 *
 * @param {number|string|null|undefined} value The value.
 * @return {number|null} The id.
 */
function idOf(value) {
	const number = typeof value === 'string' && /^\d+$/.test(value) ? Number(value) : value
	return Number.isInteger(number) && number > 0 ? number : null
}

/**
 * Point the companion at one document or folder, or at nothing when the id is empty.
 *
 * @param {object|null} context The injected `cnAiContext`, or null outside CnAppRoot.
 * @param {string} pageKind `document` or `folder`.
 * @param {number|string|null} fileId The file or folder id.
 * @return {void}
 */
export function pointCompanionAt(context, pageKind, fileId) {
	if (!context) {
		return
	}
	const id = idOf(fileId)
	if (id === null) {
		releaseCompanion(context)
		return
	}
	context.pageKind = pageKind
	context.fileId = id
}

/**
 * Take the page's file back out of the companion context.
 *
 * Only a context still pointing at a document or folder is cleared, so a
 * page that already set its own kind is left alone.
 *
 * @param {object|null} context The injected `cnAiContext`.
 * @return {void}
 */
export function releaseCompanion(context) {
	if (!context) {
		return
	}
	if (context.pageKind === 'document' || context.pageKind === 'folder') {
		context.pageKind = 'custom'
	}
	context.fileId = undefined
}

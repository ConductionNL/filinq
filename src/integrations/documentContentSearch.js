// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Searching the documents of one record by the words in them: the query the
// flat document list takes for it, the snippet split into marked and plain
// parts so the words show without rendering server text as HTML, and the
// note when the server cannot search content.

/**
 * The extra query parameters for a content search, or null for no search.
 *
 * @param {string} words What the user typed.
 * @return {{mode: string, search: string}|null} The parameters, or null when nothing was typed.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.2
 */
export function contentSearchQuery(words) {
	const search = String(words ?? '').trim()
	if (search === '') {
		return null
	}
	return { mode: 'content', search }
}

/**
 * Split a snippet into parts, marking each place one of the words occurs.
 * Matching ignores case; the snippet's own text is kept as written.
 *
 * @param {string} snippet The snippet the server found.
 * @param {string} words The words searched for.
 * @return {Array<{text: string, marked: boolean}>} The parts in order.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.2
 */
export function markedSnippet(snippet, words) {
	const text = String(snippet ?? '')
	const terms = String(words ?? '')
		.split(/\s+/)
		.filter((term) => term !== '')
		.map((term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
	if (text === '' || terms.length === 0) {
		return text === '' ? [] : [{ text, marked: false }]
	}
	const alternatives = terms.join('|')
	const whole = new RegExp('^(?:' + alternatives + ')$', 'i')
	return text
		.split(new RegExp('(' + alternatives + ')', 'i'))
		.filter((part) => part !== '')
		.map((part) => ({ text: part, marked: whole.test(part) }))
}

/**
 * Whether the answer says content could not be searched, so only names matched.
 *
 * @param {object} body The answer of the flat document list in content mode.
 * @return {boolean} True when the server has no content search.
 * @spec openspec/changes/work-content-search/tasks.md#task-2.2
 */
export function contentUnavailable(body) {
	return Boolean(body && body.content && body.content.available === false)
}

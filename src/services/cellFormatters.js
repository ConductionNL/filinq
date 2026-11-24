/**
 * Cell formatters for manifest index columns (`columns[].formatter`), for
 * values the library's built-in formatters do not write: a list of names, and
 * counts grouped by a few keys.
 *
 * Each formatter is `(value, row, property, options) => string`, the shape
 * CnAppRoot's `formatters` registry takes.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-verklaring-van-vernietiging-is-listed-and-permanent-req-ddare-005
 */

/**
 * A scalar as text; anything else as empty.
 *
 * @param {unknown} value The value.
 * @return {string} The text.
 */
function textOf(value) {
	if (typeof value === 'string') {
		return value.trim()
	}
	return typeof value === 'number' || typeof value === 'boolean'
		? String(value)
		: ''
}

/**
 * A list as one line, its entries joined by commas; empty entries left out.
 * A single value is written as it is.
 *
 * @param {unknown} value The list.
 * @return {string} The line.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-verklaring-van-vernietiging-is-listed-and-permanent-req-ddare-005
 */
export function formatJoinedList(value) {
	const entries = Array.isArray(value) ? value : [value]
	return entries
		.map(textOf)
		.filter((text) => text !== '')
		.join(', ')
}

/**
 * Counts grouped by a few keys as one line: each group's key values joined by
 * " / ", then its count, the groups separated by "; ".
 * `[{ schema: 12, classification: '1.1', count: 3 }]` with keys
 * `['schema', 'classification']` reads "12 / 1.1: 3".
 *
 * @param {unknown} value The groups.
 * @param {object} [_row] The full row (unused).
 * @param {object} [_property] The schema property (unused).
 * @param {{keys?: Array<string>, countKey?: string}} [options] The column's `formatterOptions`: which keys name a group (every key but the count by default) and which holds the count (`count`).
 * @return {string} The line, or empty when there are no groups.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-verklaring-van-vernietiging-is-listed-and-permanent-req-ddare-005
 */
export function formatGroupCounts(value, _row, _property, options = {}) {
	const countKey =
		typeof options?.countKey === 'string' && options.countKey !== ''
			? options.countKey
			: 'count'
	const declared = Array.isArray(options?.keys)
		? options.keys.filter((key) => typeof key === 'string')
		: null
	return (Array.isArray(value) ? value : [])
		.filter(
			(group) =>
				group !== null && typeof group === 'object' && !Array.isArray(group),
		)
		.map((group) => {
			const keys =
				declared ?? Object.keys(group).filter((key) => key !== countKey)
			const name = keys
				.map((key) => textOf(group[key]))
				.filter((text) => text !== '')
				.join(' / ')
			const count = textOf(group[countKey]) || '0'
			return name === '' ? count : name + ': ' + count
		})
		.join('; ')
}

/** The app's cell formatters, by the id a manifest column names. */
export const CELL_FORMATTERS = Object.freeze({
	joinedList: formatJoinedList,
	groupCounts: formatGroupCounts,
})

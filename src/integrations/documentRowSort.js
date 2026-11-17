/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

/**
 * The columns of the flat file list a user may sort on.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-the-flat-file-list-sorts-on-any-column-req-scr-005
 */
export const SORT_KEYS = ['name', 'mtime', 'size', 'status']

const NUMERIC = ['mtime', 'size']

/**
 * The value of one column of a row.
 *
 * @param {object} row One flat-list row.
 * @param {string} key The column.
 * @return {string|number} The value to compare.
 */
function valueOf(row, key) {
	if (key === 'status') {
		return row?.record?.status ?? ''
	}
	if (NUMERIC.includes(key)) {
		return Number(row?.[key] ?? 0) || 0
	}
	return String(row?.[key] ?? '')
}

/**
 * The rows in the chosen order, as a new array; equal values keep name order.
 *
 * @param {Array<object>} rows The flat-list rows.
 * @param {{key: string, direction: string}} sort The column and `asc` or `desc`.
 * @return {Array<object>} The sorted rows.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-the-flat-file-list-sorts-on-any-column-req-scr-005
 */
export function sortDocumentRows(rows, sort) {
	const key = SORT_KEYS.includes(sort?.key) ? sort.key : 'name'
	const sign = sort?.direction === 'desc' ? -1 : 1
	const compare = (a, b) => {
		const left = valueOf(a, key)
		const right = valueOf(b, key)
		if (typeof left === 'number') {
			return left - right
		}
		return left.localeCompare(right, undefined, { numeric: true, sensitivity: 'base' })
	}

	return [...(rows ?? [])].sort((a, b) => (sign * compare(a, b)) || valueOf(a, 'name').localeCompare(valueOf(b, 'name')))
}

/**
 * The order after the user chooses a column: the same column flips, another
 * column starts newest or largest first for dates and sizes, A to Z otherwise.
 *
 * @param {{key: string, direction: string}|null} current The order now.
 * @param {string} key The column chosen.
 * @return {{key: string, direction: string}} The next order.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-the-flat-file-list-sorts-on-any-column-req-scr-005
 */
export function nextSort(current, key) {
	if (current?.key === key) {
		return { key, direction: current.direction === 'asc' ? 'desc' : 'asc' }
	}
	return { key, direction: NUMERIC.includes(key) ? 'desc' : 'asc' }
}

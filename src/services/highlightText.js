/**
 * Pure text-highlighting helper.
 *
 * Splits a piece of rendered document text into segments around the entity
 * values that should be highlighted. Matching is by value (not by backend
 * offsets, which are not forwarded to the frontend) and case-insensitive,
 * preserving the original casing in the output.
 *
 * Longer values win: when two values would overlap (e.g. 'Jan' inside
 * 'Jan Jansen'), the longest is matched first and the shorter is skipped where
 * it overlaps — this is the same "do not let an earlier shorter value overwrite
 * a longer one" rule the manual-entity flow relies on.
 */

/**
 * Sentinel type for the pending selection the user is about to add as a manual
 * entity. Rendered with a distinct style instead of a per-type colour.
 *
 * @type {string}
 */
export const PENDING_TYPE = '__pending__'

/**
 * Build the highlight segments for a text.
 *
 * @param {string} text Rendered text to split.
 * @param {Array<{value: string, type: string}>} entities Values to highlight.
 * @return {Array<{text: string, type: (string|null)}>} Ordered segments;
 *         `type` is null for plain (non-highlighted) text.
 */
export function buildHighlightSegments(text, entities) {
	if (!text) {
		return []
	}

	// Longest first so a longer value claims its span before a shorter,
	// overlapping one gets a chance.
	const values = (entities || [])
		.filter((e) => e && e.value)
		.map((e) => ({ value: String(e.value), type: e.type }))
		.sort((a, b) => b.value.length - a.value.length)

	if (!values.length) {
		return [{ text, type: null }]
	}

	const lower = text.toLowerCase()
	const ranges = [] // { start, end, type }

	for (const { value, type } of values) {
		const needle = value.toLowerCase()
		if (!needle) {
			continue
		}
		let from = 0
		let idx = lower.indexOf(needle, from)
		while (idx !== -1) {
			const end = idx + needle.length
			// Skip occurrences that overlap an already-claimed (longer) range.
			const overlaps = ranges.some((r) => idx < r.end && end > r.start)
			if (!overlaps) {
				ranges.push({ start: idx, end, type })
			}
			from = end
			idx = lower.indexOf(needle, from)
		}
	}

	if (!ranges.length) {
		return [{ text, type: null }]
	}

	ranges.sort((a, b) => a.start - b.start)

	const segments = []
	let cursor = 0
	for (const r of ranges) {
		if (r.start > cursor) {
			segments.push({ text: text.slice(cursor, r.start), type: null })
		}
		segments.push({ text: text.slice(r.start, r.end), type: r.type })
		cursor = r.end
	}
	if (cursor < text.length) {
		segments.push({ text: text.slice(cursor), type: null })
	}

	return segments
}

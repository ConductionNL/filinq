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
 * The values to match, longest first so a longer value claims its span before
 * a shorter, overlapping one gets a chance.
 *
 * @param {Array<{value: string, type: string}>} entities Values to highlight.
 * @return {Array<{value: string, type: string}>}
 */
function valuesLongestFirst(entities) {
	return (entities || [])
		.filter((e) => e && e.value)
		.map((e) => ({ value: String(e.value), type: e.type }))
		.sort((a, b) => b.value.length - a.value.length)
}

/**
 * Claim every occurrence of one value in the lower-cased text, skipping
 * occurrences that overlap an already-claimed (longer) range.
 *
 * @param {{lower: string, ranges: Array<{start: number, end: number, type: string}>}} claim
 *        The lower-cased text and the ranges claimed so far; new ranges are appended.
 * @param {{value: string, type: string}} entity The value to claim.
 * @return {void}
 */
function claimOccurrences(claim, { value, type }) {
	const needle = value.toLowerCase()
	if (!needle) {
		return
	}
	let idx = claim.lower.indexOf(needle, 0)
	while (idx !== -1) {
		const end = idx + needle.length
		const overlaps = claim.ranges.some((r) => idx < r.end && end > r.start)
		if (!overlaps) {
			claim.ranges.push({ start: idx, end, type })
		}
		idx = claim.lower.indexOf(needle, end)
	}
}

/**
 * Cut the text into plain and highlighted segments along sorted ranges.
 *
 * @param {string} text The text to cut.
 * @param {Array<{start: number, end: number, type: string}>} ranges Non-overlapping ranges, sorted by start.
 * @return {Array<{text: string, type: (string|null)}>}
 */
function segmentsAlong(text, ranges) {
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
	const claim = { lower: text.toLowerCase(), ranges: [] }
	for (const entity of valuesLongestFirst(entities)) {
		claimOccurrences(claim, entity)
	}
	if (!claim.ranges.length) {
		return [{ text, type: null }]
	}
	claim.ranges.sort((a, b) => a.start - b.start)
	return segmentsAlong(text, claim.ranges)
}

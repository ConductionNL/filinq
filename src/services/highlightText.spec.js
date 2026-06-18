import { buildHighlightSegments, PENDING_TYPE } from './highlightText.js'

describe('buildHighlightSegments', () => {
	it('returns nothing for empty text', () => {
		expect(buildHighlightSegments('', [{ value: 'x', type: 'PERSON' }])).toEqual([])
	})

	it('returns a single plain segment when there are no entities', () => {
		expect(buildHighlightSegments('hello world', [])).toEqual([
			{ text: 'hello world', type: null },
		])
	})

	it('wraps a single match and keeps the surrounding text plain', () => {
		const segments = buildHighlightSegments('Hi Kuipers!', [{ value: 'Kuipers', type: 'PERSON' }])
		expect(segments).toEqual([
			{ text: 'Hi ', type: null },
			{ text: 'Kuipers', type: 'PERSON' },
			{ text: '!', type: null },
		])
	})

	it('matches case-insensitively but preserves the original casing', () => {
		const segments = buildHighlightSegments('see KUIPERS now', [{ value: 'kuipers', type: 'PERSON' }])
		expect(segments).toEqual([
			{ text: 'see ', type: null },
			{ text: 'KUIPERS', type: 'PERSON' },
			{ text: ' now', type: null },
		])
	})

	it('matches every occurrence', () => {
		const segments = buildHighlightSegments('a a', [{ value: 'a', type: 'OTHER' }])
		expect(segments.filter((s) => s.type === 'OTHER')).toHaveLength(2)
	})

	it('lets the longest value win on overlap (Jan vs Jan Jansen)', () => {
		const segments = buildHighlightSegments('Jan Jansen', [
			{ value: 'Jan', type: 'PERSON' },
			{ value: 'Jan Jansen', type: 'PERSON' },
		])
		// The whole "Jan Jansen" is one segment; the shorter "Jan" does not
		// carve it up.
		expect(segments).toEqual([{ text: 'Jan Jansen', type: 'PERSON' }])
	})

	it('marks the pending selection with the sentinel type', () => {
		const segments = buildHighlightSegments('pick Amsterdam', [
			{ value: 'Amsterdam', type: PENDING_TYPE },
		])
		expect(segments).toContainEqual({ text: 'Amsterdam', type: PENDING_TYPE })
	})
})

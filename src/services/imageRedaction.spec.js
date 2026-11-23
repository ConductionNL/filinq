/**
 * Tests for the image redaction helpers.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import {
	applyImageFlags,
	burnedRegionLine,
	decideScope,
	fetchScope,
	imageExtractionWarning,
	imageRedactionWarning,
	isImageEntity,
	regionLabel,
	regionOverlays,
	scopeLabel,
} from './imageRedaction.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (m, k) => (vars && k in vars ? String(vars[k]) : m)),
	translatePlural: (app, one, many, count) => (count === 1 ? one : many).replace('%n', String(count)),
}))

// @spec openspec/changes/image-redaction/tasks.md#task-4.2
describe('imageExtractionWarning', () => {
	it('says images were not checked when there is no image detection', () => {
		expect(imageExtractionWarning({ imageDetectionSkipped: { reason: 'backend_unavailable' } })).toMatch(/no image detection is available/)
	})

	it('says images could not be checked for any other reason', () => {
		expect(imageExtractionWarning({ imageDetectionSkipped: { reason: 'not_detected' } })).toMatch(/could not be checked/)
	})

	it('says signatures were not looked for when no detector ran', () => {
		expect(imageExtractionWarning({ objectDetection: 'not-run' })).toMatch(/Signature detection is not available/)
	})

	it('is silent when the images were looked at', () => {
		expect(imageExtractionWarning({ objectDetection: 'ran' })).toBeNull()
		expect(imageExtractionWarning(null)).toBeNull()
	})
})

describe('imageRedactionWarning', () => {
	it('warns when image redaction is pending', () => {
		expect(imageRedactionWarning({ imageRedactionPending: true })).toMatch(/not redacted/)
		expect(imageRedactionWarning({ imageRedactionPending: false })).toBeNull()
	})
})

describe('applyImageFlags', () => {
	it('copies the warning and reports unseen image content', () => {
		const entry = {}
		expect(applyImageFlags(entry, { imageDetectionSkipped: { reason: 'backend_unavailable' } })).toBe(true)
		expect(entry.imageWarning).toMatch(/not checked/)
	})

	it('does not report a document whose images were looked at as unseen', () => {
		const entry = {}
		expect(applyImageFlags(entry, { objectDetection: 'not-run' })).toBe(false)
		expect(entry.imageWarning).toMatch(/Signature/)
	})
})

describe('scopeLabel and isImageEntity', () => {
	it('labels each scope', () => {
		expect(scopeLabel({ scope: 'document' })).toBe('Whole document withheld')
		expect(scopeLabel({ scope: 'page', fromPage: 3, toPage: 3 })).toBe('Page 3 redacted')
		expect(scopeLabel({ scope: 'pageRange', fromPage: 3, toPage: 5 })).toBe('Pages 3 to 5 redacted')
	})

	it('knows an image row', () => {
		expect(isImageEntity({ origin: 'image' })).toBe(true)
		expect(isImageEntity({ origin: 'text' })).toBe(false)
	})
})

// @spec openspec/changes/image-redaction/tasks.md#task-6.3
describe('decideScope', () => {
	beforeEach(() => {
		axios.post.mockReset()
		axios.get.mockReset()
	})

	it('stores a page range and commits it with the same body', async () => {
		axios.post.mockResolvedValueOnce({ data: { scope: 'pageRange', fromPage: 3, toPage: 5 } })
		axios.post.mockResolvedValueOnce({ data: { withheld: false, burnedPages: [3, 4, 5] } })

		const answer = await decideScope(7, { scope: 'pageRange', fromPage: '3', toPage: '5', bases: ['5.1.2.e'] }, true)

		const sent = { scope: 'pageRange', bases: ['5.1.2.e'], fromPage: 3, toPage: 5 }
		expect(axios.post).toHaveBeenNthCalledWith(1, '/apps/filinq/api/anonymization/scope/7', sent)
		expect(axios.post).toHaveBeenNthCalledWith(2, '/apps/filinq/api/anonymization/scope/7/commit', sent)
		expect(answer).toEqual({ ok: true, decision: { scope: 'pageRange', fromPage: 3, toPage: 5 }, outcome: { withheld: false, burnedPages: [3, 4, 5] } })
	})

	it('sends no pages for a whole document', async () => {
		axios.post.mockResolvedValueOnce({ data: { scope: 'document' } })

		await decideScope(7, { scope: 'document', fromPage: 2, bases: ['5.1.1.c'] })

		expect(axios.post).toHaveBeenCalledWith('/apps/filinq/api/anonymization/scope/7', { scope: 'document', bases: ['5.1.1.c'] })
	})

	it('carries the server refusal', async () => {
		axios.post.mockRejectedValueOnce({ response: { data: { error: 'The document has 4 pages, so pages 3 to 6 cannot be redacted.' } } })

		const answer = await decideScope(7, { scope: 'pageRange', fromPage: 3, toPage: 6, bases: ['5.1.2.e'] })

		expect(answer).toEqual({ ok: false, error: 'The document has 4 pages, so pages 3 to 6 cannot be redacted.' })
	})

	it('reads the scope state', async () => {
		axios.get.mockResolvedValueOnce({ data: { decisions: [], pageCount: 4, pageScopesAvailable: false } })

		expect(await fetchScope(7)).toEqual({ decisions: [], pageCount: 4, pageScopesAvailable: false })
	})
})

// @spec openspec/changes/image-redaction/tasks.md#task-4.1
describe('regionOverlays', () => {
	const box = (extra = {}) => ({ page: 2, x: 0.1, y: 0.2, w: 0.3, h: 0.05, ...extra })

	it('turns every box of an image-origin row into an overlay tied to the row index', () => {
		const entities = [
			{ type: 'PERSON', value: 'Jansen' },
			{ type: 'BSN', value: '123', origin: 'image', boxes: [box(), box({ page: 3 })] },
		]
		expect(regionOverlays(entities)).toEqual([
			{ index: 1, page: 2, x: 0.1, y: 0.2, w: 0.3, h: 0.05, type: 'BSN', state: 'redact' },
			{ index: 1, page: 3, x: 0.1, y: 0.2, w: 0.3, h: 0.05, type: 'BSN', state: 'redact' },
		])
	})

	it('mirrors a rejected row as a region kept visible', () => {
		const entities = [{ type: 'SIGNATURE', origin: 'image', boxes: [box()], _decisionSkip: true }]
		expect(regionOverlays(entities)[0].state).toBe('keep')
	})

	it('ignores text-origin rows even when they carry boxes', () => {
		expect(regionOverlays([{ type: 'PERSON', boxes: [box()] }])).toEqual([])
	})

	it('leaves out a box it cannot place, without dropping the other boxes', () => {
		const entities = [{
			type: 'BSN',
			origin: 'image',
			boxes: [box({ page: null }), box({ page: 0 }), box({ x: 'a' }), box({ w: 0 }), null, box()],
		}]
		const overlays = regionOverlays(entities)
		expect(overlays).toHaveLength(1)
		expect(overlays[0].page).toBe(2)
	})

	it('clamps a box to the page', () => {
		const [overlay] = regionOverlays([{ type: 'BSN', origin: 'image', boxes: [box({ x: 0.9, y: -0.5, w: 0.5, h: 2 })] }])
		expect(overlay).toMatchObject({ x: 0.9, y: 0, h: 1 })
		expect(overlay.w).toBeCloseTo(0.1)
	})

	it('answers an empty list for anything but a list', () => {
		expect(regionOverlays(null)).toEqual([])
		expect(regionOverlays({})).toEqual([])
	})
})

// @spec openspec/changes/image-redaction/tasks.md#task-4.1
describe('regionLabel', () => {
	it('names the type and whether the region is redacted or kept', () => {
		expect(regionLabel({ type: 'BSN', state: 'redact' })).toBe('Image region to be redacted: BSN')
		expect(regionLabel({ type: 'SIGNATURE', state: 'keep' })).toBe('Image region kept visible: SIGNATURE')
	})

	it('falls back to an unknown type', () => {
		expect(regionLabel({ type: '', state: 'redact' })).toBe('Image region to be redacted: Unknown')
	})
})

// @spec openspec/changes/image-redaction/tasks.md#task-4.2
describe('burnedRegionLine', () => {
	it('counts the regions the seam burned', () => {
		expect(burnedRegionLine({ burnedRegionCount: 1 })).toBe('1 image region burned')
		expect(burnedRegionLine({ burnedRegionCount: 3 })).toBe('3 image regions burned')
	})

	it('shows nothing when no region was burned', () => {
		expect(burnedRegionLine({ burnedRegionCount: 0 })).toBeNull()
		expect(burnedRegionLine({ burnedRegionCount: null })).toBeNull()
		expect(burnedRegionLine({})).toBeNull()
		expect(burnedRegionLine(null)).toBeNull()
	})

	it('never shows a count that is not a whole number of regions', () => {
		expect(burnedRegionLine({ burnedRegionCount: 'many' })).toBeNull()
		expect(burnedRegionLine({ burnedRegionCount: 2.5 })).toBeNull()
		expect(burnedRegionLine({ burnedRegionCount: -1 })).toBeNull()
	})
})

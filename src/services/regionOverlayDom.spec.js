/**
 * Tests for drawing the image-region overlays of a page.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import { applyRegionOverlays } from './regionOverlayDom.js'

jest.mock('@nextcloud/axios', () => ({
	__esModule: true,
	default: { get: jest.fn(), post: jest.fn() },
}))
jest.mock('@nextcloud/router', () => ({
	generateUrl: (path) => path,
}))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (m, k) => (vars && k in vars ? String(vars[k]) : m)),
}))

const regions = [
	{ index: 4, page: 1, x: 0.1, y: 0.25, w: 0.5, h: 0.1, type: 'BSN', state: 'redact' },
	{ index: 7, page: 2, x: 0, y: 0, w: 1, h: 0.5, type: 'SIGNATURE', state: 'keep' },
	{ index: 9, page: 1, x: 0.6, y: 0.7, w: 0.2, h: 0.2, type: 'PERSON', state: 'keep' },
]

// @spec openspec/changes/image-redaction/tasks.md#task-4.1
describe('applyRegionOverlays', () => {
	it('draws only the regions of its page, placed in page percentages', () => {
		const layer = document.createElement('div')
		expect(applyRegionOverlays(layer, regions, 1)).toBe(2)

		const [first, second] = layer.children
		expect(first.className).toBe('dd-region dd-region--redact')
		expect(first.style.left).toBe('10%')
		expect(first.style.top).toBe('25%')
		expect(first.style.width).toBe('50%')
		expect(first.style.height).toBe('10%')
		expect(first.getAttribute('data-entity-index')).toBe('4')
		expect(first.getAttribute('role')).toBe('img')
		expect(first.getAttribute('aria-label')).toBe('Image region to be redacted: BSN')
		expect(second.className).toBe('dd-region dd-region--keep')
		expect(second.getAttribute('aria-label')).toBe('Image region kept visible: PERSON')
	})

	it('replaces what it drew before, so a changed decision redraws the region', () => {
		const layer = document.createElement('div')
		applyRegionOverlays(layer, regions, 1)
		const changed = regions.map((r) => (r.index === 4 ? { ...r, state: 'keep' } : r))
		applyRegionOverlays(layer, changed, 1)
		expect(layer.children).toHaveLength(2)
		expect(layer.children[0].className).toBe('dd-region dd-region--keep')
	})

	it('clears the layer when no region is on the page, and ignores a missing layer', () => {
		const layer = document.createElement('div')
		applyRegionOverlays(layer, regions, 1)
		expect(applyRegionOverlays(layer, regions, 5)).toBe(0)
		expect(layer.children).toHaveLength(0)
		expect(applyRegionOverlays(null, regions, 1)).toBe(0)
		expect(applyRegionOverlays(layer, undefined, 1)).toBe(0)
	})
})

/**
 * Draw the image-region overlays of one page into its overlay layer.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The layer is an absolutely positioned element covering the rendered page;
 * each region is placed in percentages of the page, so it follows the page at
 * any zoom. The overlays only show the table's decisions; they hold none.
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-image-regions-review-through-the-existing-workbench-model-req-ddimr-005
 */

import { regionLabel } from './imageRedaction.js'

export const REGION_CLASS = 'dd-region'

/**
 * Replace the overlays in a page's layer with the regions of that page.
 *
 * @param {HTMLElement|null} layer The page's overlay layer.
 * @param {Array<object>} regions Every overlay of the document (regionOverlays()).
 * @param {number} page The 1-based page number of the layer.
 * @return {number} The number of overlays drawn.
 * @spec openspec/changes/image-redaction/tasks.md#task-4.1
 */
export function applyRegionOverlays(layer, regions, page) {
	if (!layer) {
		return 0
	}
	layer.replaceChildren()
	let drawn = 0
	for (const region of Array.isArray(regions) ? regions : []) {
		if (region.page !== page) {
			continue
		}
		const element = document.createElement('div')
		element.className = `${REGION_CLASS} ${REGION_CLASS}--${region.state}`
		element.style.left = `${region.x * 100}%`
		element.style.top = `${region.y * 100}%`
		element.style.width = `${region.w * 100}%`
		element.style.height = `${region.h * 100}%`
		element.setAttribute('data-entity-index', String(region.index))
		element.setAttribute('role', 'img')
		element.setAttribute('aria-label', regionLabel(region))
		element.title = regionLabel(region)
		layer.appendChild(element)
		drawn++
	}
	return drawn
}

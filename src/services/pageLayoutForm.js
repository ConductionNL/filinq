/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The page layout admin form, free of Vue: filling it from a stored layout,
 * the fields it sends to `api/page-layouts`, and the two pages its preview
 * draws. The version fields belong to `PageLayoutService` and are never sent.
 */

const DEFAULT_MARGINS = { top: 25, right: 20, bottom: 25, left: 20 }

/**
 * The form state for a stored layout, or for a new one.
 *
 * @param {object} layout A stored `pageLayout`, or `{}`.
 *
 * @return {object} The form state.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
export function formFromLayout(layout) {
	const source = layout || {}
	return {
		name: source.name || '',
		paperSize: source.paperSize || 'A4',
		orientation: source.orientation || 'portrait',
		margins: { ...DEFAULT_MARGINS, ...(source.margins || {}) },
		header: source.header || '',
		footer: source.footer || '',
		firstPageDiffers: source.firstPageDiffers === true,
		firstPageHeader: source.firstPageHeader || '',
		firstPageFooter: source.firstPageFooter || '',
	}
}

/**
 * The fields the form sends, as the create and edit endpoints take them.
 *
 * @param {object} form The form state.
 *
 * @return {object} The layout fields.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
export function layoutPayload(form) {
	const margins = {}
	for (const side of ['top', 'right', 'bottom', 'left']) {
		margins[side] = Number(form.margins[side] || 0)
	}

	return {
		paperSize: form.paperSize,
		orientation: form.orientation,
		margins,
		header: form.header,
		footer: form.footer,
		firstPageDiffers: form.firstPageDiffers === true,
		firstPageHeader: form.firstPageHeader,
		firstPageFooter: form.firstPageFooter,
	}
}

/**
 * The first and a following page, as the preview draws them.
 *
 * @param {object} form The form state.
 *
 * @return {Array<{kind: string, header: string, footer: string}>} Two pages.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
export function previewPages(form) {
	const following = { kind: 'following', header: form.header, footer: form.footer }
	const first =
		form.firstPageDiffers === true
			? {
					kind: 'first',
					header: form.firstPageHeader,
					footer: form.firstPageFooter,
				}
			: { kind: 'first', header: form.header, footer: form.footer }

	return [first, following]
}

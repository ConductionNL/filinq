/**
 * The page layout admin form: what it sends, and what the two-page preview shows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The payload keys are the `pageLayout` properties `PageLayoutService::pdfOptions()`
 * reads; the version fields are the service's and are never sent.
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	formFromLayout,
	layoutPayload,
	previewPages,
} from '../../src/services/pageLayoutForm.js'

const STORED = {
	uuid: 'layout-2',
	name: 'Gemeente, besluit',
	layoutVersion: 2,
	supersedes: 'layout-1',
	active: true,
	paperSize: 'A4',
	orientation: 'portrait',
	margins: { top: 35, right: 20, bottom: 25, left: 25 },
	header: 'Gemeente',
	footer: 'Pagina {{page}}',
	firstPageDiffers: true,
	firstPageHeader: '',
	firstPageFooter: 'Postbus 1',
}

describe('pageLayoutForm', () => {
	it('fills the form from a stored layout, with defaults for what is missing', () => {
		const form = formFromLayout({ name: 'Kaal' })
		expect(form.paperSize).toBe('A4')
		expect(form.orientation).toBe('portrait')
		expect(form.margins).toEqual({ top: 25, right: 20, bottom: 25, left: 20 })
		expect(form.firstPageDiffers).toBe(false)
		expect(formFromLayout(STORED).margins.top).toBe(35)
	})

	it('sends the layout fields and never the version bookkeeping', () => {
		const payload = layoutPayload({
			...formFromLayout(STORED),
			margins: { top: '30', right: 20, bottom: 25, left: 25 },
		})

		expect(payload).toEqual({
			paperSize: 'A4',
			orientation: 'portrait',
			margins: { top: 30, right: 20, bottom: 25, left: 25 },
			header: 'Gemeente',
			footer: 'Pagina {{page}}',
			firstPageDiffers: true,
			firstPageHeader: '',
			firstPageFooter: 'Postbus 1',
		})
		expect(payload).not.toHaveProperty('layoutVersion')
		expect(payload).not.toHaveProperty('supersedes')
		expect(payload).not.toHaveProperty('name')
	})

	it('previews a different first page only when the layout says so', () => {
		const pages = previewPages(formFromLayout(STORED))
		expect(pages).toEqual([
			{ kind: 'first', header: '', footer: 'Postbus 1' },
			{ kind: 'following', header: 'Gemeente', footer: 'Pagina {{page}}' },
		])

		const same = previewPages(
			formFromLayout({ ...STORED, firstPageDiffers: false }),
		)
		expect(same[0]).toEqual({
			kind: 'first',
			header: 'Gemeente',
			footer: 'Pagina {{page}}',
		})
	})
})

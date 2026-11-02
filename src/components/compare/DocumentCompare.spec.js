/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * DocumentCompare renders the original and the delivered file side by side,
 * each in the file viewer's own viewer, with the host's labels, and says so
 * when a type cannot be previewed (anonymization-review-workbench REQ-DDARW-014).
 * The four viewers are replaced by markers that echo their props, so the test
 * reads what each pane hands its viewer without pdfjs or mammoth.
 */

import { createApp } from 'vue'
import DocumentCompare from './DocumentCompare.vue'

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))

/**
 * A stand-in viewer that prints its name and props.
 *
 * @param {string} name The viewer's name.
 * @return {object} A component.
 */
function mockViewer(name) {
	return {
		name,
		props: {
			path: { type: String, default: '' },
			url: { type: String, default: '' },
		},
		render() {
			return require('vue').h('div', {
				class: 'viewer',
				'data-viewer': name,
				'data-url': this.url,
				'data-path': this.path,
			})
		},
	}
}

// The routing's EML preview URL lives in the file service, which pulls pdfjs.
jest.mock('../../services/fileViewerService.js', () => ({
	emlPreviewUrl: (fileId) => `/eml/${fileId}`,
}))
jest.mock('../viewers/PdfViewer.vue', () => mockViewer('PdfViewer'))
jest.mock('../viewers/WordViewer.vue', () => mockViewer('WordViewer'))
jest.mock('../viewers/OdtViewer.vue', () => mockViewer('OdtViewer'))
jest.mock('../viewers/TextViewer.vue', () => mockViewer('TextViewer'))

/**
 * Mount the component into a fresh element.
 *
 * @param {object} props Props.
 * @return {HTMLElement}
 */
function render(props) {
	const el = document.createElement('div')
	document.body.appendChild(el)
	createApp(DocumentCompare, props).mount(el)
	return el
}

const original = {
	fileName: 'besluit.pdf',
	mimeType: 'application/pdf',
	url: '/x/original',
}
const delivered = {
	fileName: 'besluit-gelakt.docx',
	mimeType:
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	url: '/x/delivered',
}

describe('DocumentCompare', () => {
	it('shows the original first and the delivered file second, each in its own viewer', () => {
		const el = render({ original, delivered })
		const panes = el.querySelectorAll('.document-compare__pane')
		expect(panes).toHaveLength(2)
		expect(panes[0].querySelector('.viewer').dataset.viewer).toBe('PdfViewer')
		expect(panes[0].querySelector('.viewer').dataset.url).toBe('/x/original')
		expect(panes[1].querySelector('.viewer').dataset.viewer).toBe('WordViewer')
		expect(panes[1].querySelector('.viewer').dataset.url).toBe('/x/delivered')
		expect(panes[0].textContent).toContain('Original')
		expect(panes[1].textContent).toContain('Delivered')
		expect(panes[1].textContent).toContain('besluit-gelakt.docx')
	})

	it("uses the host's labels when it passes them", () => {
		const el = render({
			original,
			delivered,
			labels: { original: 'Origineel', delivered: 'Geleverd' },
		})
		const titles = [...el.querySelectorAll('.document-compare__title')].map(
			(n) => n.textContent.trim(),
		)
		expect(titles).toEqual(['Origineel', 'Geleverd'])
	})

	it('says a type cannot be previewed and still offers the download', () => {
		const el = render({
			original,
			delivered: {
				fileName: 'plan.dwg',
				mimeType: 'image/vnd.dwg',
				url: '/x/plan',
			},
		})
		const pane = el.querySelector('[data-testid="document-compare-delivered"]')
		expect(pane.querySelector('.viewer')).toBeNull()
		expect(pane.textContent).toContain('This file type cannot be previewed.')
		expect(pane.querySelector('a[download]').getAttribute('href')).toBe(
			'/x/plan',
		)
	})
})

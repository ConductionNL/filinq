/**
 * The OCR helpers: which files are offered OCR, the badge, and the warning a
 * scan detection could not read carries into the review.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.2
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'
import {
	applyOcrFlags,
	fetchOcrStatus,
	isOcrCandidate,
	ocrBadgeLabel,
	ocrErrorMessage,
	ocrExtractionWarning,
	runOcr,
} from '../../src/services/ocr.js'

const calls = []
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config])
			return Promise.resolve({
				data: { capability: { available: true }, results: {} },
			})
		},
		post: (url) => {
			calls.push(['post', url])
			return Promise.resolve({
				data: { ocrProcessed: true, confidence: 91.4 },
			})
		},
	},
}))

describe('the calls', () => {
	it('posts to the file and asks for many files in one request', async () => {
		await runOcr(812004)
		await fetchOcrStatus([1, 2])
		expect(calls[0]).toEqual(['post', '/index.php/apps/filinq/api/ocr/812004'])
		expect(calls[1][1]).toBe('/index.php/apps/filinq/api/ocr')
		expect(calls[1][2]).toEqual({ params: { fileIds: '1,2' } })
	})
})

describe('isOcrCandidate', () => {
	it('offers OCR on images and PDFs only', () => {
		expect(isOcrCandidate('application/pdf')).toBe(true)
		expect(isOcrCandidate('image/TIFF')).toBe(true)
		expect(
			isOcrCandidate(
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			),
		).toBe(false)
		expect(isOcrCandidate(undefined)).toBe(false)
	})
})

describe('ocrBadgeLabel', () => {
	it('shows the rounded confidence, and nothing when OCR never ran', () => {
		expect(ocrBadgeLabel({ confidence: 91.4 })).toBe('OCR 91%')
		expect(ocrBadgeLabel(null)).toBe('')
	})
})

describe('ocrErrorMessage', () => {
	it('shows the reason the server gave', () => {
		const error = {
			response: {
				status: 409,
				data: { error: 'OCR is switched off by an administrator.' },
			},
		}
		expect(ocrErrorMessage(error)).toBe(
			'OCR is switched off by an administrator.',
		)
		expect(
			ocrErrorMessage({
				ocrProcessed: false,
				error: 'OCR found no text in this file.',
			}),
		).toBe('OCR found no text in this file.')
	})
})

describe('ocrExtractionWarning and applyOcrFlags', () => {
	it('warns when OCR ran but detection could not read the text', () => {
		const entry = {}
		expect(
			applyOcrFlags(entry, { entities: [], ocrDetectionPending: true }),
		).toBe(true)
		expect(entry.ocrWarning).toContain('entity detection could not check it')
	})

	it('names why a scan was not checked', () => {
		expect(ocrExtractionWarning({ ocrSkipped: 'ocr_disabled' })).toContain(
			'switched off',
		)
		expect(
			ocrExtractionWarning({ ocrSkipped: 'tesseract_unavailable' }),
		).toContain('not installed')
		expect(ocrExtractionWarning({ ocrSkipped: 'something_new' })).toContain(
			'failed',
		)
	})

	it('says nothing about a document detection saw in full', () => {
		const entry = {}
		expect(
			applyOcrFlags(entry, { entities: [], ocrDetectionPending: false }),
		).toBe(false)
		expect(entry.ocrWarning).toBeNull()
	})
})

describe('the surfaces', () => {
	it('offer Run OCR on My documents and the file viewer, and show the warning in the review', () => {
		expect(
			readFileSync('src/views/myDocuments/MyDocumentsIndex.vue', 'utf8'),
		).toContain('runOcrOn(row)')
		expect(
			readFileSync('src/views/fileViewer/FileViewerPage.vue', 'utf8'),
		).toContain('@click="runOcrNow"')
		expect(readFileSync('src/sidebars/FileViewerSidebar.vue', 'utf8')).toContain(
			'entry.ocrWarning',
		)
	})
})

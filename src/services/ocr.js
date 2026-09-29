/**
 * OCR: the calls the pages make, and what an extraction result says about a scan.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server decides whether OCR can run and answers with a reason when it
 * cannot. These helpers only carry that answer to the screen.
 *
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The MIME types OCR reads: images, and PDFs.
 *
 * @type {string[]}
 */
export const OCR_MIME_TYPES = [
	'image/png',
	'image/jpeg',
	'image/tiff',
	'image/bmp',
	'image/gif',
	'application/pdf',
]

const base = () => generateUrl('/apps/filinq/api/ocr')

/**
 * Whether OCR is offered for a file of this type.
 *
 * @param {string} mimeType The MIME type.
 * @return {boolean} True for an image or a PDF.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */
export function isOcrCandidate(mimeType) {
	return OCR_MIME_TYPES.includes((mimeType || '').toLowerCase())
}

/**
 * Whether OCR can run here, and the last result for each file asked about.
 *
 * @param {Array<number|string>} fileIds The files.
 * @return {Promise<{capability: object, results: object}>} The answer.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */
export async function fetchOcrStatus(fileIds = []) {
	const response = await axios.get(base(), {
		params: { fileIds: fileIds.join(',') },
	})
	return response.data
}

/**
 * Run OCR on a file. Rejects with the server's reason when it cannot run.
 *
 * @param {number|string} fileId The file.
 * @return {Promise<object>} The result: ocrProcessed, confidence, textLength.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */
export async function runOcr(fileId) {
	const response = await axios.post(base() + '/' + encodeURIComponent(fileId))
	return response.data
}

/**
 * The message to show for a failed or refused run.
 *
 * @param {object} error The axios error, or a 200 answer with ocrProcessed false.
 * @return {string} The message.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */
export function ocrErrorMessage(error) {
	const data = error?.response?.data ?? error
	return data?.error || t('filinq', 'OCR failed on this file.')
}

/**
 * The badge text for a file's last OCR result.
 *
 * @param {object|null} result The result, or null when OCR never ran.
 * @return {string} E.g. "OCR 91%", or '' when there is no result.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.1
 */
export function ocrBadgeLabel(result) {
	if (!result || typeof result.confidence !== 'number') {
		return ''
	}
	return t('filinq', 'OCR {confidence}%', {
		confidence: Math.round(result.confidence),
	})
}

/**
 * What an extraction result says about a scan detection could not read.
 *
 * @param {object} data The extract endpoint's answer.
 * @return {string|null} The warning, or null when detection saw the whole document.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.2
 */
export function ocrExtractionWarning(data) {
	if (!data) {
		return null
	}
	if (data.ocrDetectionPending === true) {
		return t(
			'filinq',
			'This document is a scan. OCR read its text, but entity detection could not check it yet. Review it by hand before you publish.',
		)
	}
	const reasons = {
		ocr_disabled: t(
			'filinq',
			'This document is a scan and OCR is switched off, so it was not checked for personal data.',
		),
		tesseract_unavailable: t(
			'filinq',
			'This document is a scan and OCR is not installed, so it was not checked for personal data.',
		),
		no_text_recovered: t(
			'filinq',
			'This document is a scan and OCR found no text in it, so it was not checked for personal data.',
		),
		ocr_failed: t(
			'filinq',
			'This document is a scan and OCR failed on it, so it was not checked for personal data.',
		),
	}
	if (data.ocrSkipped) {
		return reasons[data.ocrSkipped] ?? reasons.ocr_failed
	}
	return null
}

/**
 * Copy the OCR warning onto a queue entry.
 *
 * @param {object} entry The queue entry.
 * @param {object} data The extract endpoint's answer.
 * @return {boolean} True when detection did not see the document, so an empty
 *                   entity list must not mark it done.
 * @spec openspec/changes/ocr-trigger-surface/tasks.md#task-3.2
 */
export function applyOcrFlags(entry, data) {
	entry.ocrWarning = ocrExtractionWarning(data)
	return entry.ocrWarning !== null
}

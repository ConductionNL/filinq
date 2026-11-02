// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Running OCR from a page: the toast that says how it went, and switching the
// page's OCR action off when the server says OCR cannot run here. A page using
// it has an `ocrAvailable` data property.

import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { ocrErrorMessage, runOcr } from '../services/ocr.js'

export const ocrRun = {
	methods: {
		/**
		 * Run OCR on a file and tell the user how it went. A refusal with 409
		 * or 503 means OCR cannot run here, so `ocrAvailable` turns false.
		 *
		 * @param {number|string} fileId The file.
		 * @return {Promise<object|null>} The result when OCR read the file, else null.
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		async runOcrAndReport(fileId) {
			try {
				const result = await runOcr(fileId)
				if (result.ocrProcessed) {
					showSuccess(
						t('filinq', 'OCR read {length} characters.', {
							length: result.textLength,
						}),
					)
					return result
				}
				showError(ocrErrorMessage(result))
			} catch (error) {
				showError(ocrErrorMessage(error))
				if ([409, 503].includes(error?.response?.status)) {
					this.ocrAvailable = false
				}
			}
			return null
		},
	},
}

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Lazy-load pdfjs-dist plus its worker. The module is heavy (~2MB), so it is
 * pulled in only when a PDF is actually shown, and loaded once.
 */

let pdfjsLibPromise = null

/**
 * The pdfjs module, with its worker configured.
 *
 * @return {Promise<object>} pdfjsLib module.
 * @spec openspec/specs/document-preview/spec.md#requirement-format-specific-in-app-document-preview-req-ddprv-001
 */
export function loadPdfjs() {
	if (!pdfjsLibPromise) {
		pdfjsLibPromise = (async () => {
			const pdfjsLib = await import('pdfjs-dist/build/pdf.mjs')
			const workerUrl = new URL(
				'pdfjs-dist/build/pdf.worker.min.mjs',
				import.meta.url,
			).toString()
			pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl
			return pdfjsLib
		})()
	}
	return pdfjsLibPromise
}

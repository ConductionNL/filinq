/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Which in-app viewer opens a file, and which props it gets. Kept out of
 * FileViewerPage so the routing is testable on its own.
 */

import { emlPreviewUrl } from './fileViewerService.js'

/**
 * Match a file (by MIME and name) to one of the supported in-app viewers.
 *
 * @param {object|null} file Current file descriptor from the store.
 * @return {string|null} 'pdf' | 'word' | 'odt' | 'text' | 'eml' | null when unsupported.
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-7
 */
export function detectViewer(file) {
	if (!file) return null
	const name = (file.fileName || '').toLowerCase()
	const mime = (file.mimeType || '').toLowerCase()
	if (mime.includes('pdf') || name.endsWith('.pdf')) return 'pdf'
	if (
		mime
			=== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		|| name.endsWith('.docx')
	)
		return 'word'
	if (mime === 'application/vnd.oasis.opendocument.text' || name.endsWith('.odt'))
		return 'odt'
	if (mime === 'message/rfc822' || name.endsWith('.eml')) return 'eml'
	if (mime.startsWith('text/') || name.match(/\.(txt|md|markdown|log|csv)$/))
		return 'text'
	return null
}

/**
 * Map a viewer kind to the component that renders it. An EML message is
 * shown as its server-rendered PDF preview, so it uses PdfViewer.
 *
 * @param {string|null} kind Viewer kind from detectViewer().
 * @return {string|null} Component name, or null when nothing can render it.
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-7
 */
export function viewerComponentFor(kind) {
	switch (kind) {
		case 'pdf':
		case 'eml':
			return 'PdfViewer'
		case 'word':
			return 'WordViewer'
		case 'odt':
			return 'OdtViewer'
		case 'text':
			return 'TextViewer'
		default:
			return null
	}
}

/**
 * Props for the active viewer. EML loads its bytes from the preview endpoint
 * keyed by file id; every other kind loads from the WebDAV path.
 *
 * @param {object|null} file Current file descriptor from the store.
 * @param {string|null} kind Viewer kind from detectViewer().
 * @return {object} Props to bind on the viewer component.
 * @spec openspec/changes/archive/2026-10-09-eml-viewer-preview/tasks.md#task-7
 */
export function viewerPropsFor(file, kind) {
	if (!file) return {}
	if (kind === 'eml') {
		return { path: file.path, url: emlPreviewUrl(file.fileId) }
	}
	return { path: file.path }
}

/**
 * Anonymisation upload allow-list + file partitioning.
 *
 * Anonymisation only produces real redactions for formats the backend can
 * edit in place: Word (.docx) via PHPWord, OpenDocument Text (.odt) via
 * OpenRegister's in-place XML surgery (odt-anonymisation-writeback), plain
 * text via byte-level replace, and PDF via the SAPP byte-replace pipeline.
 * EML is anonymised by OpenRegister and assembled into a redacted PDF/A-3b by
 * EmlPdfAssemblyService (eml-pdf-assembly). Other binary formats fall through
 * to the str_ireplace path that returns a byte-identical copy — see
 * project-anonymization-pipeline for the upstream OR limitation. The upload
 * widget restricts selection so users can't accidentally pick a format that
 * won't actually redact.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

/**
 * File extensions the anonymisation pipeline can redact.
 *
 * @type {string[]}
 */
export const ALLOWED_EXTENSIONS = ['docx', 'odt', 'txt', 'pdf', 'eml']

/**
 * MIME types the anonymisation pipeline can redact.
 *
 * @type {Set<string>}
 */
export const ALLOWED_MIMES = new Set([
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'application/vnd.oasis.opendocument.text',
	'text/plain',
	'application/pdf',
	'message/rfc822',
])

/**
 * Value for the file input's `accept` attribute (extensions + MIME types).
 *
 * @type {string}
 */
export const ACCEPT_ATTR =
	'.docx,.odt,.txt,.pdf,.eml,'
	+ 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
	+ 'application/vnd.oasis.opendocument.text,'
	+ 'text/plain,application/pdf,message/rfc822'

/**
 * Split a FileList into accepted (docx/odt/txt/pdf/eml) and rejected files.
 *
 * Matches on both MIME and filename extension because drag-and-drop sometimes
 * omits MIME (e.g. for .docx on certain browsers) and the input's `accept`
 * attribute is advisory only.
 *
 * @param {FileList | File[]} files Incoming files.
 * @return {{ accepted: File[], rejected: File[] }}
 */
export function partitionFiles(files) {
	const accepted = []
	const rejected = []
	for (const file of Array.from(files)) {
		const ext = (file.name.split('.').pop() || '').toLowerCase()
		if (ALLOWED_MIMES.has(file.type) || ALLOWED_EXTENSIONS.includes(ext)) {
			accepted.push(file)
		} else {
			rejected.push(file)
		}
	}
	return { accepted, rejected }
}

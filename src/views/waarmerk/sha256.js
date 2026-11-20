/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

/**
 * The hex SHA-256 of a local file, computed in the browser.
 *
 * The file's bytes never leave the verifier's machine: only this hash is
 * sent to the server (document-waarmerk-certification REQ-DDWMK-004).
 *
 * @param {Blob} file The file the verifier selected.
 * @param {SubtleCrypto} subtle The WebCrypto implementation.
 * @return {Promise<string>} The lowercase hex digest.
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
export async function sha256Hex(file, subtle = globalThis.crypto?.subtle) {
	if (!subtle) {
		throw new Error('WebCrypto is not available')
	}
	const buffer = await file.arrayBuffer()
	const digest = await subtle.digest('SHA-256', buffer)
	return Array.from(new Uint8Array(digest))
		.map((byte) => byte.toString(16).padStart(2, '0'))
		.join('')
}

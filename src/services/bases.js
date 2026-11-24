/**
 * Grondslagen options for the pickers — the Woo refusal grounds, read
 * through filinq's one resolver (`GET /apps/filinq/api/grounds`): dossiq's
 * list when dossiq is installed, else the read-only snapshot.
 *
 * The stored value is the ground's code (e.g. "5.1.2.e"); the label shows
 * the code and the ground's name. Only citable grounds are offered: an
 * article heading such as "5.1" is not a ground a redaction can cite.
 *
 * On an error no options are returned rather than a hard-coded list, so a
 * value that is not a ground is never offered for storage.
 *
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * One ground as a picker option, or null when it cannot be cited.
 *
 * @param {object} ground A ground of the list (`code`, `label`, `citable`).
 * @return {{label: string, value: string}|null} The option, or null.
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */
export function groundOption(ground) {
	const code = typeof ground?.code === 'string' ? ground.code : ''
	if (code === '' || ground?.citable === false) {
		return null
	}

	return { label: optionLabel(code, ground.label), value: code }
}

/**
 * The picker label of a ground: its code, with its label when it has one.
 *
 * @param {string} code The ground's code.
 * @param {string|undefined} label The ground's label as answered.
 * @return {string} The label.
 */
function optionLabel(code, label) {
	return typeof label === 'string' && label !== '' ? `${code} — ${label}` : code
}

/**
 * Fetch the grondslagen options from filinq's grounds endpoint.
 *
 * @return {Promise<Array<{label: string, value: string}>>} Options for NcSelect
 *   ({label, value: code}); empty when the list cannot be read.
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-every-grounds-reader-in-filinq-goes-through-the-resolver-req-grd-002
 */
export async function fetchBaseOptions() {
	try {
		const response = await axios.get(generateUrl('/apps/filinq/api/grounds'))
		const grounds = Array.isArray(response.data?.grounds) ? response.data.grounds : []
		return grounds.map(groundOption).filter(Boolean)
	} catch {
		return []
	}
}

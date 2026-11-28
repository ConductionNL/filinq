/**
 * Where filinq's Woo refusal grounds come from, for the read-only admin section.
 *
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Whether a grounds answer carries at least one ground.
 *
 * @param {object|null} answer The `/api/grounds` answer.
 * @return {boolean} True when it does.
 */
function hasGrounds(answer) {
	return Boolean(answer) && Array.isArray(answer.grounds) && answer.grounds.length > 0
}

/**
 * Whether a ground has a code and may be cited.
 *
 * @param {object} ground A ground of the answer.
 * @return {boolean} True when it may be cited.
 */
function isCitable(ground) {
	return typeof ground?.code === 'string' && ground.code !== '' && ground.citable !== false
}

/**
 * The summary of a grounds answer: its source, version and the citable grounds.
 *
 * @param {object|null} answer The `/api/grounds` answer.
 * @return {{source: string, version: string, grounds: Array<{code: string, label: string}>}|null} The summary, or null when nothing could be read.
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 */
export function sourceSummary(answer) {
	if (!hasGrounds(answer)) {
		return null
	}

	return {
		source: answer.source === 'dossiq' ? 'dossiq' : 'snapshot',
		version: typeof answer.version === 'string' ? answer.version : '',
		grounds: answer.grounds
			.filter(isCitable)
			.map((ground) => ({ code: ground.code, label: typeof ground.label === 'string' ? ground.label : '' })),
	}
}

/**
 * The address of dossiq's grounds page.
 *
 * @return {string} The URL of the grounds page in dossiq.
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 */
export function dossiqGroundsUrl() {
	return generateUrl('/apps/dossiq/settings/woo-refusal-grounds')
}

/**
 * Read the grounds summary; null when the list cannot be read.
 *
 * @return {Promise<object|null>}
 * @spec openspec/changes/archive/2026-11-24-grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 */
export async function fetchGroundsSource() {
	try {
		const response = await axios.get(generateUrl('/apps/filinq/api/grounds'))
		return sourceSummary(response.data)
	} catch {
		return null
	}
}

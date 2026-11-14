/**
 * Where filinq's Woo refusal grounds come from, for the read-only admin section.
 *
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The summary of a grounds answer: its source, version and the citable grounds.
 *
 * @param {object|null} answer The `/api/grounds` answer.
 * @return {{source: string, version: string, grounds: Array<{code: string, label: string}>}|null} The summary, or null when nothing could be read.
 */
export function sourceSummary(answer) {
	if (!answer || !Array.isArray(answer.grounds) || answer.grounds.length === 0) {
		return null
	}

	return {
		source: answer.source === 'dossiq' ? 'dossiq' : 'snapshot',
		version: typeof answer.version === 'string' ? answer.version : '',
		grounds: answer.grounds
			.filter((ground) => typeof ground?.code === 'string' && ground.code !== '' && ground.citable !== false)
			.map((ground) => ({ code: ground.code, label: typeof ground.label === 'string' ? ground.label : '' })),
	}
}

/**
 * The address of dossiq's grounds page.
 *
 * @return {string}
 */
export function dossiqGroundsUrl() {
	return generateUrl('/apps/dossiq/settings/woo-refusal-grounds')
}

/**
 * Read the grounds summary; null when the list cannot be read.
 *
 * @return {Promise<object|null>}
 * @spec openspec/changes/grondslagen-read-from-dossiq/specs/refusal-grounds-source/spec.md#requirement-the-snapshot-is-read-only-and-the-admin-says-where-the-list-lives-req-grd-004
 */
export async function fetchGroundsSource() {
	try {
		const response = await axios.get(generateUrl('/apps/filinq/api/grounds'))
		return sourceSummary(response.data)
	} catch {
		return null
	}
}

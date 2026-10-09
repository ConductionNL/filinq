/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * What the signing folder leaf reads and links to. The folder is a query over
 * the signer's pending requests, read on every mount and never stored, the
 * same endpoint the signing folder page reads.
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * How many entries the leaf shows before it points at the full folder.
 */
export const SIGNING_FOLDER_PREVIEW_SIZE = 5

/**
 * Read the first entries of the signing folder and the total waiting.
 *
 * @param {number} limit How many entries to read.
 * @return {Promise<{entries: Array, total: number}>} The entries, oldest deadline first, and the total.
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export async function fetchSigningFolderPreview(
	limit = SIGNING_FOLDER_PREVIEW_SIZE,
) {
	const { data } = await axios.get(
		generateUrl('/apps/filinq/api/signing/folder'),
		{
			params: { limit, offset: 0 },
		},
	)
	return {
		entries: Array.isArray(data?.entries) ? data.entries : [],
		total: Number(data?.total) || 0,
	}
}

/**
 * The URL of the full signing folder page in filinq.
 *
 * @return {string} The page URL.
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export function signingFolderPageUrl() {
	return generateUrl('/apps/filinq/signing-folder')
}

/**
 * A deadline as a short local date, or '' when there is none.
 *
 * @param {string} value An ISO date or date-time.
 * @return {string} The local date, the raw value when it does not parse, or ''.
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
export function formatDeadline(value) {
	if (!value) {
		return ''
	}
	const parsed = new Date(value)
	if (Number.isNaN(parsed.getTime())) {
		return String(value)
	}
	return parsed.toLocaleDateString()
}

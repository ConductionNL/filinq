/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Which documents on a page of My Documents are in the case system: their
 * case-system delivery was written back. One request per page, not per row.
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * The file ids among these that are in the case system.
 *
 * @param {Array<number>} fileIds The files on the page.
 * @return {Promise<object>} A map of file id (as a string) to true.
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */
export async function fetchCaseSystemFiles(fileIds = []) {
	if (fileIds.length === 0) {
		return {}
	}
	const response = await axios.get(
		generateUrl('/apps/filinq/api/bridge/case-system/files'),
		{ params: { fileIds: fileIds.join(',') } },
	)
	const delivered = Array.isArray(response.data?.delivered) ? response.data.delivered : []
	return Object.fromEntries(delivered.map((id) => [String(id), true]))
}

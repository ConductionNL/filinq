/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Request a server-computed structured comparison of two document subjects.
 *
 * Each subject is `{ fileId, versionTimestamp? }`. The response is the
 * structured diff (hunks, summary, crossFormat) plus redaction annotation
 * metadata when the pair is an original/anonymised pair.
 *
 * @param {object} left  Left subject `{ fileId, versionTimestamp? }`.
 * @param {object} right Right subject `{ fileId, versionTimestamp? }`.
 * @return {Promise<object>} The comparison response.
 *
 * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
 */
export async function compareDocuments(left, right) {
	const url = generateUrl('/apps/docudesk/api/comparison/compare')
	const { data } = await axios.post(url, { left, right })
	return data
}

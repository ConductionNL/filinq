/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A document's activity: the trail filinq keeps in OpenRegister for the
 * document's record (opened, downloaded, edited, versioned, shared,
 * anonymised, signed, deleted), shown beside the viewer, and the report that
 * filinq's viewer opened a document.
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The trail of one document, `{id}` the file id. `CnAuditTrailCard` reads it
 * as an endpoint, oldest first.
 *
 * @type {string}
 */
export const DOCUMENT_ACTIVITY_ENDPOINT = '/apps/filinq/api/documents/{id}/audit'

/**
 * The words for each action a document's trail holds: the document actions
 * filinq writes, and the record's own registration and change.
 *
 * @return {Object<string, string>} Label by action.
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
export function documentActivityLabels() {
	return {
		create: t('filinq', 'Registered'),
		update: t('filinq', 'Record changed'),
		'filinq.document.opened': t('filinq', 'Opened'),
		'filinq.document.downloaded': t('filinq', 'Downloaded'),
		'filinq.document.edited': t('filinq', 'Edited'),
		'filinq.document.versioned': t('filinq', 'Version restored'),
		'filinq.document.shared': t('filinq', 'Shared'),
		'filinq.document.anonymised': t('filinq', 'Anonymised'),
		'filinq.document.signed': t('filinq', 'Signed'),
		'filinq.document.deleted': t('filinq', 'Deleted'),
		'filinq.document.copied_to': t('filinq', 'Copied to another record'),
		'filinq.document.copied_from': t('filinq', 'Copied from another record'),
	}
}

/**
 * Tell filinq that its viewer opened a document, so the opening lands in the
 * document's trail. The server checks that the reader may read the file.
 * Never throws: a failed report must not stop the document from showing.
 *
 * @param {number|string} fileId The opened file.
 * @return {Promise<boolean>} Whether an entry was written.
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
export async function recordDocumentOpened(fileId) {
	const id = Number(fileId)
	if (!Number.isInteger(id) || id <= 0) {
		return false
	}
	try {
		const response = await axios.post(generateUrl(`/apps/filinq/api/documents/${id}/opened`))
		return response.data?.recorded === true
	} catch {
		return false
	}
}

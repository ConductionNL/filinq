/**
 * The acknowledgement of receipt and the stamped copy of an intake document,
 * as the inbox shows them.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A document without an acknowledgement matched no rule that asks for one and
 * shows nothing, so a clerk can tell "nobody was told" from "not asked to".
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */

import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The label for an acknowledgement.
 *
 * @param {object|null|undefined} acknowledgement The document's acknowledgement.
 * @return {string} Sent, Queued as letter, Refused, Failed, or '' when there is none.
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */
export function acknowledgementLabel(acknowledgement) {
	const status = acknowledgement?.status
	if (!status) {
		return ''
	}
	const labels = {
		sent: t('filinq', 'Sent'),
		'queued-as-letter': t('filinq', 'Queued as letter'),
		refused: t('filinq', 'Refused'),
		failed: t('filinq', 'Failed'),
	}
	return labels[status] ?? labels.failed
}

/**
 * The badge colour per label.
 *
 * @return {object} Label to CnStatusBadge variant.
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */
export function acknowledgementColorMap() {
	return {
		[acknowledgementLabel({ status: 'sent' })]: 'success',
		[acknowledgementLabel({ status: 'queued-as-letter' })]: 'primary',
		[acknowledgementLabel({ status: 'refused' })]: 'warning',
		[acknowledgementLabel({ status: 'failed' })]: 'error',
	}
}

/**
 * Why an acknowledgement did not go, or where it went, for the badge tooltip.
 *
 * @param {object|null|undefined} acknowledgement The document's acknowledgement.
 * @return {string} The reason, else the address, else ''.
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */
export function acknowledgementDetail(acknowledgement) {
	return acknowledgement?.reason || acknowledgement?.address || ''
}

/**
 * Where the stamped copy opens: Nextcloud's own `/f/{fileId}` route, under
 * the clerk's own access to the file.
 *
 * @param {object} document The intake document.
 * @return {string} The URL, or '' when no stamped copy was made.
 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-the-worklist-shows-both-outcomes-req-ias-004
 */
export function stampedCopyUrl(document) {
	const fileId = Number(document?.stampedRendition)
	if (!Number.isInteger(fileId) || fileId <= 0) {
		return ''
	}
	return generateUrl(`/f/${fileId}`)
}

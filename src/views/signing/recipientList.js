/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The recipient list on the signing request detail page
 * (signing-accept-only-recipient task 4.2): who signs, who only accepts,
 * where each of them is, and when they signed or accepted.
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The label of a recipient's role.
 *
 * @param {string} role `sign` or `accept`; anything else signs.
 * @return {string}
 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#4-screens
 */
export function roleLabel(role) {
	return role === 'accept' ? t('filinq', 'Accept only') : t('filinq', 'Sign')
}

/**
 * The label of a recipient's state.
 *
 * @param {string} status The recipient record's status.
 * @return {string}
 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#4-screens
 */
export function statusLabel(status) {
	const labels = {
		PENDING: t('filinq', 'Waiting'),
		SIGNED: t('filinq', 'Signed'),
		ACCEPTED: t('filinq', 'Accepted'),
		DECLINED: t('filinq', 'Declined'),
	}
	return labels[status] ?? t('filinq', 'Unknown')
}

/**
 * The rows of the recipient list, in signing order.
 *
 * @param {Array<object>} recipients The `recipients` of the signing request.
 * @return {Array<{id: string, name: string, role: string, status: string, at: string}>}
 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#4-screens
 */
export function recipientRows(recipients) {
	if (!Array.isArray(recipients)) {
		return []
	}
	return [...recipients]
		.sort((left, right) => (left.order ?? 0) - (right.order ?? 0))
		.map((recipient, index) => ({
			id: String(recipient.id ?? index),
			name:
				recipient.name
				|| t('filinq', 'Recipient {number}', { number: index + 1 }),
			role: roleLabel(recipient.role),
			status: statusLabel(recipient.status),
			at: recipient.at ? new Date(recipient.at).toLocaleString() : '-',
		}))
}

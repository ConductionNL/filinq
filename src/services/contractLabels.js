/**
 * The words the contract pages show for statuses, buckets and fields.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * A contract status as shown.
 *
 * @param {string} status The status.
 * @return {string} The label.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function statusLabel(status) {
	return (
		{
			draft: t('filinq', 'Draft'),
			active: t('filinq', 'Active'),
			renewed: t('filinq', 'Renewed'),
			terminated: t('filinq', 'Terminated'),
			expired: t('filinq', 'Expired'),
		}[status] || status
	)
}

/**
 * A pipeline bucket as shown.
 *
 * @param {string} bucket The bucket.
 * @return {string} The heading.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-2
 */
export function bucketLabel(bucket) {
	return (
		{
			expired: t('filinq', 'Expired'),
			noticeDue: t('filinq', 'Notice due within 30 days'),
			expiring: t('filinq', 'Ending within 90 days'),
			later: t('filinq', 'Later'),
		}[bucket] || bucket
	)
}

/**
 * The contract field a suggestion would fill, as shown.
 *
 * @param {string} field The field.
 * @return {string} The label.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function suggestionFieldLabel(field) {
	return (
		{
			startDate: t('filinq', 'Start date'),
			endDate: t('filinq', 'End date'),
			noticePeriodDays: t('filinq', 'Notice period in days'),
			value: t('filinq', 'Value'),
			currency: t('filinq', 'Currency'),
			party: t('filinq', 'Party'),
		}[field] || field
	)
}

/**
 * A money amount in the viewer's locale.
 *
 * @param {number|null} value The amount.
 * @param {string} currency ISO 4217.
 * @return {string} The amount, or ''.
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#3-1
 */
export function money(value, currency = 'EUR') {
	if (value === null || value === undefined || value === '') {
		return ''
	}
	try {
		return new Intl.NumberFormat(undefined, {
			style: 'currency',
			currency: currency || 'EUR',
		}).format(Number(value))
	} catch (error) {
		return `${value} ${currency || ''}`.trim()
	}
}

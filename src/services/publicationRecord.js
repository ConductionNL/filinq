/**
 * Woo publications: what a record means. No network here, so it tests plainly.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-publicatie-pipeline/specs/woo-publicatie-pipeline/spec.md
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The Woo metadata a hand-off needs.
 */
export const REQUIRED_METADATA = ['officieleTitel', 'wooCategory', 'publicatiedatum']

/**
 * The label of a status.
 *
 * @param {string} status The stored status
 * @return {string} The label.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export function publicationStatusLabel(status) {
	const labels = {
		draft: t('filinq', 'Not ready'),
		ready: t('filinq', 'Ready to hand off'),
		handed_off: t('filinq', 'Handed to the publication platform'),
		published: t('filinq', 'Published'),
		depublication_requested: t('filinq', 'Withdrawal requested'),
		depublished: t('filinq', 'Withdrawn'),
	}
	return labels[status] || status
}

/**
 * The three readiness checks of a record, in order.
 *
 * @param {object} record The record
 * @return {Array<{key: string, label: string, ok: boolean, route: string}>} The checks, each with the page where it is resolved.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export function readinessChecks(record) {
	return [
		{
			key: 'entitiesReviewed',
			label: t('filinq', 'Detected entities checked by a person'),
			ok: record.entitiesReviewed === true,
			route: 'Anonymization',
		},
		{
			key: 'consentClear',
			label: t('filinq', 'Consent requests allow publication'),
			ok: record.consentClear === true,
			route: 'Consent',
		},
		{
			key: 'prohibitionsClear',
			label: t('filinq', 'No publication prohibition applies'),
			ok: record.prohibitionsClear === true,
			route: 'Prohibitions',
		},
	]
}

/**
 * The Woo metadata still missing for a hand-off.
 *
 * @param {object} record The record
 * @return {string[]} The missing field names.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export function missingMetadata(record) {
	return REQUIRED_METADATA.filter((field) => !record[field])
}

/**
 * Whether the hand-off button can be used.
 *
 * @param {object} record The record
 * @param {boolean} platformAvailable Whether OpenCatalogi is installed
 * @return {boolean} True when the record is ready, complete, and there is a platform.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export function canHandOff(record, platformAvailable) {
	return (
		platformAvailable === true
		&& record.status === 'ready'
		&& missingMetadata(record).length === 0
	)
}

/**
 * Whether the publication can be withdrawn.
 *
 * @param {object} record The record
 * @return {boolean} True once it was handed off and not yet withdrawn.
 * @spec openspec/changes/woo-publicatie-pipeline/tasks.md#task-3.1
 */
export function canWithdraw(record) {
	return ['handed_off', 'published'].includes(record.status)
}

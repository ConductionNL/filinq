/**
 * The reading state of an intake document, as the inbox shows it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A clerk sees whether the text is still coming, is there, or will not come.
 * A document with no reading state was never queued for reading (not a scan,
 * or reading on arrival is off) and shows nothing.
 *
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-2.2
 */

import { translatePlural as n, translate as t } from '@nextcloud/l10n'

/**
 * The label for a reading state.
 *
 * @param {string|undefined} state queued, reading, read, failed, or nothing.
 * @return {string} The label, or '' when the document is not being read.
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-2.2
 */
export function readingLabel(state) {
	const labels = {
		queued: t('filinq', 'Waiting to be read'),
		reading: t('filinq', 'Reading'),
		read: t('filinq', 'Text recognised'),
		failed: t('filinq', 'Text could not be read'),
	}
	if (!state) {
		return ''
	}
	return labels[state] ?? labels.failed
}

/**
 * The badge colour per label.
 *
 * @return {object} Label to CnStatusBadge variant.
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-2.2
 */
export function readingColorMap() {
	return {
		[readingLabel('queued')]: 'default',
		[readingLabel('reading')]: 'primary',
		[readingLabel('read')]: 'success',
		[readingLabel('failed')]: 'error',
	}
}

/**
 * What the inbox says when a failed reading would reach nobody.
 *
 * The group is named, because that turns the warning into a two-minute fix.
 * The warning is shown even with nothing failed yet: an empty group is worth
 * knowing about before the night it is needed.
 *
 * @param {object|null|undefined} reach The notificationReach of the inbox answer.
 * @return {string} The warning, or '' when somebody will be told.
 * @spec openspec/changes/archive/2026-10-09-intake-failure-reaches-someone/tasks.md#task-3.3
 */
export function reachWarning(reach) {
	if (!reach || reach.staffed !== false) {
		return ''
	}
	const group = reach.group || ''
	const failed = Number(reach.failureCount) || 0
	if (failed === 0) {
		return t(
			'filinq',
			'Nobody is in "{group}", so if a document cannot be read, no one will be told. Add at least one person to that group.',
			{ group },
		)
	}
	return n(
		'filinq',
		'%n document could not be read and nobody in "{group}" will be told. Add at least one person to that group.',
		'%n documents could not be read and nobody in "{group}" will be told. Add at least one person to that group.',
		failed,
		{ group },
	)
}

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
 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-2.2
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The label for a reading state.
 *
 * @param {string|undefined} state queued, reading, read, failed, or nothing.
 * @return {string} The label, or '' when the document is not being read.
 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-2.2
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
 * @spec openspec/changes/intake-ocr-on-arrival/tasks.md#task-2.2
 */
export function readingColorMap() {
	return {
		[readingLabel('queued')]: 'default',
		[readingLabel('reading')]: 'primary',
		[readingLabel('read')]: 'success',
		[readingLabel('failed')]: 'error',
	}
}

/**
 * The dossier lifecycle as both dossier views show it: the label of each
 * status and the badge colour of each label.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Human label for a lifecycle status.
 *
 * A dossier created before `status` existed has none, and absence
 * means `open` rather than missing data.
 *
 * @param {string} status The stored status.
 * @return {string} The label.
 */
export function dossierStatusLabel(status) {
	const labels = {
		open: t('filinq', 'Open'),
		'in-review': t('filinq', 'In review'),
		processed: t('filinq', 'Processed'),
		published: t('filinq', 'Published'),
		closed: t('filinq', 'Closed'),
	}
	return labels[status] || t('filinq', 'Open')
}

/**
 * The badge colour per status label, labels translated.
 *
 * @return {Object<string, string>} Label => colour.
 */
export function dossierStatusColors() {
	return {
		[t('filinq', 'Open')]: 'default',
		[t('filinq', 'In review')]: 'warning',
		[t('filinq', 'Processed')]: 'primary',
		[t('filinq', 'Published')]: 'success',
		[t('filinq', 'Closed')]: 'default',
	}
}

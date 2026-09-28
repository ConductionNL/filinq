/**
 * The merge fields the template editor offers before the author types one.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `grondslag` is the legal basis (a `base` object bound through dataRefs,
 * also offered under that name by DocumentService). `bezwaar` is filled at
 * generation from the decision date in the data (besluitDatum or
 * decisionDate) and the `bezwaar_termijn_weken` app setting.
 *
 * @spec openspec/changes/archive/2026-09-28-decision-letter-legal-basis-and-deadline/tasks.md#task-1.3
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * The offered fields, each with the path to insert and a label to show.
 *
 * @return {Array<{field: string, label: string}>} The offered merge fields.
 */
export function suggestedMergeFields() {
	return [
		{ field: 'grondslag.name', label: t('filinq', 'Legal basis') },
		{
			field: 'grondslag.description',
			label: t('filinq', 'Legal basis explanation'),
		},
		{ field: 'bezwaar.uiterlijk', label: t('filinq', 'Last day to object') },
		{ field: 'bezwaar.vanaf', label: t('filinq', 'First day to object') },
		{
			field: 'bezwaar.termijnWeken',
			label: t('filinq', 'Objection term in weeks'),
		},
	]
}

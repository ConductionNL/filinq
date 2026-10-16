/**
 * Accessibility after redaction: what the sidebar and the publication page say.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server records `structurePreservation` on the anonymisation link and
 * returns it with the run: the state (preserved, degraded, not-applicable,
 * unknown), the tag counts and the reasons. Unknown is never shown as fine.
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * A readable sentence for a loss reason from OpenRegister or veraPDF.
 *
 * @param {string} reason The reason code.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
export function lossReasonText(reason) {
	switch (reason) {
		case 'engine-cannot-reauthor-structtree':
			return t(
				'filinq',
				'The redaction engine cannot rebuild the tag structure',
			)
		case 'marked-content-correspondence-broken':
			return t('filinq', 'The tags no longer point at the right text')
		case 'structtreeroot-dropped-on-rebuild':
			return t(
				'filinq',
				'The tag structure was dropped when the file was rebuilt',
			)
		case 'input-not-tagged':
			return t('filinq', 'The original had no tags to keep')
		case 'page-structure-not-preservable':
			return t('filinq', 'The page structure could not be kept')
		case 'verapdf-not-pdfua':
			return t('filinq', 'veraPDF found that the copy no longer meets PDF/UA')
		default:
			return reason
	}
}

/**
 * The note to show for a redaction's outcome, or null when there is none.
 *
 * @param {object|null|undefined} outcome The structurePreservation block.
 * @return {{type: string, title: string, detail: string, reasons: string[]}|null}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
export function accessibilityNote(outcome) {
	if (!outcome || !outcome.state) {
		return null
	}
	const counts =
		outcome.tagCountBefore === undefined
			? ''
			: t('filinq', '{after} of {before} tags kept', {
					after: outcome.tagCountAfter ?? 0,
					before: outcome.tagCountBefore,
				}) + '. '
	const reasons = (outcome.lossReasons || []).map(lossReasonText)
	switch (outcome.state) {
		case 'preserved':
			return {
				type: 'success',
				title: t('filinq', 'Accessibility preserved'),
				detail: (
					counts
					+ (outcome.veraPdfVerified
						? t('filinq', 'Confirmed by veraPDF.')
						: '')
				).trim(),
				reasons,
			}
		case 'not-applicable':
			return {
				type: 'info',
				title: t('filinq', 'No accessibility to keep'),
				detail: t(
					'filinq',
					'The original had no tag structure, so the copy has none either.',
				),
				reasons: [],
			}
		case 'degraded':
			return {
				type: 'warning',
				title: t('filinq', 'Accessibility lost'),
				detail:
					counts + t('filinq', 'Screen readers may not read this copy.'),
				reasons,
			}
		default:
			return {
				type: 'warning',
				title: t('filinq', 'Accessibility unknown'),
				detail: t(
					'filinq',
					'OpenRegister did not report whether the tag structure was kept. Treat this copy as not accessible.',
				),
				reasons: [],
			}
	}
}

/**
 * Whether a publication record's redacted copy lost its accessibility.
 *
 * @param {object} record The publication record.
 * @return {boolean}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
export function accessibilityLost(record) {
	return ['degraded', 'unknown'].includes(record?.accessibilityState)
}

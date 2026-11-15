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
 * The sentence for each loss reason code OpenRegister or veraPDF reports.
 * Each entry is a function so the string is translated when it is shown.
 */
const LOSS_REASON_TEXT = {
	'engine-cannot-reauthor-structtree': () =>
		t('filinq', 'The redaction engine cannot rebuild the tag structure'),
	'marked-content-correspondence-broken': () =>
		t('filinq', 'The tags no longer point at the right text'),
	'structtreeroot-dropped-on-rebuild': () =>
		t('filinq', 'The tag structure was dropped when the file was rebuilt'),
	'input-not-tagged': () => t('filinq', 'The original had no tags to keep'),
	'page-structure-not-preservable': () =>
		t('filinq', 'The page structure could not be kept'),
	'verapdf-not-pdfua': () =>
		t('filinq', 'veraPDF found that the copy no longer meets PDF/UA'),
}

/**
 * A readable sentence for a loss reason from OpenRegister or veraPDF.
 *
 * @param {string} reason The reason code.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
export function lossReasonText(reason) {
	return Object.hasOwn(LOSS_REASON_TEXT, reason)
		? LOSS_REASON_TEXT[reason]()
		: reason
}

/**
 * The tag count sentence of an outcome, or an empty string without counts.
 *
 * @param {object} outcome The structurePreservation block.
 * @return {string}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
function tagCountText(outcome) {
	if (outcome.tagCountBefore === undefined) {
		return ''
	}
	return (
		t('filinq', '{after} of {before} tags kept', {
			after: outcome.tagCountAfter ?? 0,
			before: outcome.tagCountBefore,
		}) + '. '
	)
}

/**
 * The note builder for each structurePreservation state. Each builder takes
 * the outcome and its tag count sentence and reason sentences.
 */
const NOTE_BY_STATE = {
	preserved: (outcome, parts) => ({
		type: 'success',
		title: t('filinq', 'Accessibility preserved'),
		detail: (
			parts.counts
			+ (outcome.veraPdfVerified ? t('filinq', 'Confirmed by veraPDF.') : '')
		).trim(),
		reasons: parts.reasons,
	}),
	'not-applicable': () => ({
		type: 'info',
		title: t('filinq', 'No accessibility to keep'),
		detail: t(
			'filinq',
			'The original had no tag structure, so the copy has none either.',
		),
		reasons: [],
	}),
	degraded: (outcome, parts) => ({
		type: 'warning',
		title: t('filinq', 'Accessibility lost'),
		detail: parts.counts + t('filinq', 'Screen readers may not read this copy.'),
		reasons: parts.reasons,
	}),
}

/**
 * The note for a state nothing reported on, which is never shown as fine.
 *
 * @return {{type: string, title: string, detail: string, reasons: string[]}}
 *
 * @spec openspec/changes/archive/2026-09-29-accessible-redaction-output/tasks.md#task-3.1
 */
function unknownNote() {
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
	const parts = {
		counts: tagCountText(outcome),
		reasons: (outcome.lossReasons || []).map(lossReasonText),
	}
	const known =
		typeof outcome.state === 'string'
		&& Object.hasOwn(NOTE_BY_STATE, outcome.state)
	return known ? NOTE_BY_STATE[outcome.state](outcome, parts) : unknownNote()
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

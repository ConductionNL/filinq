/**
 * Four eyes when anonymising (redaction-four-eyes-setting REQ-RWB-09): whether
 * the signed-in person may anonymise a document they checked themselves, and
 * the sentence the review card shows when a colleague has to do it. The server
 * decides; this mirrors that decision so the button is disabled before a click
 * and keeps a refusal the server sent.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09
 */

/** The checker is the person anonymising. */
export const SAME_PERSON = 'same_person'

/** Nobody is signed in, so it cannot be shown someone else checked. */
export const ANONYMISER_UNKNOWN = 'anonymiser_unknown'

/** The refusals that keep the check mark: the document is checked, only by the wrong person. */
export const FOUR_EYES_REASONS = [SAME_PERSON, ANONYMISER_UNKNOWN]

/**
 * Whether the setting asks for four eyes on this run. An unknown mode reads
 * as the default, `woo`.
 *
 * @param {string} mode The stored `redaction_four_eyes` value.
 * @param {boolean} wooRun Whether the run is a Woo case (a dossier run, or flagged by a case system).
 * @return {boolean} True when the checker may not anonymise.
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09
 */
export function fourEyesApplies(mode, wooRun) {
	if (mode === 'always') {
		return true
	}
	if (mode === 'off') {
		return false
	}
	return wooRun === true
}

/**
 * Why the signed-in person may not anonymise this entry, or ''. A refusal the
 * server sent wins; otherwise the setting, the run and the check mark decide.
 *
 * @param {object|null} entry The queue entry, with `reviewMark` and any `fourEyes` refusal.
 * @param {{mode: string, wooRun: boolean, uid: string}} context The setting, the run and who is signed in.
 * @return {string} `same_person`, `anonymiser_unknown` or ''.
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09
 */
export function colleagueReason(entry, { mode, wooRun = false, uid = '' }) {
	if (entry && FOUR_EYES_REASONS.includes(entry.fourEyes)) {
		return entry.fourEyes
	}
	if (!entry || !entry.reviewMark || !fourEyesApplies(mode, wooRun)) {
		return ''
	}
	if (!uid) {
		return ANONYMISER_UNKNOWN
	}
	return entry.reviewMark.checkedBy === uid ? SAME_PERSON : ''
}

/**
 * The sentence for a four-eyes reason.
 *
 * @param {string} reason The reason.
 * @param {(app: string, text: string) => string} t The translator.
 * @return {string} The sentence, or '' without a reason.
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09
 */
export function colleagueHint(reason, t) {
	if (reason === SAME_PERSON) {
		return t('filinq', 'A colleague must anonymise this document')
	}
	if (reason === ANONYMISER_UNKNOWN) {
		return t('filinq', 'Sign in to anonymise this document')
	}
	return ''
}

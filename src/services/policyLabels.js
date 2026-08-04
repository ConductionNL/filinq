/**
 * Display labels for the policy enums.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * Severity and consent-method values arrive from the backend as raw schema enum
 * tokens (`high`, `digital_signature`, …) and were rendered straight into tables
 * and selects, so a Dutch user saw `digital_signature`. This mirrors what
 * `ConsentIndex`'s `formatStatus` / `formatDecision` already do for the consent
 * and notification statuses, and what `entityTypes.js` does for entity types —
 * the pattern existed, it just was not applied everywhere.
 *
 * Two conventions coexist in this codebase and both are kept deliberately:
 * `entityTypeLabel()` translates by the UPPERCASE TOKEN as msgid (`PERSON` →
 * `PERSOON`), because those tokens are already msgids elsewhere. Here a readable
 * English msgid is used instead, because `t('docudesk', 'high')` would be an
 * ambiguous string to hand a translator — high what?
 *
 * The VALUE is never translated. Only the label is. The stored value must stay
 * the raw token or OpenRegister's write-time schema validation rejects the
 * record, which is the coupling the form comments already warn about.
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Severity tokens, in the order the publicationProhibition schema enumerates
 * them. Keep in lock-step with `docudesk_register.json`.
 */
export const SEVERITIES = Object.freeze(['high', 'medium', 'low'])

/**
 * Consent-method tokens per the publicationConsent schema.
 */
export const CONSENT_METHODS = Object.freeze([
	'paper',
	'digital_signature',
	'verbal_recorded',
	'opt_in_form',
])

/**
 * Translated label for a prohibition severity.
 *
 * Unknown or empty values fall back to the raw token rather than rendering
 * blank, so a schema addition shows up as an untranslated token instead of
 * silently disappearing from the UI.
 *
 * @param {string} severity Raw severity token.
 * @return {string} Translated label, or the raw token.
 */
export function severityLabel(severity) {
	const key = String(severity || '').trim().toLowerCase()
	const map = {
		high: t('docudesk', 'High'),
		medium: t('docudesk', 'Medium'),
		low: t('docudesk', 'Low'),
	}
	return map[key] || severity || ''
}

/**
 * Translated label for a consent method.
 *
 * @param {string} method Raw consent-method token.
 * @return {string} Translated label, or the raw token.
 */
export function consentMethodLabel(method) {
	const key = String(method || '').trim().toLowerCase()
	const map = {
		paper: t('docudesk', 'Paper'),
		digital_signature: t('docudesk', 'Digital signature'),
		verbal_recorded: t('docudesk', 'Verbal (recorded)'),
		opt_in_form: t('docudesk', 'Opt-in form'),
	}
	return map[key] || method || ''
}

/**
 * Severity options for an NcSelect, in the `{label, value}` shape the repo's
 * existing selects use with `:reduce="(o) => o.value"`.
 *
 * @return {Array<{label: string, value: string}>} Options.
 */
export function severityOptions() {
	return SEVERITIES.map((value) => ({ label: severityLabel(value), value }))
}

/**
 * Consent-method options for an NcSelect.
 *
 * @return {Array<{label: string, value: string}>} Options.
 */
export function consentMethodOptions() {
	return CONSENT_METHODS.map((value) => ({ label: consentMethodLabel(value), value }))
}

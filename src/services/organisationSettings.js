/**
 * Organisation settings: the calls behind the organisation settings page.
 *
 * The page reads and saves through filinq's api/organisation-settings, which
 * lets only an admin of the active organisation (or an instance admin) save.
 * The huisstijl options come from OpenRegister's object API, which keeps them
 * inside the active organisation; nothing is filtered here.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/admin-settings/spec.md#requirement-per-organisation-settings-are-stored-as-organisationsettings-objects-req-ddmth-007
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const BASE = '/apps/filinq/api/organisation-settings'

/** The entity categories a profile can list, in the order the page shows them. */
export const ENTITY_CATEGORIES = Object.freeze([
	'PERSON',
	'BSN',
	'PHONE',
	'EMAIL',
	'IBAN',
	'ADDRESS',
	'ORGANIZATION',
	'LOCATION',
	'DATE',
])

/**
 * The active organisation, whether the caller may edit, and the stored overrides.
 *
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>}
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export function loadOrganisationSettings() {
	return answer(axios.get(generateUrl(BASE)))
}

/**
 * Save the overrides; an empty value inherits the instance default.
 *
 * @param {object} form The form: name, consentPeriodDays, anonymize, keepVisible, defaultHuisstijl, notes.
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>}
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export function saveOrganisationSettings(form) {
	return answer(axios.put(generateUrl(BASE), toPayload(form)))
}

/**
 * The request body of a save.
 *
 * Two empty category lists send no profile, so the instance profile applies.
 *
 * @param {object} form The form.
 * @return {object} The body.
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export function toPayload(form) {
	const anonymize = copyList(form.anonymize)
	const keepVisible = copyList(form.keepVisible)
	return {
		name: trimmedOrNull(form.name),
		consentPeriodDays: trimmedOrNull(String(form.consentPeriodDays ?? '')),
		anonymizationProfile: profileOrNull(anonymize, keepVisible),
		defaultHuisstijl: form.defaultHuisstijl || null,
		notes: trimmedOrNull(form.notes),
	}
}

/**
 * A trimmed text, or null when nothing is left.
 *
 * @param {string|null|undefined} value The text.
 * @return {string|null} The trimmed text, or null.
 */
function trimmedOrNull(value) {
	return (value || '').trim() || null
}

/**
 * A copy of a list; an absent list is empty.
 *
 * @param {Array|null|undefined} list The list.
 * @return {Array} The copy.
 */
function copyList(list) {
	return [...(list || [])]
}

/**
 * The anonymization profile of two category lists, or null when both are empty.
 *
 * @param {Array} anonymize The categories to anonymize.
 * @param {Array} keepVisible The categories to keep visible.
 * @return {object|null} The profile, or null.
 */
function profileOrNull(anonymize, keepVisible) {
	if (anonymize.length === 0 && keepVisible.length === 0) {
		return null
	}
	return { anonymize, keepVisible }
}

/**
 * The form of a stored view.
 *
 * @param {object|null} settings The stored overrides from the view.
 * @return {object} The form.
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export function toForm(settings) {
	const stored = settings || {}
	return {
		name: stored.name || '',
		consentPeriodDays: textOrEmpty(stored.consentPeriodDays),
		...profileLists(stored.anonymizationProfile),
		defaultHuisstijl: stored.defaultHuisstijl || null,
		notes: stored.notes || '',
	}
}

/**
 * The two category lists of a stored anonymization profile, as copies.
 *
 * @param {object|null|undefined} stored The stored profile.
 * @return {{anonymize: Array, keepVisible: Array}} The lists.
 */
function profileLists(stored) {
	const profile = stored || {}
	return {
		anonymize: copyList(profile.anonymize),
		keepVisible: copyList(profile.keepVisible || profile.keep),
	}
}

/**
 * A value as text; null and undefined become empty.
 *
 * @param {string|number|null|undefined} value The value.
 * @return {string} The text.
 */
function textOrEmpty(value) {
	return value === null || value === undefined ? '' : String(value)
}

/**
 * The huisstijl objects the caller can see, as select options.
 *
 * @return {Promise<Array<{id: string, label: string}>>} The options; empty when none can be read.
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export async function loadHuisstijlOptions() {
	const result = await answer(
		axios.get(generateUrl('/apps/openregister/api/objects/filinq/huisstijl'), {
			params: { _limit: 100 },
		}),
	)
	if (!result.ok) {
		return []
	}
	const rows = Array.isArray(result.data?.results) ? result.data.results : []
	return rows
		.map((row) => ({ id: optionId(row), label: optionLabel(row) }))
		.filter((option) => option.id !== '')
		.map((option) => ({ ...option, label: option.label || option.id }))
}

/**
 * The id of a huisstijl row.
 *
 * @param {object|null} row The row.
 * @return {string} The id; empty when it has none.
 */
function optionId(row) {
	return row?.['@self']?.id || row?.id || row?.uuid || ''
}

/**
 * The label of a huisstijl row.
 *
 * @param {object|null} row The row.
 * @return {string} The label; empty when it has none.
 */
function optionLabel(row) {
	return row?.name || row?.title || row?.['@self']?.name || ''
}

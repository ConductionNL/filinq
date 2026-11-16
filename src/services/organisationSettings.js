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
	const anonymize = [...(form.anonymize || [])]
	const keepVisible = [...(form.keepVisible || [])]
	const period = String(form.consentPeriodDays ?? '').trim()
	return {
		name: (form.name || '').trim() || null,
		consentPeriodDays: period === '' ? null : period,
		anonymizationProfile:
			anonymize.length === 0 && keepVisible.length === 0
				? null
				: { anonymize, keepVisible },
		defaultHuisstijl: form.defaultHuisstijl || null,
		notes: (form.notes || '').trim() || null,
	}
}

/**
 * The form of a stored view.
 *
 * @param {object|null} settings The stored overrides from the view.
 * @return {object} The form.
 * @spec openspec/changes/multi-tenant-hardening/tasks.md#4-frontend
 */
export function toForm(settings) {
	const profile = settings?.anonymizationProfile || {}
	return {
		name: settings?.name || '',
		consentPeriodDays:
			settings?.consentPeriodDays === null || settings?.consentPeriodDays === undefined
				? ''
				: String(settings.consentPeriodDays),
		anonymize: [...(profile.anonymize || [])],
		keepVisible: [...(profile.keepVisible || profile.keep || [])],
		defaultHuisstijl: settings?.defaultHuisstijl || null,
		notes: settings?.notes || '',
	}
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
		.map((row) => ({
			id: row?.['@self']?.id || row?.id || row?.uuid || '',
			label: row?.name || row?.title || row?.['@self']?.name || '',
		}))
		.filter((option) => option.id !== '')
		.map((option) => ({ ...option, label: option.label || option.id }))
}

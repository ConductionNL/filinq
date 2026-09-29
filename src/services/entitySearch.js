/**
 * Entity search: the calls behind the entity search page, and the labels it shows.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/entity-search/tasks.md#task-3.1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const base = () => generateUrl('/apps/filinq/api/entity-search')

/**
 * The entity types OpenRegister's detection stores, offered as a filter.
 *
 * @type {Array<string>}
 */
export const ENTITY_TYPES = ['PERSON', 'EMAIL', 'PHONE', 'ADDRESS', 'IBAN', 'SSN', 'ORGANIZATION', 'LOCATION', 'CUSTOM_DICTIONARY']

/**
 * Run a call and answer { ok, data } or { ok: false, error, status }.
 *
 * @param {() => Promise<{data: object}>} call The axios call.
 * @return {Promise<object>} The outcome.
 */
async function outcome(call) {
	try {
		const { data } = await call()
		return { ok: true, data }
	} catch (error) {
		return {
			ok: false,
			status: error.response?.status || 0,
			error: error.response?.data?.error || t('filinq', 'The entity search failed. Try again later.'),
		}
	}
}

/**
 * Whether the signed-in user may use the entity search. Anything but a 200 is no.
 *
 * @return {Promise<boolean>} True when allowed.
 * @spec openspec/changes/entity-search/tasks.md#task-3.1
 */
export async function mayUseEntitySearch() {
	const result = await outcome(() => axios.get(base() + '/access'))
	return result.ok === true && result.data?.allowed === true
}

/**
 * Search the catalogue.
 *
 * @param {object} filters query, type, category, limit, offset.
 * @return {Promise<object>} The outcome, data.results the entities and data.total the count.
 * @spec openspec/changes/entity-search/tasks.md#task-3.1
 */
export function searchEntities(filters) {
	const params = {}
	for (const key of ['query', 'type', 'category', 'limit', 'offset']) {
		if (filters[key] !== undefined && filters[key] !== null && filters[key] !== '') {
			params[key] = filters[key]
		}
	}
	return outcome(() => axios.get(base(), { params }))
}

/**
 * One entity and where it occurs.
 *
 * @param {string} uuid The entity uuid.
 * @return {Promise<object>} The outcome, data the entity with documents, noAccess and other.
 * @spec openspec/changes/entity-search/tasks.md#task-3.2
 */
export function entityDetail(uuid) {
	return outcome(() => axios.get(base() + '/' + encodeURIComponent(uuid)))
}

/**
 * The label for a document's anonymisation state.
 *
 * @param {string} state none, anonymised or derivative.
 * @return {string} The label.
 * @spec openspec/changes/entity-search/tasks.md#task-3.2
 */
export function anonymisationLabel(state) {
	if (state === 'anonymised') {
		return t('filinq', 'Anonymised copy exists')
	}
	if (state === 'derivative') {
		return t('filinq', 'This is an anonymised copy')
	}
	return t('filinq', 'Not anonymised')
}

/**
 * The label for an occurrence outside a file.
 *
 * @param {string} kind object or email.
 * @param {number} count How many.
 * @return {string} The label.
 * @spec openspec/changes/entity-search/tasks.md#task-3.2
 */
export function otherLabel(kind, count) {
	if (kind === 'object') {
		return t('filinq', 'Register objects: {count}', { count })
	}
	return t('filinq', 'Emails: {count}', { count })
}

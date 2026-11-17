/**
 * Detection provider: the calls behind the admin section that picks an
 * organisation's entity-detection engine, and the notice the review shows
 * about which engine produced a document's entities.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const BASE = '/apps/filinq/api/detection-provider'

/**
 * Load the detection settings as the admin section reads them.
 *
 * @return {Promise<{ok: boolean, data?: object}>} The settings, or the refusal.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function loadDetectionSettings() {
	return answer(axios.get(generateUrl(`${BASE}/settings`)))
}

/**
 * Save the detection settings. Only the fields given are changed.
 *
 * @param {object} fields Any of organisation + provider, endpoint, agentflow, requireLocal.
 * @return {Promise<{ok: boolean, data?: object}>} The settings as stored, or the refusal.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function saveDetectionSettings(fields) {
	return answer(axios.put(generateUrl(`${BASE}/settings`), fields))
}

/**
 * Ask whether the LLM provider could be called now.
 *
 * @return {Promise<{ok: boolean, data?: {available: boolean, reason: string|null}}>} The test result.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function testDetectionProvider() {
	return answer(axios.post(generateUrl(`${BASE}/test`)))
}

/**
 * The organisations the admin can choose a provider for, from OpenRegister.
 *
 * @return {Promise<{ok: boolean, data?: {organisations: Array<{uuid: string, name: string}>, active: string|null}}>}
 *         The organisations and the admin's active one, or the refusal.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export async function loadOrganisations() {
	const result = await answer(axios.get(generateUrl('/apps/openregister/api/organisations')))
	if (!result.ok) {
		return result
	}
	const rows = Array.isArray(result.data?.results) ? result.data.results : []
	const organisations = rows
		.filter((row) => row && typeof row.uuid === 'string' && row.uuid !== '')
		.map((row) => ({ uuid: row.uuid, name: row.name || row.uuid }))
	return {
		ok: true,
		data: { organisations, active: result.data?.active?.uuid ?? null },
	}
}

/**
 * The provider an organisation is on: `default` unless the map opts it in.
 *
 * @param {object} providers The stored organisation → provider map.
 * @param {string|null} organisation The organisation uuid.
 * @return {string} The provider id.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function providerFor(providers, organisation) {
	if (!organisation || !providers || typeof providers !== 'object') {
		return 'default'
	}
	return providers[organisation] || 'default'
}

/**
 * The label of a provider id, as the picker shows it.
 *
 * @param {string} id The provider id.
 * @return {string} The label; an unknown id is shown as it is.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function providerLabel(id) {
	return {
		default: t('filinq', 'Default detection (OpenRegister)'),
		llm: t('filinq', 'Local language model (Hermiq agentflow)'),
	}[id] ?? id
}

/**
 * The sentence for a connectivity test result.
 *
 * @param {{available: boolean, reason: string|null}} result The test result.
 * @return {string} The sentence.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-4.2
 */
export function testResultText(result) {
	if (result && result.available === true) {
		return t('filinq', 'The language model provider answers and can be used.')
	}
	const reasons = {
		unconfigured: t('filinq', 'The language model provider is not set up: enter an endpoint and an agentflow.'),
		'not-local': t('filinq', 'The endpoint is not on this server or the local network, and only local endpoints are allowed.'),
		unreachable: t('filinq', 'The endpoint did not answer. Check that Hermiq runs and the address is right.'),
	}
	return reasons[result?.reason] ?? t('filinq', 'The language model provider cannot be used.')
}

/**
 * Which engine produced a document's entities, as the review shows it.
 *
 * A run that fell back always carries a warning, even when the server sent
 * none, so a default run is never shown as a language-model run.
 *
 * @param {object} data The extract endpoint's answer.
 * @return {{provider: string|null, fellBack: boolean, warning: string|null}} The notice.
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
 */
export function detectionNotice(data) {
	if (!data || typeof data.detectionProvider !== 'string' || data.detectionProvider === '') {
		return { provider: null, fellBack: false, warning: null }
	}
	const fellBack = data.detectionFellBack === true
	let warning = null
	if (fellBack) {
		warning = (typeof data.detectionWarning === 'string' && data.detectionWarning.trim() !== '')
			? data.detectionWarning
			: t('filinq', 'The language model could not run, so the default detection was used. It may miss entities that depend on context.')
	}
	return { provider: data.detectionProvider, fellBack, warning }
}

/**
 * Copy the detection notice onto a queue entry.
 *
 * @param {object} entry The queue entry.
 * @param {object} data The extract endpoint's answer.
 * @return {void}
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
 */
export function applyDetectionNotice(entry, data) {
	const notice = detectionNotice(data)
	entry.detectionProvider = notice.provider
	entry.detectionFellBack = notice.fellBack
	entry.detectionWarning = notice.warning
}

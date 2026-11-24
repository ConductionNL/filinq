/**
 * The suggestions an arriving document carries, as the assign dialog shows
 * them: what the base registries answered (intake-base-registry-enrichment
 * REQ-IBR-004) and the parties read out of its text (REQ-IDW-03). A suggestion
 * is never a party record; the clerk accepts, corrects or dismisses it, and the
 * server records who did.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { decidePartySuggestion } from './intakeService.js'

/** The registry fields a clerk may correct before accepting (RegistrySuggestionDecision::CORRECTABLE). */
export const REGISTRY_FIELDS = ['name', 'address', 'kvkNumber']

/** The party fields text detection fills (PartySuggestionService::FIELDS). */
export const PARTY_FIELDS = ['name', 'address', 'email', 'telephone']

/**
 * A plain object, or an empty one.
 *
 * @param {unknown} value The value.
 * @return {object} The object.
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */
function objectOf(value) {
	return value && typeof value === 'object' && !Array.isArray(value) ? value : {}
}

/**
 * The given keys of an object as trimmed strings, leaving out empty ones.
 *
 * @param {object} source The object.
 * @param {Array<string>} keys The keys to keep.
 * @return {object} The fields.
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */
export function pickFields(source, keys) {
	const fields = {}
	for (const key of keys) {
		const value = objectOf(source)[key]
		if (typeof value === 'string' && value.trim() !== '') {
			fields[key] = value.trim()
		} else if (typeof value === 'number') {
			fields[key] = String(value)
		}
	}
	return fields
}

/**
 * The registry suggestions nobody has decided yet, each with its position on
 * the document, which is how the server addresses it.
 *
 * @param {object|null} document The intake document.
 * @return {Array<{index: number, source: string, identifier: string, fields: object}>} The open suggestions.
 * @spec openspec/changes/intake-base-registry-enrichment/specs/intake-registry-enrichment/spec.md#requirement-a-person-accepts-or-dismisses-a-registry-suggestion-req-ibr-004
 */
export function openRegistrySuggestions(document) {
	const suggestions = Array.isArray(document?.registrySuggestion) ? document.registrySuggestion : []
	return suggestions
		.map((suggestion, index) => ({ suggestion: objectOf(suggestion), index }))
		.filter(({ suggestion }) => suggestion.written !== true && suggestion.dismissed !== true)
		.map(({ suggestion, index }) => ({
			index,
			source: typeof suggestion.source === 'string' ? suggestion.source : '',
			identifier: typeof suggestion.identifier === 'string' ? suggestion.identifier : '',
			fields: pickFields(suggestion.fields, REGISTRY_FIELDS),
		}))
}

/**
 * The party suggestions nobody has decided yet.
 *
 * @param {object|null} document The intake document.
 * @return {Array<{index: number, party: object, confidence: number|null}>} The open suggestions.
 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
 */
export function openPartySuggestions(document) {
	const suggestions = Array.isArray(document?.partySuggestion) ? document.partySuggestion : []
	return suggestions
		.map((suggestion, index) => ({ suggestion: objectOf(suggestion), index }))
		.filter(({ suggestion }) => suggestion.written !== true && suggestion.dismissed !== true)
		.map(({ suggestion, index }) => ({
			index,
			party: pickFields(suggestion.party, PARTY_FIELDS),
			confidence: typeof suggestion.confidence === 'number' ? suggestion.confidence : null,
		}))
		.filter((suggestion) => Object.keys(suggestion.party).length > 0)
}

/**
 * Accept or dismiss one registry suggestion. Only an accepted suggestion
 * carries fields, and only the ones a clerk may correct.
 *
 * @param {string} uuid The intake document.
 * @param {number} index The suggestion's position on the document.
 * @param {'accepted'|'dismissed'} decision The decision.
 * @param {object} [fields] The fields as the clerk accepted them, corrected or not.
 * @return {Promise<object>} The saved intake document.
 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.2
 */
export async function decideRegistrySuggestion(uuid, index, decision, fields = {}) {
	const url = generateUrl(`/apps/filinq/api/intake/documents/${encodeURIComponent(uuid)}/registry-decisions`)
	const body = { index, decision }
	if (decision === 'accepted') {
		body.fields = pickFields(fields, REGISTRY_FIELDS)
	}
	const { data } = await axios.post(url, body)
	return data
}

/**
 * Confirm, correct or dismiss one party suggestion. A correction that changes
 * nothing is a confirmation; a dismissal files nothing.
 *
 * @param {object} document The intake document.
 * @param {{party: object}} suggestion The suggestion.
 * @param {'confirm'|'dismiss'} choice What the clerk chose.
 * @param {object} [corrected] The fields as the clerk left them.
 * @return {Promise<object>} The stored correction.
 * @spec openspec/changes/intake-base-registry-enrichment/tasks.md#task-3.3
 */
export async function decidePartyChoice(document, suggestion, choice, corrected = null) {
	const suggested = pickFields(suggestion.party, PARTY_FIELDS)
	const accepted = choice === 'dismiss' ? {} : pickFields(corrected || suggested, PARTY_FIELDS)
	let decision = 'rejected'
	if (choice !== 'dismiss') {
		decision = JSON.stringify(accepted) === JSON.stringify(suggested) ? 'accepted' : 'edited'
	}
	return decidePartySuggestion({
		sender: typeof document?.sender === 'string' ? document.sender : '',
		decision,
		suggested,
		accepted,
		intakeDocument: typeof document?.uuid === 'string' ? document.uuid : '',
	})
}

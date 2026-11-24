/**
 * Archival wiring: OpenRegister's archival settings and the one explicit
 * admin action that points them at filinq's selectielijst and destruction
 * list schemas.
 *
 * The page reads and writes OpenRegister's own `api/settings/archival`; filinq
 * keeps no copy and writes nothing on boot or in a repair step, so another
 * app's wiring is only ever replaced by an admin who saw it and clicked.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { answer } from './apiAnswer.js'

const SETTINGS = '/apps/openregister/api/settings/archival'

/** The register and schemas filinq wires, by slug. */
export const ARCHIVAL_SLUGS = Object.freeze({
	register: 'filinq',
	selectielijst: 'selectielijstEntry',
	destructionList: 'destructionList',
})

/** The five archival settings the action writes. */
export const WIRING_KEYS = Object.freeze([
	'selectielijstRegister',
	'selectielijstSchema',
	'destructionListRegister',
	'destructionListSchema',
	'archivalRegister',
])

/**
 * OpenRegister's archival settings.
 *
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>} The settings, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */
export function loadArchivalSettings() {
	return answer(axios.get(generateUrl(SETTINGS)))
}

/**
 * The id of a register or schema entry, as a number.
 *
 * @param {object|number|string|null|undefined} entry The entry, or a bare id.
 * @return {number|null} The id, or null when there is none.
 */
function idOf(entry) {
	const raw = (entry !== null && typeof entry === 'object') ? entry.id : entry
	const id = Number(raw)
	return Number.isInteger(id) && id > 0 ? id : null
}

/**
 * The schema of a register with the given slug.
 *
 * @param {object} register The register, its schemas expanded.
 * @param {string} slug The schema slug.
 * @return {object|undefined} The schema.
 */
function schemaOf(register, slug) {
	return (Array.isArray(register?.schemas) ? register.schemas : [])
		.find((schema) => schema !== null && typeof schema === 'object' && schema.slug === slug)
}

/**
 * The values the action writes: filinq's register and its two schemas, by id.
 *
 * @param {Array<object>} registers The registers the settings page lists, schemas expanded.
 * @return {object|null} The five settings, or null when filinq's register or a schema is not there.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */
export function wiringFor(registers) {
	const register = (Array.isArray(registers) ? registers : []).find((entry) => entry?.slug === ARCHIVAL_SLUGS.register)
	const registerId = idOf(register)
	const selectielijst = idOf(schemaOf(register, ARCHIVAL_SLUGS.selectielijst))
	const destructionList = idOf(schemaOf(register, ARCHIVAL_SLUGS.destructionList))
	if (registerId === null || selectielijst === null || destructionList === null) {
		return null
	}

	return {
		selectielijstRegister: registerId,
		selectielijstSchema: selectielijst,
		destructionListRegister: registerId,
		destructionListSchema: destructionList,
		archivalRegister: registerId,
	}
}

/**
 * Where the archival settings point now, against what the action would write.
 *
 * @param {object|null} settings OpenRegister's archival settings.
 * @param {object|null} wiring What the action would write.
 * @return {'unset'|'wired'|'elsewhere'} Unset when none of the five is set, wired when all match, elsewhere otherwise.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */
export function wiringState(settings, wiring) {
	const current = WIRING_KEYS.map((key) => idOf(settings?.[key]))
	if (current.every((id) => id === null)) {
		return 'unset'
	}

	if (wiring !== null && WIRING_KEYS.every((key, index) => current[index] === wiring[key])) {
		return 'wired'
	}

	return 'elsewhere'
}

/**
 * The register that owns the selectielijst wiring now, by name.
 *
 * @param {object|null} settings OpenRegister's archival settings.
 * @param {Array<object>} registers The registers the settings page lists.
 * @return {string} The register's title or slug, its id when it is not listed, '' when nothing is set.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */
export function currentOwner(settings, registers) {
	const id = idOf(settings?.selectielijstRegister)
	if (id === null) {
		return ''
	}

	const register = (Array.isArray(registers) ? registers : []).find((entry) => idOf(entry) === id)
	return register?.title || register?.slug || String(id)
}

/**
 * Point OpenRegister's archival settings at filinq's register and schemas.
 *
 * OpenRegister resets every key a write leaves out to its default, so the
 * other archival settings are sent back as they were read.
 *
 * @param {object} settings OpenRegister's archival settings as read.
 * @param {object} wiring The five values from wiringFor().
 * @return {Promise<{ok: boolean, data?: object, status?: number, error?: string}>} The stored settings, or the refusal.
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */
export function wireArchivalRegister(settings, wiring) {
	return answer(axios.put(generateUrl(SETTINGS), { ...(settings ?? {}), ...wiring }))
}

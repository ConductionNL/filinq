/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Scanner profiles on the intake channels page: what an administrator types
 * becomes one profile, and the whole list is declared back to the server,
 * which validates and stores it (`scanIntake#declareProfiles`).
 *
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/** The separator modes the server accepts. */
export const SEPARATOR_MODES = ['qr', 'blankPage', 'none']

/** The colour modes the server accepts. */
export const COLOUR_MODES = ['colour', 'greyscale', 'blackAndWhite']

/** The resolutions the server accepts, in dpi. */
export const RESOLUTION_RANGE = Object.freeze({ min: 75, max: 1200, default: 300 })

/**
 * An empty add form.
 *
 * @return {{label: string, watchedFolder: string, resolution: number, colourMode: string, separatorMode: string}}
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
export function emptyProfileForm() {
	return {
		label: '',
		watchedFolder: '',
		resolution: RESOLUTION_RANGE.default,
		colourMode: COLOUR_MODES[0],
		separatorMode: SEPARATOR_MODES[0],
	}
}

/**
 * What is wrong with the add form, by field; empty when it can be added.
 *
 * @param {object} form The add form.
 * @return {object} `label`, `watchedFolder` or `resolution` mapped to a reason code.
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
export function profileFormErrors(form) {
	const errors = {}
	if (String(form?.label ?? '').trim() === '') {
		errors.label = 'required'
	}
	if (String(form?.watchedFolder ?? '').trim() === '') {
		errors.watchedFolder = 'required'
	}
	const resolution = Number(form?.resolution)
	if (!Number.isInteger(resolution) || resolution < RESOLUTION_RANGE.min || resolution > RESOLUTION_RANGE.max) {
		errors.resolution = 'range'
	}
	return errors
}

/**
 * An id for a new profile: its name in lower case with hyphens, made unique
 * against the profiles already declared.
 *
 * @param {string} label The profile name.
 * @param {Array<{id: string}>} existing The declared profiles.
 * @return {string}
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
export function profileId(label, existing) {
	const base = String(label ?? '')
		.normalize('NFKD')
		.replace(/[̀-ͯ]/g, '')
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '') || 'scanner'
	const taken = new Set((existing ?? []).map((profile) => profile.id))
	let id = base
	for (let n = 2; taken.has(id); n++) {
		id = `${base}-${n}`
	}
	return id
}

/**
 * The profile list with the add form appended as a new profile.
 *
 * @param {Array<object>} existing The declared profiles.
 * @param {object} form The add form.
 * @return {Array<object>}
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
export function withNewProfile(existing, form) {
	const label = String(form.label).trim()
	return [
		...(existing ?? []),
		{
			id: profileId(label, existing),
			label,
			watchedFolder: String(form.watchedFolder).trim(),
			resolution: Number(form.resolution),
			colourMode: COLOUR_MODES.includes(form.colourMode) ? form.colourMode : COLOUR_MODES[0],
			separatorMode: SEPARATOR_MODES.includes(form.separatorMode) ? form.separatorMode : SEPARATOR_MODES[0],
		},
	]
}

/**
 * Declare the whole profile list.
 *
 * @param {Array<object>} profiles The profiles.
 * @return {Promise<Array<object>>} What the server stored.
 * @spec openspec/changes/archive/2026-11-25-scan-intake-with-separator-sheets/tasks.md#task-4.2
 */
export async function declareScanProfiles(profiles) {
	const { data } = await axios.post(generateUrl('/apps/filinq/api/scan/profiles'), { profiles })
	return Array.isArray(data?.results) ? data.results : []
}

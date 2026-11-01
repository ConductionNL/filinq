/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Helpers for rendering OpenRegister register-i18n values.
 *
 * Fields tagged for register-i18n are stored by OpenRegister's TranslationHandler
 * as a per-language map (`{"nl": "Projectnamen", "en": "Project names"}`) rather
 * than a plain string. Binding such a value straight into a template renders
 * nothing (Vue stringifies the object into the DOM as `[object Object]`, and an
 * empty text node when interpolated through a slot), which is how a seeded
 * dictionary showed an empty Label column on a live instance while every unit
 * test — which used plain strings — stayed green.
 *
 * Use `resolveI18nValue()` anywhere a register-backed, i18n-tagged string field
 * is rendered.
 *
 * @spec openspec/specs/register-i18n/spec.md
 */

import { getLanguage } from '@nextcloud/l10n'

/**
 * Resolve a register-i18n value to a displayable string.
 *
 * Accepts either a plain string (returned unchanged) or a per-language map, and
 * picks, in order: the active Nextcloud language, its base language (`nl` for
 * `nl_NL`), English, then the first non-empty entry. Returns `fallback` when
 * nothing usable is present.
 *
 * @param {string|object|null|undefined} value    The raw field value from OpenRegister.
 * @param {string}                       fallback Value to return when nothing resolves.
 * @return {string} A displayable string, never an object.
 * @spec openspec/specs/register-i18n/spec.md
 */
export function resolveI18nValue(value, fallback = '') {
	if (isMissing(value)) {
		return fallback
	}
	return isLanguageMap(value) ? (pickLanguage(value) ?? fallback) : String(value)
}

/**
 * Whether a field holds no value at all.
 *
 * @param {string|object|number|boolean|null|undefined} value The raw field value.
 * @return {boolean}
 */
function isMissing(value) {
	return value === null || value === undefined
}

/**
 * Whether a field value is a per-language map rather than a scalar.
 *
 * @param {string|object|number|boolean|null|undefined} value The raw field value.
 * @return {boolean}
 */
function isLanguageMap(value) {
	return typeof value === 'object' && !Array.isArray(value)
}

/**
 * Whether a map entry can be shown.
 *
 * @param {string|object|number|boolean|null|undefined} entry One language's value.
 * @return {boolean}
 */
function isText(entry) {
	return typeof entry === 'string' && entry.length > 0
}

/**
 * The languages to try, in order: the active one, its base language (`nl`
 * for `nl_NL`), then English.
 *
 * @return {Array<string>}
 */
function languageCandidates() {
	const active = typeof getLanguage === 'function' ? getLanguage() || '' : ''
	return [active, active.split(/[-_]/)[0], 'en']
}

/**
 * The entry of a per-language map to show: the first candidate language
 * with text, else the first entry with text.
 *
 * @param {object} map The per-language map.
 * @return {string|undefined}
 */
function pickLanguage(map) {
	const key = languageCandidates().find((k) => k && isText(map[k]))
	return key === undefined ? Object.values(map).find(isText) : map[key]
}

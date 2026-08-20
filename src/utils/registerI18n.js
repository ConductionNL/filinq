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
 */
export function resolveI18nValue(value, fallback = '') {
	if (value === null || value === undefined) {
		return fallback
	}

	if (typeof value === 'string') {
		return value
	}

	if (typeof value !== 'object' || Array.isArray(value) === true) {
		return String(value)
	}

	const active = typeof getLanguage === 'function' ? getLanguage() || '' : ''
	const base = active.split(/[-_]/)[0]

	const candidates = [active, base, 'en']
	for (const key of candidates) {
		if (key && typeof value[key] === 'string' && value[key].length > 0) {
			return value[key]
		}
	}

	const first = Object.values(value).find(
		(entry) => typeof entry === 'string' && entry.length > 0,
	)

	return first !== undefined ? first : fallback
}

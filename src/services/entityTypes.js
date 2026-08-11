/**
 * Entity-type catalogue and colour helpers.
 *
 * Single source of truth for the detectable entity types and the colour each
 * one is shown in — both the sidebar type badge (`DdEntityCard`) and the
 * in-document highlight (T09) resolve their colour through here.
 *
 * The definitive per-type colours are not decided yet: every type currently
 * maps onto a shared default tint via the `--dd-entity-color-*` CSS variables
 * defined in `src/assets/app.css`. To give a type its own colour later, only
 * those variables need to change — no JS edit required.
 */

import { translate as t } from '@nextcloud/l10n'

/**
 * Detectable entity types. Sourced from the OpenRegister detector vocabulary
 * (presidio + openanonymiser tags); kept as a flat list so the frontend has a
 * stable set to populate selects with.
 *
 * @type {Array<string>}
 */
export const ENTITY_TYPES = Object.freeze([
	'PERSON',
	'ORGANIZATION',
	'EMAIL',
	'PHONE_NUMBER',
	'IBAN_CODE',
	'IP_ADDRESS',
	'LOCATION',
	'KENTEKEN',
	'OTHER',
])

/**
 * Normalise a raw type string to the lower-case key used in the CSS variable
 * names. Unknown / empty types collapse to `default` so they pick up the
 * shared fallback colour.
 *
 * @param {string} type Raw entity type (any casing) or empty.
 * @return {string} Lower-case key matching a `--dd-entity-color-<key>` var, or `default`.
 */
export function normaliseEntityType(type) {
	const upper = String(type || '').trim().toUpperCase()
	if (ENTITY_TYPES.includes(upper)) {
		return upper.toLowerCase()
	}
	return 'default'
}

/**
 * CSS colour reference for a type, suitable for an inline `background-color`
 * or a highlight span. Resolves to the type's `--dd-entity-color-<key>` var
 * with the shared default as fallback, so unknown types still render.
 *
 * @param {string} type Raw entity type (any casing).
 * @return {string} A `var(...)` expression, e.g. `var(--dd-entity-color-person, var(--dd-entity-color-default))`.
 */
export function entityTypeColor(type) {
	const key = normaliseEntityType(type)
	if (key === 'default') {
		return 'var(--dd-entity-color-default)'
	}
	return `var(--dd-entity-color-${key}, var(--dd-entity-color-default))`
}

/**
 * Human-readable, translated label for an entity type — display only.
 *
 * The raw type (`PERSON`, `EMAIL`, …) is a stable machine key: it drives the
 * anonymisation placeholders, dedup keys, colour lookup and the OpenRegister
 * mapping, so it must never be mutated. This helper leaves that key untouched
 * and only produces the string shown to the user.
 *
 * The raw uppercase token IS the translation msgid — deliberately the same
 * scheme the DocuDesk backend uses (`LegalBasesSummaryService::localizeEntityType`
 * → `IL10N::t($entityType)`) and that OpenRegister writes into redacted
 * documents (`DocumentProcessingHandler::LOCALIZABLE_ENTITY_TYPES`). Translating
 * by the raw token means the sidebar label, the summary legend and the redacted
 * document all resolve to the same string for a given language. `t()` returns
 * the msgid unchanged when no translation exists, so unknown / free-form types
 * fall back to their raw label and nothing ever renders blank.
 *
 * @param {string} type Raw entity type (any casing).
 * @return {string} Translated label, or the raw uppercase token when untranslated.
 */
export function entityTypeLabel(type) {
	const upper = String(type || '').trim().toUpperCase()
	if (upper === '') {
		return ''
	}
	return t('docudesk', upper)
}

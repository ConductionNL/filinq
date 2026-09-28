/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The register/schema sections of the admin settings page.
 *
 * The page renders one section per object type from its first render on, so
 * `sections` must hold an entry for every type before the settings response
 * arrives; an empty `{}` made every load throw on `selectedRegister`.
 */

/**
 * One section with nothing selected.
 *
 * @return {{selectedRegister: string, selectedSchema: string, loading: boolean}} The section.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function emptySection() {
	return { selectedRegister: '', selectedSchema: '', loading: false }
}

/**
 * An empty section for every object type.
 *
 * @param {string[]} objectTypes The object types the page configures.
 * @return {object} The sections, keyed by object type.
 *
 * @spec openspec/specs/admin-settings/spec.md
 */
export function initialSections(objectTypes) {
	return Object.fromEntries(objectTypes.map((type) => [type, emptySection()]))
}

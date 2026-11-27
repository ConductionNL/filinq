/**
 * Which registry leaves a record page leaves out because their app is absent.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The library registers its built-in leaves (Mail, Calendar, Contacts, Deck…)
 * unconditionally, and the registry-driven object sidebar lists every
 * registered leaf except the ids it is told to hide. Without this, a record
 * page on an instance without Mail still shows an Emails tab that can only
 * fail. This module names the leaves to hide; the sidebar stays the library's.
 *
 * 🔴 ABSENT HIDES; UNKNOWN DOES NOT. A leaf is hidden when its descriptor says
 * `available: false`, or when it names a `requiredApp` and the page's list of
 * enabled apps (`OC.appswebroots`, one key per app enabled for the user) is
 * known and lacks it. When that list is not on the page, nothing is hidden for
 * it: hiding on "not known" would hide a leaf whose app is installed, the same
 * rule `documentLeafTabs.js` keeps for `available: null`.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

/**
 * The apps enabled for the current user, as the page states them, or null.
 *
 * @param {object} [scope] The global scope to read `OC` from (window by default).
 * @return {Set<string>|null} The enabled app ids, or null when the page does not say.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function enabledApps(scope = globalThis) {
	const roots = scope?.OC?.appswebroots
	if (roots === null || typeof roots !== 'object') {
		return null
	}
	return new Set(Object.keys(roots))
}

/**
 * Whether one registered leaf is absent.
 *
 * @param {object}           provider A registry descriptor.
 * @param {Set<string>|null} enabled  The enabled app ids, or null when unknown.
 * @return {boolean} True when the leaf says it is unavailable or its app is known to be absent.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function isAbsentLeaf(provider, enabled) {
	if (provider.available === false) {
		return true
	}
	const app = typeof provider.requiredApp === 'string' ? provider.requiredApp : ''
	return app !== '' && enabled instanceof Set && enabled.has(app) === false
}

/**
 * The ids of the registered leaves whose app is absent.
 *
 * @param {Array<object>}    providers The registry snapshot.
 * @param {Set<string>|null} enabled   The enabled app ids, or null when unknown.
 * @return {string[]} The leaf ids to hide, in registry order.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function absentLeafIds(providers, enabled) {
	return (Array.isArray(providers) ? providers : [])
		.filter((provider) => provider !== null && typeof provider === 'object' && typeof provider.id === 'string')
		.filter((provider) => isAbsentLeaf(provider, enabled))
		.map((provider) => provider.id)
}

/**
 * The tabs a record page hides: the page's own choice plus the absent leaves.
 *
 * @param {string[]}         pageHidden The ids the page itself hides.
 * @param {Array<object>}    providers  The registry snapshot.
 * @param {Set<string>|null} enabled    The enabled app ids, or null when unknown.
 * @return {string[]} The ids to hide, each once, the page's first.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
export function hiddenLeafTabs(pageHidden, providers, enabled) {
	const own = Array.isArray(pageHidden) ? pageHidden.filter((id) => typeof id === 'string') : []
	return [...new Set([...own, ...absentLeafIds(providers, enabled)])]
}

/**
 * The detection backend state the settings endpoint reports, as the
 * warning banner reads it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server derives `warning` from OpenRegister's real state. This file never
 * derives one itself: a banner computed from a value filinq failed to read is
 * the defect anonymisation-fails-closed-without-a-detector removes.
 *
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-4
 */

/**
 * Warnings under which every anonymisation is refused.
 *
 * @type {string[]}
 */
export const REFUSING_WARNINGS = ['unknown', 'disabled', 'unavailable']

/**
 * The state before the settings endpoint has answered: no banner, no method.
 *
 * @return {object} The empty state.
 */
export function emptyBackendState() {
	return {
		known: false,
		activeMethod: '',
		effectiveMethod: '',
		warning: null,
		appApiInstalled: false,
		warningDismissed: false,
		showWarning: false,
	}
}

/**
 * Normalise the `anonymiserBackend` block of the settings response.
 *
 * @param {object|undefined} payload The block, or undefined when absent.
 * @return {object} The state the banner reads.
 */
export function backendStateFromSettings(payload) {
	if (!payload || typeof payload !== 'object') {
		return emptyBackendState()
	}

	return {
		known: payload.known === true,
		activeMethod: payload.activeMethod ?? '',
		effectiveMethod: payload.effectiveMethod ?? '',
		warning: payload.warning ?? null,
		appApiInstalled: payload.appApiInstalled === true,
		warningDismissed: payload.warningDismissed === true,
		showWarning: payload.showWarning === true,
	}
}

/**
 * Whether a warning means anonymisation is refused, so it cannot be dismissed.
 *
 * @param {string|null} warning The warning kind.
 * @return {boolean} True when every run is refused.
 */
export function isRefusing(warning) {
	return REFUSING_WARNINGS.includes(warning)
}

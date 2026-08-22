/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Minimal @nextcloud/auth stub for the offline Vitest suite. getCurrentUser
 * is overridable per-test via __setCurrentUser so the no-user branch of
 * buildWebdavUrl is exercisable.
 */

let user = { uid: 'admin', displayName: 'Admin' }

export function __setCurrentUser(next) {
	user = next
}

export function getCurrentUser() {
	return user
}

export function getRequestToken() {
	return 'test-token'
}

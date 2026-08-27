/**
 * SPDX-FileCopyrightText: 2026 Conduction / Filinq Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Minimal @nextcloud/router stub for the offline Vitest suite.
 */

export function generateUrl(url) {
	return `/index.php${url.startsWith('/') ? url : `/${url}`}`
}

export function generateRemoteUrl(service) {
	return `http://localhost/remote.php/${service}`
}

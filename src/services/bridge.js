/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The bridge from a case system: its sources with their health for the
 * admin panel, and the source badge of the files on a page of My Documents
 * (one request per page, not per row).
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The bridge sources, each with its `health`.
 *
 * @return {Promise<Array<object>>} The sources.
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */
export async function fetchBridgeSources() {
	const response = await axios.get(generateUrl('/apps/filinq/api/bridge/sources'))
	return Array.isArray(response.data?.results) ? response.data.results : []
}

/**
 * The vendor of each of these files that came in through the bridge.
 *
 * @param {Array<number>} fileIds The files on the page.
 * @return {Promise<object>} A map of file id (as a string) to vendor.
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
 */
export async function fetchSourceBadges(fileIds = []) {
	if (fileIds.length === 0) {
		return {}
	}
	const response = await axios.get(
		generateUrl('/apps/filinq/api/bridge/badges'),
		{ params: { fileIds: fileIds.join(',') } },
	)
	const badges = response.data?.badges
	if (!badges || typeof badges !== 'object') {
		return {}
	}
	return Object.fromEntries(
		Object.entries(badges)
			.filter(([, vendor]) => typeof vendor === 'string' && vendor !== '')
			.map(([id, vendor]) => [String(id), vendor]),
	)
}

/**
 * The badge text for a file from a case system.
 *
 * @param {string} vendor The case system's vendor.
 * @return {string} "Zaaksysteem: {vendor}".
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
 */
export function sourceBadgeLabel(vendor) {
	return t('filinq', 'Zaaksysteem: {vendor}', { vendor })
}

/**
 * A source's health in words.
 *
 * @param {string} health fresh|stale|failing|inactive.
 * @return {string} The label.
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */
export function healthLabel(health) {
	const labels = {
		fresh: t('filinq', 'Up to date'),
		stale: t('filinq', 'Not synced in the last day'),
		failing: t('filinq', 'Failing'),
		inactive: t('filinq', 'Inactive'),
	}
	return labels[health] || t('filinq', 'Unknown')
}

/**
 * The status-badge colour per health label.
 *
 * @return {object} Label to colour.
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */
export function healthColorMap() {
	return {
		[healthLabel('fresh')]: 'success',
		[healthLabel('stale')]: 'warning',
		[healthLabel('failing')]: 'error',
		[healthLabel('inactive')]: 'default',
	}
}

/**
 * A source type in words.
 *
 * @param {string} type zgw-drc|stuf-zds.
 * @return {string} The label.
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */
export function sourceTypeLabel(type) {
	if (type === 'zgw-drc') {
		return t('filinq', 'ZGW Documenten API')
	}
	if (type === 'stuf-zds') {
		return t('filinq', 'StUF-ZDS')
	}
	return type || '-'
}

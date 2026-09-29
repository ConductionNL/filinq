/**
 * Reversible pseudonymisation: whether a redacted copy kept a key, and restore it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The server decides who may restore and writes every attempt to the audit
 * trail. This module only asks, and turns the answer into what the sidebar and
 * the restore dialog show.
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
 */

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * Whether a redacted copy kept a key and whether the current user may restore it.
 *
 * @param {number} fileId The redacted copy's file id.
 * @return {Promise<object|null>} { linkId, reversible, entryCount, mayRestore }, or null
 *   when the copy has no link or cannot be read.
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
 */
export async function fetchPseudonymStatus(fileId) {
	if (!fileId) {
		return null
	}
	try {
		const { data } = await axios.get(
			generateUrl(`/apps/filinq/api/pseudonymisation/status/${fileId}`),
		)
		return data
	} catch {
		return null
	}
}

/**
 * Restore the names in a redacted copy.
 *
 * @param {string} linkId The anonymisation link.
 * @return {Promise<object>} { ok: true, result } or { ok: false, error }.
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
 */
export async function restoreOriginal(linkId) {
	try {
		const { data } = await axios.post(
			generateUrl(
				`/apps/filinq/api/pseudonymisation/${encodeURIComponent(linkId)}/restore`,
			),
		)
		return { ok: true, result: data }
	} catch (error) {
		return {
			ok: false,
			error:
				error.response?.data?.error
				|| t('filinq', 'The names could not be restored.'),
		}
	}
}

/**
 * What to tell the operator about the key after a reversible run.
 *
 * @param {object|undefined} pseudonymisation The `pseudonymisation` block of the anonymise answer.
 * @return {string} A warning, or '' when there is nothing to warn about.
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.1
 */
export function keyWarning(pseudonymisation) {
	if (
		!pseudonymisation
		|| pseudonymisation.reversible !== true
		|| pseudonymisation.keyKept === true
	) {
		return ''
	}
	const reasons = {
		no_placeholders: t(
			'filinq',
			'No key was kept: none of the replaced values got a numbered placeholder. This copy cannot be restored.',
		),
		link_not_recorded: t(
			'filinq',
			'No key was kept, because the link between the original and this copy could not be saved. This copy cannot be restored.',
		),
		store_failed: t(
			'filinq',
			'No key was kept, because it could not be saved. This copy cannot be restored.',
		),
	}
	return reasons[pseudonymisation.reason] ?? reasons.store_failed
}

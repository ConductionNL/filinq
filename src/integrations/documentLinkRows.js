// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.

import { translatePlural as n } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

/**
 * The rows the documents leaf reports to a "linked" card: one row per dossier
 * the object's files sit in ('Dossier "Fietspad Lindelaan" (3 documents)',
 * board DqZaak "Gekoppeld"), and one row per file that is in no dossier.
 *
 * @param {Array<object>} rows The flat-list rows; `dossier` is `{ id, name }` or null.
 * @return {Array<{label: string, href: string, icon: string}>} The rows, dossiers first.
 * @spec openspec/changes/leaves-library-card/specs/leaves-library-card/spec.md#requirement-the-documents-leaf-reports-to-the-library-card-and-the-linked-card
 */
export function documentLinkRows(rows) {
	const dossiers = new Map()
	const loose = []
	for (const row of Array.isArray(rows) ? rows : []) {
		const dossier = row && row.dossier
		if (dossier && dossier.id) {
			const entry = dossiers.get(dossier.id) || { dossier, count: 0 }
			entry.count++
			dossiers.set(dossier.id, entry)
		} else if (row) {
			loose.push({ label: row.name, href: generateUrl('/f/{fileId}', { fileId: row.fileId }), icon: 'FileDocumentOutline' })
		}
	}
	const grouped = [...dossiers.values()].map(({ dossier, count }) => ({
		label: n('filinq', 'Dossier "{name}" (%n document)', 'Dossier "{name}" (%n documents)', count, { name: dossier.name }),
		href: generateUrl('/apps/filinq/dossiers/{id}', { id: dossier.id }),
		icon: 'FolderOutline',
	}))
	return [...grouped, ...loose]
}

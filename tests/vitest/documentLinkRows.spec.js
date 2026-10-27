// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.

import { describe, expect, it, vi } from 'vitest'
import { documentLinkRows } from '../../src/integrations/documentLinkRows.js'

vi.mock('@nextcloud/router', () => ({ generateUrl: (path, params = {}) => path.replace(/\{(\w+)\}/g, (_m, key) => params[key]) }))
vi.mock('@nextcloud/l10n', () => ({
	translatePlural: (app, one, many, count, vars) => (count === 1 ? one : many).replace('%n', String(count)).replace(/\{(\w+)\}/g, (_m, key) => vars[key]),
}))

/**
 * The documents leaf names the dossier a case's files sit in, as board DqZaak
 * "Gekoppeld" draws it, and lists a file in no dossier on its own.
 *
 * @spec openspec/changes/leaves-library-card/specs/leaves-library-card/spec.md#requirement-the-documents-leaf-reports-to-the-library-card-and-the-linked-card
 */
describe('documentLinkRows', () => {
	const lindelaan = { id: 'd-1', name: 'Fietspad Lindelaan' }

	it('draws one row per dossier with its count, then the loose files', () => {
		const rows = documentLinkRows([
			{ fileId: 1, name: 'advies.pdf', dossier: lindelaan },
			{ fileId: 2, name: 'los.pdf', dossier: null },
			{ fileId: 3, name: 'raadsvoorstel.pdf', dossier: lindelaan },
			{ fileId: 4, name: 'klachten.csv', dossier: lindelaan },
		])
		expect(rows).toEqual([
			{ label: 'Dossier "Fietspad Lindelaan" (3 documents)', href: '/apps/filinq/dossiers/d-1', icon: 'FolderOutline' },
			{ label: 'los.pdf', href: '/f/2', icon: 'FileDocumentOutline' },
		])
	})

	it('lists every file on its own when none is in a dossier', () => {
		expect(documentLinkRows([{ fileId: 7, name: 'a.pdf' }]).map((row) => row.label)).toEqual(['a.pdf'])
		expect(documentLinkRows(undefined)).toEqual([])
	})
})

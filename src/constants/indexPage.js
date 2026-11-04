/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * CnIndexPage props for a page that only lists rows: no selection, no row
 * edit, copy or delete actions, no mass actions and no table/card toggle.
 */

export const LIST_ONLY_INDEX_PAGE = Object.freeze({
	selectable: false,
	showEditAction: false,
	showCopyAction: false,
	showDeleteAction: false,
	showMassImport: false,
	showMassExport: false,
	showMassCopy: false,
	showMassDelete: false,
	showViewToggle: false,
})

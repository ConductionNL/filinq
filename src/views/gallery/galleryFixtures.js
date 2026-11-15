/**
 * The fixed demo content of the component gallery: the table of contents,
 * the icon names and the mock documents and entities the demos render.
 * The gallery copies it into its own state, so a demo that changes a value
 * never changes this module.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

export const GALLERY_FIXTURES = Object.freeze({
	// Table of contents entries (id must match each section's id).
	toc: [
		{ id: 'dd-page-header', label: 'PageHeader' },
		{ id: 'dd-button', label: 'Button' },
		{ id: 'dd-icon', label: 'Icon' },
		{ id: 'dd-view-toggle', label: 'ViewToggle' },
		{ id: 'dd-search-bar', label: 'SearchBar' },
		{ id: 'dd-skeleton', label: 'Skeleton' },
		{ id: 'dd-pagination', label: 'Pagination' },
		{ id: 'dd-card-grid', label: 'CardGrid' },
		{ id: 'dd-document-card', label: 'DocumentCard' },
		{ id: 'dd-data-table', label: 'DataTable' },
		{ id: 'dd-entity-card', label: 'EntityCard' },
		{ id: 'dd-index-page', label: 'IndexPage' },
	],

	// All SVG icons currently in src/assets/icons (png assets excluded —
	// DdIcon only registers .svg files).
	iconNames: [
		'pdf',
		'add',
		'article',
		'close',
		'delete',
		'discover_tune',
		'download',
		'edit_square',
		'error',
		'folder',
		'information-circle-outline',
		'list',
		'lock',
		'search',
		'tiles',
		'visibility_off',
	],

	// Mock documents for card/table demos.
	documents: [
		{
			id: 1,
			fileName: 'Project dossier',
			isFolder: true,
			isAnonymized: false,
			modified: 1717500000,
		},
		{
			id: 2,
			fileName: 'Contract-2026.docx',
			isFolder: false,
			isAnonymized: false,
			modified: 1717400000,
			mimeType:
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		},
		{
			id: 3,
			fileName: 'Verslag-anon.pdf',
			isFolder: false,
			isAnonymized: true,
			modified: 1717300000,
			mimeType: 'application/pdf',
		},
	],

	// Column defs for the table demos.
	tableColumns: [
		{ key: 'fileName', label: 'Name' },
		{ key: 'kind', label: 'Kind', width: '160px' },
	],

	// Entity-card demo data.
	basesOptions: [
		{ id: 'consent', label: 'Consent' },
		{ id: 'contract', label: 'Contract' },
		{ id: 'legal', label: 'Legal obligation' },
	],

	reviewEntity: {
		type: 'PERSON',
		value: 'Jane Doe',
		confidence: 0.97,
		included: true,
		_decisionBases: [],
		relationIds: ['rel-1'],
		_patchError: null,
	},

	anonymizedEntity: {
		type: 'PERSON',
		value: 'Jane Doe',
		placeholder: '[PERSON: 1]',
		count: 3,
		bases: ['Consent'],
		_resolveError: null,
	},
})

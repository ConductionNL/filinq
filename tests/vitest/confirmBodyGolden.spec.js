// @vitest-environment jsdom
/**
 * Golden: the body of a classification confirmation, recorded on the
 * single-function implementation.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#3-1
 */

import { describe, it } from 'vitest'
import { confirmBody } from '../../src/services/classification.js'
import { golden } from './golden/golden.js'

const RECORDS = [
	{},
	{
		suggestedDocumentType: 'invoice',
		suggestedCorrespondent: { name: 'ACME', entityType: 'ORGANIZATION' },
		suggestedDossier: 'd-1',
	},
	{ suggestedDocumentType: 'letter', suggestedCorrespondent: { name: 'Jan' } },
	{ suggestedCorrespondent: null, suggestedDossier: '' },
]

const CHOICES = [
	undefined,
	{},
	{ documentType: 'invoice' },
	{ documentType: 'letter' },
	{ documentType: '' },
	{ correspondentName: 'ACME' },
	{ correspondentName: ' ACME ' },
	{ correspondentName: 'Piet' },
	{ correspondentName: '' },
	{ correspondentName: '   ' },
	{ correspondentName: null },
	{ correspondentName: 0 },
	{ correspondentName: 'Jan' },
	{ dossier: 'd-1' },
	{ dossier: 'd-2' },
	{ dossier: '' },
	{ dossier: null },
	{ dossier: 0 },
	{ documentType: 'memo', correspondentName: 'X', dossier: 'd-3' },
]

describe('confirmBody golden', () => {
	it('builds the recorded body for every record and choice', () => {
		golden(
			'confirmBody',
			RECORDS.flatMap((record) =>
				CHOICES.map((choices) => confirmBody(record, choices)),
			),
		)
	})
})

/**
 * Canonical Woo Art. 5 grondslagen seeded by the `dossier` register
 * (Wave 1.1 + Wave 4a) and used as the fallback list across the
 * anonymisation flows.
 *
 * Single source of truth — previously this list was duplicated between
 * `store/modules/folderAnonymization.js` and the original
 * `views/anonymization/AnonymizationWidget.vue`. Both should import from
 * here; the PoC flow additionally tries to GET the live list from
 * `/apps/openregister/api/objects/dossier/base` and falls back to this
 * array on any error so the UI keeps working without a seeded register.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */
export const WOO_BASES = [
	'art-5-1-1-a',
	'art-5-1-1-b',
	'art-5-1-1-c',
	'art-5-1-1-d',
	'art-5-1-1-e',
	'art-5-1-2-a',
	'art-5-1-2-b',
	'art-5-1-2-c',
	'art-5-1-2-d',
	'art-5-1-2-e',
	'art-5-1-2-f',
	'art-5-1-2-g',
	'art-5-1-2-h',
	'art-5-1-2-i',
	'art-5-1-4',
	'art-5-1-5',
	'art-5-1-6',
	'art-5-2-1',
	'art-5-2-2',
]

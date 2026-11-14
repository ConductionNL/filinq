/**
 * Canonical Woo Art. 5 grondslagen, as dossiq's refusal ground codes
 * (grondslagen-read-from-dossiq: the stored value is the code, the old
 * `art-*` slugs are re-pointed by the RepointWooGrounds repair). Formerly
 * seeded by the `filinq` register
 * (Wave 1.1 + Wave 4a; seeded by the `dossier` register until the five
 * registers were consolidated into one) and used as the fallback list
 * across the anonymisation flows.
 *
 * Single source of truth — previously this list was duplicated between
 * `store/modules/folderAnonymization.js` and the original
 * `views/anonymization/AnonymizationWidget.vue`. Both should import from
 * here. The pickers read the live list from `/apps/filinq/api/grounds`
 * (src/services/bases.js); this array is the folder flow's static list.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */
export const WOO_BASES = [
	'5.1.1.a',
	'5.1.1.b',
	'5.1.1.c',
	'5.1.1.d',
	'5.1.1.e',
	'5.1.2.a',
	'5.1.2.b',
	'5.1.2.c',
	'5.1.2.d',
	'5.1.2.e',
	'5.1.2.f',
	'5.1.2.g',
	'5.1.2.h',
	'5.1.2.i',
	'5.1.4',
	'5.1.5',
	'5.1.6',
	'5.2.1',
	'5.2.2',
]

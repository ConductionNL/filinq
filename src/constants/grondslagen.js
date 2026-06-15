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
	'persoonsgegevens',
	'bijzondere-persoonsgegevens',
	'strafrechtelijk',
	'bedrijfs-fabricagegegevens',
	'onevenredige-benadeling',
	'nationale-veiligheid',
]

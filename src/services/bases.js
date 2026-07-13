/**
 * Grondslagen (Woo Art. 5 bases) options — fetched from the register.
 *
 * The `base` objects are seeded in the OpenRegister `dossier` register
 * (docudesk_register.json). The picker options are fetched live so the
 * label shows each grondslag's human name (e.g. "J — Persoonlijke
 * levenssfeer") while the stored value stays the stable slug (e.g.
 * "art-5-1-2-e"), and tenant-added grondslagen surface automatically.
 *
 * Replaces the hardcoded BASES_OPTIONS mirrors that previously lived in
 * EntityReviewTable.vue and FileViewerSidebar.vue. On any error the slug
 * fallback (WOO_BASES) is returned so the UI keeps working without a
 * seeded/reachable register.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { WOO_BASES } from '../constants/grondslagen.js'

/**
 * Slug-only fallback, shaped as {label, value} so the picker renders it too.
 *
 * @type {Array<{label: string, value: string}>}
 */
const FALLBACK_OPTIONS = WOO_BASES.map((slug) => ({ label: slug, value: slug }))

/**
 * Fetch the grondslagen (base) options from the OpenRegister dossier register.
 *
 * @return {Promise<Array<{label: string, value: string}>>} Options for NcSelect
 *   ({label: name, value: slug}); the slug fallback on any error.
 */
export async function fetchBaseOptions() {
	try {
		const response = await axios.get(
			generateUrl('/apps/openregister/api/objects/dossier/base'),
		)
		const raw = Array.isArray(response.data)
			? response.data
			: (response.data?.results || [])
		const options = raw
			.map((obj) => {
				const value = obj?.['@self']?.slug || obj?.slug || obj?.id
				const label = obj?.name || value
				return value ? { label, value } : null
			})
			.filter(Boolean)
		return options.length > 0 ? options : FALLBACK_OPTIONS
	} catch (err) {
		return FALLBACK_OPTIONS
	}
}

/* eslint-disable no-console */
// SPDX-License-Identifier: EUPL-1.2
// Copyright (C) 2026 Conduction B.V.
//
// Store barrel — Phase 1 of the @conduction/nextcloud-vue store migration
// (openspec/changes/docudesk-store-migration). The lib's `useObjectStore`
// is added side-by-side with the existing eight legacy docudesk-specific
// stores; the legacy stores stay intact for every existing Vue consumer,
// and the lib store is pre-configured with the seven OR-backed object
// types declared in `lib/Settings/docudesk_register.json` so manifest
// pages and lib sub-resource plugins (live updates, audit trails, files,
// relations) have a known-shape store to bind against.
//
// See openspec/changes/docudesk-store-migration/design.md for the
// pattern rationale and the Phase 2 cutover triggers.

import { generateUrl } from '@nextcloud/router'
import { createObjectStore } from '@conduction/nextcloud-vue'
import pinia from '../pinia.js'
import { useNavigationStore } from './modules/navigation.ts'
import { useConsentStore } from './modules/consent.js'
import { useAnonymizationStore } from './modules/anonymization.js'
import { useFolderAnonymizationStore } from './modules/folderAnonymization.js'
import { useMyDocumentsStore } from './modules/myDocuments.js'
import { useFileViewerStore } from './modules/fileViewer.js'
import { useProhibitionStore } from './modules/prohibition.js'
import { useStandingConsentStore } from './modules/standingConsent.js'
import { useCustomDictionaryStore } from './modules/customDictionary.js'
import { useSettingsStore } from './modules/settings.js'

// Lib store — registered exactly once at module load with the
// docudesk-specific Pinia id `'docudesk-objects'`. Mirrors the
// decidesk PR #163 precedent (which uses `'decidesk-objects'`) and the
// zaakafhandelapp PR #190 side-by-side pattern.
//
// NOTE: as of `@conduction/nextcloud-vue@1.0.0-beta.212`,
// `liveUpdatesPlugin` is installed by `createObjectStore` BY DEFAULT
// (opt-out via `{ liveUpdates: false }`). This store therefore exposes
// `subscribe(type, id?)` / `unsubscribe(handle)` without an explicit
// plugins entry. The plugin is lazy: zero transport activity until the
// first `subscribe()` call. No docudesk view subscribes yet — every
// hand-written view still consumes the legacy docudesk-specific stores
// below (Phase 1 of the store migration), so there is no
// createObjectStore-backed list/detail view to wire. Wiring live
// updates is a Phase 2 concern, once views cut over to this lib store.
const useObjectStore = createObjectStore('docudesk-objects')

// Legacy docudesk-specific stores — preserved verbatim for every existing
// Vue consumer. These talk to docudesk-specific REST controllers and do
// NOT match the OR canonical shape; replacing them is Phase 2 work, gated
// on the triggers listed in the change's design.md.
const navigationStore = useNavigationStore(pinia)
const consentStore = useConsentStore(pinia)
const anonymizationStore = useAnonymizationStore(pinia)
const folderAnonymizationStore = useFolderAnonymizationStore(pinia)
const myDocumentsStore = useMyDocumentsStore(pinia)
const fileViewerStore = useFileViewerStore(pinia)
const prohibitionStore = useProhibitionStore(pinia)
const standingConsentStore = useStandingConsentStore(pinia)
const customDictionaryStore = useCustomDictionaryStore(pinia)

// OR-backed object types declared by lib/Settings/docudesk_register.json.
// Triple is (consumer-facing slug, OR schema slug, OR register slug).
// Slug values are kept verbatim from the register JSON so any future
// manifest renderer can resolve them lossless-ly.
const OBJECT_TYPES = Object.freeze([
	['consent', 'publicationConsent', 'consent'],
	['signing-request', 'signingRequest', 'signing'],
	['signer-record', 'signerRecord', 'signing'],
	['signing-audit-entry', 'signingAuditEntry', 'signing'],
	['template', 'template', 'templates'],
	['correspondence', 'correspondence', 'document'],
	['huisstijl', 'huisstijl', 'document'],
])

let initialized = false

/**
 * Initialise all stores that require async setup.
 *
 * Phase 1 of the @conduction/nextcloud-vue store migration:
 *   1. Await the docudesk-specific settings fetch (preserves the
 *      pre-migration boot behaviour exactly).
 *   2. Configure the lib's `useObjectStore` with the canonical OR base
 *      URL and register the seven OR-backed object types declared in
 *      `lib/Settings/docudesk_register.json`.
 *
 * Idempotent — guarded by a module-scoped `initialized` flag so calling
 * this twice short-circuits without re-fetching settings or
 * re-registering object types.
 *
 * @return {Promise<ReturnType<typeof useObjectStore>>} The configured lib store.
 */
async function initializeStores() {
	const objectStore = useObjectStore(pinia)

	if (initialized) {
		return objectStore
	}

	const settingsStore = useSettingsStore(pinia)
	await settingsStore.fetchSettings()

	objectStore.configure({
		baseUrl: generateUrl('/apps/openregister/api/objects'),
	})

	for (const [slug, schema, register] of OBJECT_TYPES) {
		objectStore.registerObjectType(slug, schema, register)
	}

	initialized = true
	return objectStore
}

export {
	// Lib store — adopt for new code, manifest pages, and any consumer
	// needing the lib's sub-resource plugins (live updates, audit, files,
	// relations).
	useObjectStore,
	// Legacy docudesk-specific stores — preserved for Phase 1 compatibility.
	navigationStore,
	consentStore,
	anonymizationStore,
	folderAnonymizationStore,
	myDocumentsStore,
	fileViewerStore,
	prohibitionStore,
	standingConsentStore,
	customDictionaryStore,
	useSettingsStore,
	initializeStores,
}

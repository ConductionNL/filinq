<?php

/**
 * Application route definitions for DocuDesk.
 *
 * @category  AppInfo
 * @package   OCA\DocuDesk\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

// The mechanical boilerplate routes — `dashboard#page` (`/`) and the SPA
// catch-all (`/{path}`, `dashboard#catchAll`) — are supplied by
// `Routes::standard()`; both resolve to docudesk's `DashboardController`
// (which extends the AppHost `GenericDashboardController`), so the route name
// `docudesk.dashboard.page` that the navigation (info.xml) and the dashboard
// widgets link to is defined. The app-specific API routes below are passed
// through as `$extra` and inserted before the catch-all.
return \OCA\OpenRegister\AppHost\Routes::standard([
        // Metrics and health.
        ['name' => 'metrics#index', 'url' => 'api/metrics', 'verb' => 'GET'],
        ['name' => 'health#index', 'url' => 'api/health', 'verb' => 'GET'],

        // Settings routes.
        ['name' => 'settings#index', 'url' => 'api/settings', 'verb' => 'GET'],
        ['name' => 'settings#create', 'url' => 'api/settings', 'verb' => 'POST'],

        // Consent routes.
        ['name' => 'consent#index', 'url' => 'api/consents', 'verb' => 'GET'],
        ['name' => 'consent#create', 'url' => 'api/consents', 'verb' => 'POST'],
        ['name' => 'consent#show', 'url' => 'api/consents/{id}', 'verb' => 'GET'],
        ['name' => 'consent#update', 'url' => 'api/consents/{id}', 'verb' => 'PUT'],
        ['name' => 'consent#byDocument', 'url' => 'api/consents/document/{documentId}', 'verb' => 'GET'],

        // Metadata enrichment route.
        ['name' => 'metadata#enrich', 'url' => 'api/metadata/enrich', 'verb' => 'POST'],

        // Document validation route.
        ['name' => 'validation#validate', 'url' => 'api/validation/validate', 'verb' => 'POST'],

        // Document comparison route.
        ['name' => 'comparison#compare', 'url' => 'api/comparison/compare', 'verb' => 'POST'],

        // Document version routes (Versies detail tab — thin files_versions consumer).
        ['name' => 'version#index', 'url' => 'api/documents/{fileId}/versions', 'verb' => 'GET'],
        ['name' => 'version#download', 'url' => 'api/documents/{fileId}/versions/{versionTimestamp}/download', 'verb' => 'GET'],
        ['name' => 'version#restore', 'url' => 'api/documents/{fileId}/versions/{versionTimestamp}/restore', 'verb' => 'POST'],

        // Anonymization routes.
        ['name' => 'anonymization#files', 'url' => 'api/anonymization/files', 'verb' => 'GET'],
        ['name' => 'anonymization#upload', 'url' => 'api/anonymization/upload', 'verb' => 'POST'],
        ['name' => 'anonymization#extract', 'url' => 'api/anonymization/extract/{fileId}', 'verb' => 'POST'],
        ['name' => 'anonymization#anonymize', 'url' => 'api/anonymization/anonymize/{fileId}', 'verb' => 'POST'],
        ['name' => 'anonymization#updateRelation', 'url' => 'api/anonymization/relations/{id}', 'verb' => 'PATCH', 'requirements' => ['id' => '\\d+']],

        // Original-EML preview (renders the source message to a PDF for the viewer).
        ['name' => 'emlPreview#preview', 'url' => 'api/anonymization/eml-preview/{fileId}', 'verb' => 'GET'],

        // Dossier anonymization routes.
        ['name' => 'dossier#generateGrondslagenSummary', 'url' => 'api/anonymization/dossier/{dossierId}/grondslagen-pdf', 'verb' => 'POST'],

        // Batch anonymization routes.
        ['name' => 'batchAnonymization#folderBatch', 'url' => 'api/anonymization/batch/folder', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchUpload', 'url' => 'api/anonymization/batch/upload', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchExtract', 'url' => 'api/anonymization/batch/{batchId}/extract', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchStatus', 'url' => 'api/anonymization/batch/{batchId}/status', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#batchEntities', 'url' => 'api/anonymization/batch/{batchId}/entities', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#batchAnonymize', 'url' => 'api/anonymization/batch/{batchId}/anonymize', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchReport', 'url' => 'api/anonymization/batch/{batchId}/report', 'verb' => 'GET'],

        // WOO entity profile routes.
        ['name' => 'batchAnonymization#getProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#updateProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'PUT'],

        // Policy — prohibition routes.
        ['name' => 'policy#indexProhibitions', 'url' => 'api/policy/prohibitions', 'verb' => 'GET'],
        ['name' => 'policy#createProhibition', 'url' => 'api/policy/prohibitions', 'verb' => 'POST'],
        ['name' => 'policy#showProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'GET'],
        ['name' => 'policy#updateProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'PUT'],
        ['name' => 'policy#deleteProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'DELETE'],

        // Policy — standing consent routes.
        ['name' => 'policy#indexStandingConsents', 'url' => 'api/policy/standing-consents', 'verb' => 'GET'],
        ['name' => 'policy#createStandingConsent', 'url' => 'api/policy/standing-consents', 'verb' => 'POST'],
        ['name' => 'policy#showStandingConsent', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'GET'],
        ['name' => 'policy#updateStandingConsent', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'PUT'],
        ['name' => 'policy#deleteStandingConsent', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'DELETE'],

        // Custom dictionaries — organisation-managed term-list CRUD + import
        // (custom-dictionary-recognition).
        ['name' => 'customDictionary#index', 'url' => 'api/custom-dictionaries', 'verb' => 'GET'],
        ['name' => 'customDictionary#create', 'url' => 'api/custom-dictionaries', 'verb' => 'POST'],
        ['name' => 'customDictionary#show', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'GET'],
        ['name' => 'customDictionary#update', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'PUT'],
        ['name' => 'customDictionary#destroy', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'DELETE'],
        ['name' => 'customDictionary#indexTerms', 'url' => 'api/custom-dictionaries/{id}/terms', 'verb' => 'GET'],
        ['name' => 'customDictionary#createTerm', 'url' => 'api/custom-dictionaries/{id}/terms', 'verb' => 'POST'],
        ['name' => 'customDictionary#deleteTerm', 'url' => 'api/custom-dictionaries/{id}/terms/{termId}', 'verb' => 'DELETE'],
        ['name' => 'customDictionary#import', 'url' => 'api/custom-dictionaries/{id}/import', 'verb' => 'POST'],

        // PDF generation routes.
        ['name' => 'pdf#render', 'url' => 'api/pdf/render', 'verb' => 'POST'],
        ['name' => 'pdf#renderPdfA', 'url' => 'api/pdf/render-pdfa', 'verb' => 'POST'],

        // PDF/A-3 archival conversion route (MDTO/e-depot; consumed by
        // procest's beschikking pipeline and OpenRegister's TMLO/MDTO SIP builder).
        ['name' => 'pdfa3Conversion#convert', 'url' => 'api/pdfa3/convert', 'verb' => 'POST'],

        // Print preview and PDF/A download routes.
        ['name' => 'print#preview', 'url' => 'api/print/preview', 'verb' => 'POST'],
        ['name' => 'print#downloadPdfA', 'url' => 'api/print/pdf-a', 'verb' => 'POST'],

        // Print job queue routes (for external print services).
        ['name' => 'printJob#create', 'url' => 'api/print/jobs', 'verb' => 'POST'],
        ['name' => 'printJob#batch', 'url' => 'api/print/batch', 'verb' => 'POST'],
        ['name' => 'printJob#show', 'url' => 'api/print/jobs/{id}', 'verb' => 'GET'],
        ['name' => 'printJob#download', 'url' => 'api/print/jobs/{id}/download', 'verb' => 'GET'],
        ['name' => 'printJob#updateStatus', 'url' => 'api/print/jobs/{id}/status', 'verb' => 'PUT'],

        // Document generation routes (document-creatie-sjablonen).
        ['name' => 'document#generate', 'url' => 'api/documents/generate', 'verb' => 'POST'],
        ['name' => 'document#preview', 'url' => 'api/documents/generate/preview', 'verb' => 'POST'],
        ['name' => 'document#generateBulk', 'url' => 'api/documents/generate/bulk', 'verb' => 'POST'],
        ['name' => 'document#jobStatus', 'url' => 'api/documents/jobs/{jobId}', 'verb' => 'GET'],

        // Correspondence routes.
        ['name' => 'correspondence#generate', 'url' => 'api/correspondence/generate', 'verb' => 'POST'],
        ['name' => 'correspondence#generateBatch', 'url' => 'api/correspondence/generate/batch', 'verb' => 'POST'],
        ['name' => 'correspondence#jobStatus', 'url' => 'api/correspondence/jobs/{jobId}', 'verb' => 'GET'],

        // Template routes.
        ['name' => 'templates#index', 'url' => 'api/templates', 'verb' => 'GET'],
        ['name' => 'templates#create', 'url' => 'api/templates', 'verb' => 'POST'],
        ['name' => 'templates#preview', 'url' => 'api/templates/preview', 'verb' => 'POST'],
        ['name' => 'templates#show', 'url' => 'api/templates/{id}', 'verb' => 'GET'],
        ['name' => 'templates#update', 'url' => 'api/templates/{id}', 'verb' => 'PUT'],
        ['name' => 'templates#destroy', 'url' => 'api/templates/{id}', 'verb' => 'DELETE'],
        ['name' => 'templates#versions', 'url' => 'api/templates/{id}/versions', 'verb' => 'GET'],
        ['name' => 'templates#diffVersions', 'url' => 'api/templates/{id}/versions/diff', 'verb' => 'GET'],
        ['name' => 'templates#restoreVersion', 'url' => 'api/templates/{id}/versions/{versionId}/restore', 'verb' => 'POST'],
        ['name' => 'templates#previewTemplate', 'url' => 'api/templates/{id}/preview', 'verb' => 'POST'],
        ['name' => 'templates#duplicate', 'url' => 'api/templates/{id}/duplicate', 'verb' => 'POST'],
        ['name' => 'templates#lock', 'url' => 'api/templates/{id}/lock', 'verb' => 'POST'],
        ['name' => 'templates#unlock', 'url' => 'api/templates/{id}/lock', 'verb' => 'DELETE'],

        // Signing routes.
        ['name' => 'signing#createRequest', 'url' => 'api/signing/requests', 'verb' => 'POST'],
        ['name' => 'signing#listRequests', 'url' => 'api/signing/requests', 'verb' => 'GET'],
        ['name' => 'signing#showRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'GET'],
        ['name' => 'signing#cancelRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'DELETE'],
        ['name' => 'signing#sign', 'url' => 'api/signing/requests/{id}/sign', 'verb' => 'POST'],
        ['name' => 'signing#decline', 'url' => 'api/signing/requests/{id}/decline', 'verb' => 'POST'],
        ['name' => 'signing#bulkSign', 'url' => 'api/signing/bulk', 'verb' => 'POST'],
        ['name' => 'signing#verify', 'url' => 'api/signing/verify/{fileId}', 'verb' => 'GET'],
        ['name' => 'signing#getAudit', 'url' => 'api/signing/requests/{id}/audit', 'verb' => 'GET'],

        // Portal signing receiver routes (portal-signing-actions,
        // portal-signing-surface): the A6 endpoint-forward targets portaliq
        // calls server-to-server on behalf of an external, accountless
        // signer — #[PublicPage] because the caller is portaliq's backend,
        // never a browser; the X-Portal-Subject assertion IS the auth.
        ['name' => 'portalsigningreceiver#signDocument', 'url' => 'api/portal/signing/sign', 'verb' => 'POST'],
        ['name' => 'portalsigningreceiver#declineDocument', 'url' => 'api/portal/signing/decline', 'verb' => 'POST'],
        ['name' => 'portalsigningreceiver#viewDocument', 'url' => 'api/portal/signing/viewDocument', 'verb' => 'GET'],

        // Financial extraction routes (scan-en-herken).
        ['name' => 'extraction#financial', 'url' => 'api/extraction/financial', 'verb' => 'POST'],
        ['name' => 'extraction#corrections', 'url' => 'api/extraction/{id}/corrections', 'verb' => 'POST'],

        // GL-account ("grootboekrekening") suggestion route (ai-gl-account-suggestion).
        ['name' => 'glAccountSuggestion#suggestAccount', 'url' => 'api/extraction/{id}/suggest-account', 'verb' => 'POST'],

        // Anonymiser warning dismissal routes (admin-only, per-user).
        ['name' => 'anonymiserWarning#dismiss', 'url' => 'api/admin/anonymiser-warning/dismiss', 'verb' => 'POST'],
        ['name' => 'anonymiserWarning#reset', 'url' => 'api/admin/anonymiser-warning/reset', 'verb' => 'POST'],

        // Generic per-user preferences (used by shared nextcloud-vue widgets, e.g.
        // CnSupportDialog) — served by OpenRegister's AppHost
        // GenericPreferencesController (aliased in Application::register).
        // Served by lib/Controller/PreferencesController (a thin subclass of
        // OpenRegister's GenericPreferencesController). The route name MUST
        // stay `preferences#…`: Nextcloud resolves `foo#bar` to
        // OCA\DocuDesk\Controller\FooController, so a namespaced name here
        // resolves to a class that does not exist and 500s.
        ['name' => 'preferences#getPreference', 'url' => '/api/preferences/{key}', 'verb' => 'GET'],
        ['name' => 'preferences#setPreference', 'url' => '/api/preferences/{key}', 'verb' => 'PUT'],
]);

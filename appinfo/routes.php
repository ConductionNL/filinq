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

return [
    'routes' => [
        // Metrics and health.
        ['name' => 'metrics#index', 'url' => 'api/metrics', 'verb' => 'GET'],
        ['name' => 'health#index', 'url' => 'api/health', 'verb' => 'GET'],

        // Dashboard.
        ['name' => 'dashboard#page', 'url' => '/', 'verb' => 'GET'],

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

        // Anonymization routes.
        ['name' => 'anonymization#files', 'url' => 'api/anonymization/files', 'verb' => 'GET'],
        ['name' => 'anonymization#upload', 'url' => 'api/anonymization/upload', 'verb' => 'POST'],
        ['name' => 'anonymization#extract', 'url' => 'api/anonymization/extract/{fileId}', 'verb' => 'POST'],
        ['name' => 'anonymization#anonymize', 'url' => 'api/anonymization/anonymize/{fileId}', 'verb' => 'POST'],

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

        // Dossier routes.
        ['name' => 'dossier#generateGrondslagenSummary', 'url' => 'api/anonymization/dossier/{dossierId}/grondslagen-pdf', 'verb' => 'POST'],

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

        // PDF generation routes.
        ['name' => 'pdf#render', 'url' => 'api/pdf/render', 'verb' => 'POST'],
        ['name' => 'pdf#renderPdfA', 'url' => 'api/pdf/render-pdfa', 'verb' => 'POST'],

        // Print preview and PDF/A download routes.
        ['name' => 'print#preview', 'url' => 'api/print/preview', 'verb' => 'POST'],
        ['name' => 'print#downloadPdfA', 'url' => 'api/print/pdf-a', 'verb' => 'POST'],

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

        // Generic per-user preferences (used by shared nextcloud-vue widgets, e.g. CnSupportDialog).
        ['name' => 'preferences#getPreference', 'url' => '/api/preferences/{key}', 'verb' => 'GET'],
        ['name' => 'preferences#setPreference', 'url' => '/api/preferences/{key}', 'verb' => 'PUT'],

        // SPA catch-all — serves the Vue app shell for any frontend deep
        // link (vue-router HTML5 history mode). Must come LAST so it
        // doesn't shadow specific routes above. The dedicated
        // `dashboard#catchAll` controller method exists because
        // `dashboard#page` takes a `getParameter` arg, not a `path`
        // arg — matching arg names to the route placeholder lets NC's
        // router inject the captured value cleanly.
        ['name' => 'dashboard#catchAll', 'url' => '/{path}', 'verb' => 'GET', 'requirements' => ['path' => '.+'], 'defaults' => ['path' => '']],
    ],
];

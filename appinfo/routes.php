<?php

/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

return [
	'routes' => [
		// Metrics and health
		['name' => 'metrics#index', 'url' => 'api/metrics', 'verb' => 'GET'],
		['name' => 'health#index', 'url' => 'api/health', 'verb' => 'GET'],

		// Dashboard
		['name' => 'dashboard#page', 'url' => '/', 'verb' => 'GET'],

		// Settings routes
		['name' => 'settings#index', 'url' => 'api/settings', 'verb' => 'GET'],
		['name' => 'settings#create', 'url' => 'api/settings', 'verb' => 'POST'],

		// Consent routes
		['name' => 'consent#index', 'url' => 'api/consents', 'verb' => 'GET'],
		['name' => 'consent#create', 'url' => 'api/consents', 'verb' => 'POST'],
		['name' => 'consent#show', 'url' => 'api/consents/{id}', 'verb' => 'GET'],
		['name' => 'consent#update', 'url' => 'api/consents/{id}', 'verb' => 'PUT'],
		['name' => 'consent#byDocument', 'url' => 'api/consents/document/{documentId}', 'verb' => 'GET'],

		// Metadata enrichment route
		['name' => 'metadata#enrich', 'url' => 'api/metadata/enrich', 'verb' => 'POST'],

		// Anonymization routes
		['name' => 'anonymization#files', 'url' => 'api/anonymization/files', 'verb' => 'GET'],
		['name' => 'anonymization#upload', 'url' => 'api/anonymization/upload', 'verb' => 'POST'],
		['name' => 'anonymization#extract', 'url' => 'api/anonymization/extract/{fileId}', 'verb' => 'POST'],
		['name' => 'anonymization#anonymize', 'url' => 'api/anonymization/anonymize/{fileId}', 'verb' => 'POST'],

		// Batch anonymization routes
		['name' => 'batch_anonymization#batchUpload', 'url' => 'api/anonymization/batch/upload', 'verb' => 'POST'],
		['name' => 'batch_anonymization#batchExtract', 'url' => 'api/anonymization/batch/{batchId}/extract', 'verb' => 'POST'],
		['name' => 'batch_anonymization#batchStatus', 'url' => 'api/anonymization/batch/{batchId}/status', 'verb' => 'GET'],
		['name' => 'batch_anonymization#batchEntities', 'url' => 'api/anonymization/batch/{batchId}/entities', 'verb' => 'GET'],
		['name' => 'batch_anonymization#batchAnonymize', 'url' => 'api/anonymization/batch/{batchId}/anonymize', 'verb' => 'POST'],
		['name' => 'batch_anonymization#batchReport', 'url' => 'api/anonymization/batch/{batchId}/report', 'verb' => 'GET'],
		['name' => 'batch_anonymization#getProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'GET'],
		['name' => 'batch_anonymization#updateProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'PUT'],

		// PDF generation route
		['name' => 'pdf#render', 'url' => 'api/pdf/render', 'verb' => 'POST'],

		// Template routes
		['name' => 'templates#index', 'url' => 'api/templates', 'verb' => 'GET'],
		['name' => 'templates#create', 'url' => 'api/templates', 'verb' => 'POST'],
		['name' => 'templates#show', 'url' => 'api/templates/{id}', 'verb' => 'GET'],
		['name' => 'templates#update', 'url' => 'api/templates/{id}', 'verb' => 'PUT'],
		['name' => 'templates#destroy', 'url' => 'api/templates/{id}', 'verb' => 'DELETE'],

		// Signing routes
		['name' => 'signing#createRequest', 'url' => 'api/signing/requests', 'verb' => 'POST'],
		['name' => 'signing#listRequests', 'url' => 'api/signing/requests', 'verb' => 'GET'],
		['name' => 'signing#showRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'GET'],
		['name' => 'signing#cancelRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'DELETE'],
		['name' => 'signing#sign', 'url' => 'api/signing/requests/{id}/sign', 'verb' => 'POST'],
		['name' => 'signing#decline', 'url' => 'api/signing/requests/{id}/decline', 'verb' => 'POST'],
		['name' => 'signing#bulkSign', 'url' => 'api/signing/bulk', 'verb' => 'POST'],
		['name' => 'signing#verify', 'url' => 'api/signing/verify/{fileId}', 'verb' => 'GET'],
		['name' => 'signing#getAudit', 'url' => 'api/signing/requests/{id}/audit', 'verb' => 'GET'],

		// SPA catch-all — serves the Vue app for any frontend route (history mode)
		['name' => 'dashboard#page', 'url' => '/{path}', 'verb' => 'GET', 'requirements' => ['path' => '.+'], 'defaults' => ['path' => '']],
	],
];

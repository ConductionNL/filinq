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

		// PDF generation route
		['name' => 'pdf#render', 'url' => 'api/pdf/render', 'verb' => 'POST'],

		// Correspondence routes
		['name' => 'correspondence#generate', 'url' => 'api/correspondence/generate', 'verb' => 'POST'],
		['name' => 'correspondence#generateBatch', 'url' => 'api/correspondence/generate/batch', 'verb' => 'POST'],
		['name' => 'correspondence#jobStatus', 'url' => 'api/correspondence/jobs/{jobId}', 'verb' => 'GET'],

		// Template routes
		['name' => 'templates#index', 'url' => 'api/templates', 'verb' => 'GET'],
		['name' => 'templates#create', 'url' => 'api/templates', 'verb' => 'POST'],
		['name' => 'templates#show', 'url' => 'api/templates/{id}', 'verb' => 'GET'],
		['name' => 'templates#update', 'url' => 'api/templates/{id}', 'verb' => 'PUT'],
		['name' => 'templates#destroy', 'url' => 'api/templates/{id}', 'verb' => 'DELETE'],

		// SPA catch-all — serves the Vue app for any frontend route (history mode)
		['name' => 'dashboard#page', 'url' => '/{path}', 'verb' => 'GET', 'requirements' => ['path' => '.+'], 'defaults' => ['path' => '']],
	],
];

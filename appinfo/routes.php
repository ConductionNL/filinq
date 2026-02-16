<?php

/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

return [
	'routes' => [
		// Dashboard
		['name' => 'dashboard#page', 'url' => '/', 'verb' => 'GET'],

		// Settings routes
		['name' => 'settings#index', 'url' => 'api/settings', 'verb' => 'GET'],
		['name' => 'settings#create', 'url' => 'api/settings', 'verb' => 'POST'],

		// Consent routes
		['name' => 'consent#index', 'url' => 'api/consents', 'verb' => 'GET'],
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
	],
];

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
		['name' => 'settings#testPresidioAnalyzer', 'url' => 'api/settings/test-presidio-analyzer', 'verb' => 'POST'],
		['name' => 'settings#testPresidioAnonymizer', 'url' => 'api/settings/test-presidio-anonymizer', 'verb' => 'POST'],
		['name' => 'settings#getApiConfig', 'url' => 'api/settings/api-config', 'verb' => 'GET'],
		['name' => 'settings#saveApiConfig', 'url' => 'api/settings/api-config', 'verb' => 'POST'],
		
		// Document API routes (via OpenRegister)
		['name' => 'document#index', 'url' => 'api/documents', 'verb' => 'GET'],
		['name' => 'document#create', 'url' => 'api/documents', 'verb' => 'POST'],
		['name' => 'document#show', 'url' => 'api/documents/{id}', 'verb' => 'GET'],
		['name' => 'document#update', 'url' => 'api/documents/{id}', 'verb' => 'PUT'],
		['name' => 'document#destroy', 'url' => 'api/documents/{id}', 'verb' => 'DELETE'],
		
		// Anonymization routes
		['name' => 'anonymization#anonymize', 'url' => 'api/anonymize', 'verb' => 'POST'],
		['name' => 'anonymization#preview', 'url' => 'api/anonymize/preview', 'verb' => 'POST'],
		['name' => 'anonymization#getRules', 'url' => 'api/anonymize/rules', 'verb' => 'GET'],
		['name' => 'anonymization#updateRules', 'url' => 'api/anonymize/rules', 'verb' => 'PUT'],
		
		// Metadata routes
		['name' => 'metadata#extract', 'url' => 'api/metadata/extract', 'verb' => 'POST'],
		['name' => 'metadata#enhance', 'url' => 'api/metadata/enhance', 'verb' => 'POST'],
		['name' => 'metadata#getMetadata', 'url' => 'api/metadata/{documentId}', 'verb' => 'GET'],
		['name' => 'metadata#updateMetadata', 'url' => 'api/metadata/{documentId}', 'verb' => 'PUT'],
	],
];

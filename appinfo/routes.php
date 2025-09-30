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
		['name' => 'settings#getReportConfig', 'url' => '/api/v1/settings/report', 'verb' => 'GET'],
		['name' => 'settings#saveReportConfig', 'url' => '/api/v1/settings/report', 'verb' => 'POST'],
		
		// Object API routes	
		['name' => 'objects#index', 'url' => 'api/objects/{objectType}', 'verb' => 'GET'],
		['name' => 'objects#create', 'url' => 'api/objects/{objectType}', 'verb' => 'POST'],
		['name' => 'objects#show', 'url' => 'api/objects/{objectType}/{id}', 'verb' => 'GET'],
		['name' => 'objects#update', 'url' => 'api/objects/{objectType}/{id}', 'verb' => 'PUT'],
		['name' => 'objects#destroy', 'url' => 'api/objects/{objectType}/{id}', 'verb' => 'DELETE'],
		['name' => 'objects#lock', 'url' => 'api/objects/{objectType}/{id}/lock', 'verb' => 'POST'],
		['name' => 'objects#unlock', 'url' => 'api/objects/{objectType}/{id}/unlock', 'verb' => 'POST'],
		['name' => 'objects#revert', 'url' => 'api/objects/{objectType}/{id}/revert', 'verb' => 'POST'],
		['name' => 'objects#getAuditTrail', 'url' => 'api/objects/{objectType}/{id}/audit', 'verb' => 'GET'],
		['name' => 'objects#getRelations', 'url' => 'api/objects/{objectType}/{id}/relations', 'verb' => 'GET'],
		['name' => 'objects#getUses', 'url' => 'api/objects/{objectType}/{id}/uses', 'verb' => 'GET'],
		['name' => 'objects#getFiles', 'url' => 'api/objects/{objectType}/{id}/files', 'verb' => 'GET'],
		
		// Report routes
		['name' => 'report#index', 'url' => '/api/v1/reports', 'verb' => 'GET'],
		['name' => 'report#create', 'url' => '/api/v1/reports', 'verb' => 'POST'],
		['name' => 'report#show', 'url' => '/api/v1/reports/{id}', 'verb' => 'GET'],
		['name' => 'report#update', 'url' => '/api/v1/reports/{id}', 'verb' => 'PUT'],
		['name' => 'report#destroy', 'url' => '/api/v1/reports/{id}', 'verb' => 'DELETE'],
		['name' => 'report#getLatestForNode', 'url' => '/api/v1/reports/node/{nodeId}', 'verb' => 'GET'],
		['name' => 'report#process', 'url' => '/api/v1/reports/{id}/process', 'verb' => 'POST'],
	],
];

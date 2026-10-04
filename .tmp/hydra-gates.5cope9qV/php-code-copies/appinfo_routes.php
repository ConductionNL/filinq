<?php

   
                                            
  
                     
                                
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

                                                                         
                                                                
                                                                           
                                                                            
                                                                              
                                                                    
  
                                                                                
                                                                             
                                                                             
                                                                           
                                                                         
                                                                              
                                                                              
                                                                            
                                         
$extra = [
                              
                                                                                   
        ['name' => 'setup#status',    'url' => '/api/setup/status',            'verb' => 'GET'],
        ['name' => 'setup#runAction', 'url' => '/api/setup/action/{actionId}', 'verb' => 'POST', 'requirements' => ['actionId' => '[a-z0-9\\-]+']],
        ['name' => 'setup#saveConfig', 'url' => '/api/setup/config',           'verb' => 'POST'],
        ['name' => 'metrics#index', 'url' => 'api/metrics', 'verb' => 'GET'],
        ['name' => 'health#index', 'url' => 'api/health', 'verb' => 'GET'],

                           
        ['name' => 'settings#index', 'url' => 'api/settings', 'verb' => 'GET'],
        ['name' => 'settings#create', 'url' => 'api/settings', 'verb' => 'POST'],

                          
        ['name' => 'consent#index', 'url' => 'api/consents', 'verb' => 'GET'],
        ['name' => 'consent#create', 'url' => 'api/consents', 'verb' => 'POST'],
        ['name' => 'consent#show', 'url' => 'api/consents/{id}', 'verb' => 'GET'],
        ['name' => 'consent#update', 'url' => 'api/consents/{id}', 'verb' => 'PUT'],
        ['name' => 'consent#byDocument', 'url' => 'api/consents/document/{documentId}', 'verb' => 'GET'],

                                     
        ['name' => 'metadata#enrich', 'url' => 'api/metadata/enrich', 'verb' => 'POST'],

                                     
        ['name' => 'validation#validate', 'url' => 'api/validation/validate', 'verb' => 'POST'],

                                     
        ['name' => 'comparison#compare', 'url' => 'api/comparison/compare', 'verb' => 'POST'],

                                                                                       
        ['name' => 'version#index', 'url' => 'api/documents/{fileId}/versions', 'verb' => 'GET'],
        ['name' => 'version#download', 'url' => 'api/documents/{fileId}/versions/{versionTimestamp}/download', 'verb' => 'GET'],
        ['name' => 'version#restore', 'url' => 'api/documents/{fileId}/versions/{versionTimestamp}/restore', 'verb' => 'POST'],

                                                          
        ['name' => 'finalDocument#show', 'url' => 'api/documents/{fileId}/final', 'verb' => 'GET'],
        ['name' => 'finalDocument#finalise', 'url' => 'api/documents/{fileId}/final', 'verb' => 'POST'],
        ['name' => 'finalDocument#correct', 'url' => 'api/documents/{fileId}/final/correction', 'verb' => 'POST'],
        ['name' => 'finalDocument#unfreeze', 'url' => 'api/documents/{fileId}/final', 'verb' => 'DELETE'],
        ['name' => 'finalDocument#declareRule', 'url' => 'api/document-finality-rules', 'verb' => 'POST'],
        ['name' => 'finalDocument#applyStateChange', 'url' => 'api/document-finality-rules/apply', 'verb' => 'POST'],

                                                                
        ['name' => 'intake#index', 'url' => 'api/intake/documents', 'verb' => 'GET'],
        ['name' => 'intake#assign', 'url' => 'api/intake/documents/{uuid}/assign', 'verb' => 'POST'],
        ['name' => 'intake#reject', 'url' => 'api/intake/documents/{uuid}/reject', 'verb' => 'POST'],

                                                                                                
        ['name' => 'intake#detached', 'url' => 'api/intake/detached', 'verb' => 'GET'],
        ['name' => 'intake#detach', 'url' => 'api/intake/documents/detach', 'verb' => 'POST'],
        ['name' => 'intake#decideParty', 'url' => 'api/intake/party-decisions', 'verb' => 'POST'],
        ['name' => 'intake#declareRouting', 'url' => 'api/intake/routing-rules', 'verb' => 'POST'],

                                                                           
                                              
        ['name' => 'caseDocuments#files', 'url' => 'api/case-documents/files', 'verb' => 'GET'],
        ['name' => 'caseDocuments#linkDomain', 'url' => 'api/case-documents/{uuid}/domains', 'verb' => 'POST'],
        ['name' => 'caseDocuments#unlinkDomain', 'url' => 'api/case-documents/{uuid}/domains', 'verb' => 'DELETE'],
        ['name' => 'caseDocuments#mine', 'url' => 'api/case-documents/mine', 'verb' => 'GET'],
        ['name' => 'caseDocuments#uploadPolicy', 'url' => 'api/case-documents/upload-policy', 'verb' => 'GET'],

                                                                                
                                                                          
                                                                            
                                                                       
        ['name' => 'postRegister#openPost', 'url' => 'api/post-register/open', 'verb' => 'GET'],
        ['name' => 'postRegister#answers', 'url' => 'api/post-register/answers', 'verb' => 'GET'],
        ['name' => 'postRegister#series', 'url' => 'api/post-register/series', 'verb' => 'GET'],

                                                           
                                       
        ['name' => 'documentProduction#layoutVersions', 'url' => 'api/page-layouts', 'verb' => 'GET'],
        ['name' => 'documentProduction#editLayout', 'url' => 'api/page-layouts', 'verb' => 'POST'],
        ['name' => 'documentProduction#archivePreflight', 'url' => 'api/case-archive/preflight', 'verb' => 'GET'],
        ['name' => 'documentProduction#archiveManifest', 'url' => 'api/case-archive/manifest', 'verb' => 'POST'],
        ['name' => 'documentProduction#runPeriodic', 'url' => 'api/periodic-documents/run', 'verb' => 'POST'],
        ['name' => 'documentProduction#dueForReview', 'url' => 'api/documents/due-for-review', 'verb' => 'GET'],
        ['name' => 'documentProduction#markReviewed', 'url' => 'api/documents/{uuid}/reviewed', 'verb' => 'POST'],

                                                 
        ['name' => 'merge#create', 'url' => 'api/merge', 'verb' => 'POST'],
        ['name' => 'merge#show', 'url' => 'api/merge/{id}', 'verb' => 'GET'],

                                                                            
                                               
        ['name' => 'scanIntake#separators', 'url' => 'api/scan/separators', 'verb' => 'POST'],
        ['name' => 'scanIntake#listProfiles', 'url' => 'api/scan/profiles', 'verb' => 'GET'],
        ['name' => 'scanIntake#declareProfiles', 'url' => 'api/scan/profiles', 'verb' => 'POST'],
        ['name' => 'scanIntake#split', 'url' => 'api/scan/batches/{fileId}/split', 'verb' => 'POST'],

                                                                              
                                                            
                                                    
        ['name' => 'redactionOutput#markChecked', 'url' => 'api/redaction/documents/{fileId}/checked', 'verb' => 'POST', 'requirements' => ['fileId' => '\\d+']],
        ['name' => 'redactionOutput#composeList', 'url' => 'api/redaction/publication-list', 'verb' => 'POST'],
        ['name' => 'redactionOutput#agreement', 'url' => 'api/redaction/agreement', 'verb' => 'GET'],
        ['name' => 'redactionOutput#acceptAgreement', 'url' => 'api/redaction/agreement/accept', 'verb' => 'POST'],

                                
        ['name' => 'anonymization#files', 'url' => 'api/anonymization/files', 'verb' => 'GET'],
        ['name' => 'anonymization#upload', 'url' => 'api/anonymization/upload', 'verb' => 'POST'],
        ['name' => 'anonymization#extract', 'url' => 'api/anonymization/extract/{fileId}', 'verb' => 'POST'],
        ['name' => 'anonymization#anonymize', 'url' => 'api/anonymization/anonymize/{fileId}', 'verb' => 'POST'],
        ['name' => 'anonymization#updateRelation', 'url' => 'api/anonymization/relations/{id}', 'verb' => 'PATCH', 'requirements' => ['id' => '\\d+']],

                                                                                     
        ['name' => 'emlPreview#preview', 'url' => 'api/anonymization/eml-preview/{fileId}', 'verb' => 'GET'],

                                        
        ['name' => 'dossier#generateGrondslagenSummary', 'url' => 'api/anonymization/dossier/{dossierId}/grondslagen-pdf', 'verb' => 'POST'],

                                                                       
        ['name' => 'dossierManagement#index', 'url' => 'api/dossiers', 'verb' => 'GET'],
        ['name' => 'dossierManagement#create', 'url' => 'api/dossiers', 'verb' => 'POST'],
        ['name' => 'dossierManagement#show', 'url' => 'api/dossiers/{dossierId}', 'verb' => 'GET'],
        ['name' => 'dossierManagement#rename', 'url' => 'api/dossiers/{dossierId}/name', 'verb' => 'PUT'],
        ['name' => 'dossierManagement#transition', 'url' => 'api/dossiers/{dossierId}/status', 'verb' => 'PUT'],
        ['name' => 'dossierManagement#linkDocument', 'url' => 'api/dossiers/{dossierId}/documents', 'verb' => 'POST'],
        ['name' => 'dossierManagement#removeDocument', 'url' => 'api/dossiers/{dossierId}/documents/{fileId}', 'verb' => 'DELETE'],
        ['name' => 'dossierManagement#removalMode', 'url' => 'api/dossiers/{dossierId}/documents/{fileId}/removal-mode', 'verb' => 'GET'],

                                      
        ['name' => 'batchAnonymization#folderBatch', 'url' => 'api/anonymization/batch/folder', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchUpload', 'url' => 'api/anonymization/batch/upload', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchExtract', 'url' => 'api/anonymization/batch/{batchId}/extract', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchStatus', 'url' => 'api/anonymization/batch/{batchId}/status', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#batchEntities', 'url' => 'api/anonymization/batch/{batchId}/entities', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#batchAnonymize', 'url' => 'api/anonymization/batch/{batchId}/anonymize', 'verb' => 'POST'],
        ['name' => 'batchAnonymization#batchReport', 'url' => 'api/anonymization/batch/{batchId}/report', 'verb' => 'GET'],

                                     
        ['name' => 'batchAnonymization#getProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'GET'],
        ['name' => 'batchAnonymization#updateProfiles', 'url' => 'api/anonymization/profiles', 'verb' => 'PUT'],

                                       
        ['name' => 'policy#indexProhibitions', 'url' => 'api/policy/prohibitions', 'verb' => 'GET'],
        ['name' => 'policy#createProhibition', 'url' => 'api/policy/prohibitions', 'verb' => 'POST'],
        ['name' => 'policy#showProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'GET'],
        ['name' => 'policy#updateProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'PUT'],
        ['name' => 'policy#deleteProhibition', 'url' => 'api/policy/prohibitions/{id}', 'verb' => 'DELETE'],

                                                                        
        ['name' => 'standingConsent#index', 'url' => 'api/policy/standing-consents', 'verb' => 'GET'],
        ['name' => 'standingConsent#create', 'url' => 'api/policy/standing-consents', 'verb' => 'POST'],
        ['name' => 'standingConsent#show', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'GET'],
        ['name' => 'standingConsent#update', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'PUT'],
        ['name' => 'standingConsent#destroy', 'url' => 'api/policy/standing-consents/{id}', 'verb' => 'DELETE'],

                                                                             
                                           
        ['name' => 'customDictionary#index', 'url' => 'api/custom-dictionaries', 'verb' => 'GET'],
        ['name' => 'customDictionary#create', 'url' => 'api/custom-dictionaries', 'verb' => 'POST'],
        ['name' => 'customDictionary#show', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'GET'],
        ['name' => 'customDictionary#update', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'PUT'],
        ['name' => 'customDictionary#destroy', 'url' => 'api/custom-dictionaries/{id}', 'verb' => 'DELETE'],
        ['name' => 'customDictionary#indexTerms', 'url' => 'api/custom-dictionaries/{id}/terms', 'verb' => 'GET'],
        ['name' => 'customDictionary#createTerm', 'url' => 'api/custom-dictionaries/{id}/terms', 'verb' => 'POST'],
        ['name' => 'customDictionary#deleteTerm', 'url' => 'api/custom-dictionaries/{id}/terms/{termId}', 'verb' => 'DELETE'],
        ['name' => 'customDictionary#import', 'url' => 'api/custom-dictionaries/{id}/import', 'verb' => 'POST'],

                                 
        ['name' => 'pdf#render', 'url' => 'api/pdf/render', 'verb' => 'POST'],
        ['name' => 'pdf#renderPdfA', 'url' => 'api/pdf/render-pdfa', 'verb' => 'POST'],

                                                                       
                                                                                    
        ['name' => 'pdfa3Conversion#convert', 'url' => 'api/pdfa3/convert', 'verb' => 'POST'],

                                                   
        ['name' => 'print#preview', 'url' => 'api/print/preview', 'verb' => 'POST'],
        ['name' => 'print#downloadPdfA', 'url' => 'api/print/pdf-a', 'verb' => 'POST'],

                                                                
        ['name' => 'printJob#create', 'url' => 'api/print/jobs', 'verb' => 'POST'],
        ['name' => 'printJob#batch', 'url' => 'api/print/batch', 'verb' => 'POST'],
        ['name' => 'printJob#show', 'url' => 'api/print/jobs/{id}', 'verb' => 'GET'],
        ['name' => 'printJob#download', 'url' => 'api/print/jobs/{id}/download', 'verb' => 'GET'],
        ['name' => 'printJob#updateStatus', 'url' => 'api/print/jobs/{id}/status', 'verb' => 'PUT'],

                                                                   
        ['name' => 'document#generate', 'url' => 'api/documents/generate', 'verb' => 'POST'],
        ['name' => 'document#preview', 'url' => 'api/documents/generate/preview', 'verb' => 'POST'],
        ['name' => 'document#generateBulk', 'url' => 'api/documents/generate/bulk', 'verb' => 'POST'],
        ['name' => 'document#jobStatus', 'url' => 'api/documents/jobs/{jobId}', 'verb' => 'GET'],

                                 
        ['name' => 'correspondence#generate', 'url' => 'api/correspondence/generate', 'verb' => 'POST'],
        ['name' => 'correspondence#generateBatch', 'url' => 'api/correspondence/generate/batch', 'verb' => 'POST'],
        ['name' => 'correspondence#jobStatus', 'url' => 'api/correspondence/jobs/{jobId}', 'verb' => 'GET'],

                           
        ['name' => 'templates#index', 'url' => 'api/templates', 'verb' => 'GET'],
        ['name' => 'templates#create', 'url' => 'api/templates', 'verb' => 'POST'],
        ['name' => 'templatePreview#preview', 'url' => 'api/templates/preview', 'verb' => 'POST'],
        ['name' => 'templates#show', 'url' => 'api/templates/{id}', 'verb' => 'GET'],
        ['name' => 'templates#update', 'url' => 'api/templates/{id}', 'verb' => 'PUT'],
        ['name' => 'templates#destroy', 'url' => 'api/templates/{id}', 'verb' => 'DELETE'],
        ['name' => 'templateVersions#versions', 'url' => 'api/templates/{id}/versions', 'verb' => 'GET'],
        ['name' => 'templateVersions#diffVersions', 'url' => 'api/templates/{id}/versions/diff', 'verb' => 'GET'],
        ['name' => 'templateVersions#restoreVersion', 'url' => 'api/templates/{id}/versions/{versionId}/restore', 'verb' => 'POST'],
        ['name' => 'templatePreview#previewTemplate', 'url' => 'api/templates/{id}/preview', 'verb' => 'POST'],
        ['name' => 'templates#duplicate', 'url' => 'api/templates/{id}/duplicate', 'verb' => 'POST'],
        ['name' => 'templates#lock', 'url' => 'api/templates/{id}/lock', 'verb' => 'POST'],
        ['name' => 'templates#unlock', 'url' => 'api/templates/{id}/lock', 'verb' => 'DELETE'],

                          
        ['name' => 'signing#createRequest', 'url' => 'api/signing/requests', 'verb' => 'POST'],
        ['name' => 'signing#listRequests', 'url' => 'api/signing/requests', 'verb' => 'GET'],
        ['name' => 'signing#showRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'GET'],
        ['name' => 'signing#cancelRequest', 'url' => 'api/signing/requests/{id}', 'verb' => 'DELETE'],
        ['name' => 'signing#sign', 'url' => 'api/signing/requests/{id}/sign', 'verb' => 'POST'],
        ['name' => 'signing#decline', 'url' => 'api/signing/requests/{id}/decline', 'verb' => 'POST'],
        ['name' => 'signing#bulkSign', 'url' => 'api/signing/bulk', 'verb' => 'POST'],
        ['name' => 'signing#verify', 'url' => 'api/signing/verify/{fileId}', 'verb' => 'GET'],
        ['name' => 'signing#getAudit', 'url' => 'api/signing/requests/{id}/audit', 'verb' => 'GET'],

                                                                          
                                                                              
                                                                            
                                                                           
        ['name' => 'signingFolder#folder', 'url' => 'api/signing/folder', 'verb' => 'GET'],
        ['name' => 'signingFolder#signFolder', 'url' => 'api/signing/folder/sign', 'verb' => 'POST'],
        ['name' => 'signingFolder#mandates', 'url' => 'api/signing/mandates', 'verb' => 'GET'],
        ['name' => 'signingFolder#declareMandate', 'url' => 'api/signing/mandates', 'verb' => 'POST'],
        ['name' => 'signingFolder#withdrawMandate', 'url' => 'api/signing/mandates/{typeApp}/{typeSchema}', 'verb' => 'DELETE'],

                                                                  
                                                                            
                                                                       
                                                                           
                                                                       
          
                                                                            
                                                                               
                                                                                   
                                                                               
                                                                             
        ['name' => 'portalSigningReceiver#signDocument', 'url' => 'api/portal/signing/sign', 'verb' => 'POST'],
        ['name' => 'portalSigningReceiver#declineDocument', 'url' => 'api/portal/signing/decline', 'verb' => 'POST'],
        ['name' => 'portalSigningReceiver#viewDocument', 'url' => 'api/portal/signing/viewDocument', 'verb' => 'GET'],

                                                        
        ['name' => 'extraction#financial', 'url' => 'api/extraction/financial', 'verb' => 'POST'],
        ['name' => 'extraction#corrections', 'url' => 'api/extraction/{id}/corrections', 'verb' => 'POST'],

                                                                                        
        ['name' => 'glAccountSuggestion#suggestAccount', 'url' => 'api/extraction/{id}/suggest-account', 'verb' => 'POST'],

                                                                      
        ['name' => 'anonymiserWarning#dismiss', 'url' => 'api/admin/anonymiser-warning/dismiss', 'verb' => 'POST'],
        ['name' => 'anonymiserWarning#reset', 'url' => 'api/admin/anonymiser-warning/reset', 'verb' => 'POST'],

                                                                                   
                                                              
                                                                           
                                                                           
                                                                              
                                                                                
                                                                         
                                                            
        ['name' => 'preferences#getPreference', 'url' => '/api/preferences/{key}', 'verb' => 'GET'],
        ['name' => 'preferences#setPreference', 'url' => '/api/preferences/{key}', 'verb' => 'PUT'],
];

                                                                           
                                                                              
if (class_exists('OCA\OpenRegister\AppHost\Routes') === true) {
    return \OCA\OpenRegister\AppHost\Routes::standard($extra);
}

                                                                          
                                                                             
                                                                          
$canonicalRoutes = [
    ['name' => 'dashboard#page', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'settings#index', 'url' => '/api/settings', 'verb' => 'GET'],
    ['name' => 'settings#create', 'url' => '/api/settings', 'verb' => 'POST'],
    ['name' => 'settings#update', 'url' => '/api/settings', 'verb' => 'PUT'],
    ['name' => 'settings#load', 'url' => '/api/settings/load', 'verb' => 'POST'],
    ['name' => 'preferences#getPreference', 'url' => '/api/preferences/{key}', 'verb' => 'GET'],
    ['name' => 'preferences#setPreference', 'url' => '/api/preferences/{key}', 'verb' => 'PUT'],
    ['name' => 'metrics#index', 'url' => '/api/metrics', 'verb' => 'GET'],
    ['name' => 'health#index', 'url' => '/api/health', 'verb' => 'GET'],
];

$catchAllRoute = [
    'name'         => 'dashboard#catchAll',
    'url'          => '/{path}',
    'verb'         => 'GET',
    'requirements' => ['path' => '.+'],
    'defaults'     => ['path' => ''],
];

$extraNames = [];
foreach ($extra as $extraRoute) {
    if (isset($extraRoute['name']) === true) {
        $extraNames[(string) $extraRoute['name']] = true;
    }
}

$mergedRoutes = [];
foreach ($canonicalRoutes as $canonicalRoute) {
    if (isset($extraNames[$canonicalRoute['name']]) === true) {
        continue;
    }

    $mergedRoutes[] = $canonicalRoute;
}

$mergedRoutes   = array_merge($mergedRoutes, $extra);
$mergedRoutes[] = $catchAllRoute;

return ['routes' => $mergedRoutes];

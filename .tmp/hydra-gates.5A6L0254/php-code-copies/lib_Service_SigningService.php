<?php

   
                  
  
                                              
  
                     
                                
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Exception\RegisterNotConfiguredException;
use OCA\Filinq\Service\Signing\GuardianConsentGuard;
use RuntimeException;

   
                                                 
  
                    
                               
                                                 
                                                                                   
                                   
  
                                                                             
                                                                            
                                                                            
                                                                              
                                                                              
                                                                            
  
                                                                                 
                                                                             
                                                                                
                                                                              
                                                                     
  
                                                
                                                                                   
   
class SigningService {

	   
                                                 
   
                                     
    
	private const STATUS_TRANSITIONS = [
		'DRAFT' => ['PENDING', 'CANCELLED'],
		'PENDING' => ['IN_PROGRESS', 'CANCELLED', 'EXPIRED'],
		'IN_PROGRESS' => ['COMPLETED', 'DECLINED', 'CANCELLED', 'EXPIRED'],
		'COMPLETED' => [],
		'DECLINED' => [],
		'EXPIRED' => [],
		'CANCELLED' => [],
	];

	   
               
   
                                                            
                                                          
                                                                                                    
                                                                                              
                                                                                           
                                                                                      
                                                                                           
                                                                                   
                                                                                    
                                                                                    
                                                                                        
                                                                                          
                                                                                             
                                                                                          
                                                                                         
                                                                                           
                                                                                           
                                                                                       
                                                                            
   
                
    
	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly SigningAuditService $auditService,
		private readonly SignedArtifactProducer $artifactProducer,
		private readonly SigningRequestValidator $validator,
		private readonly SigningActorResolver $actorResolver,
		private readonly SigningConclusionEmitter $emitter,
		private readonly GuardianConsentGuard $consentGuard,
		private readonly ?SigningMandateService $mandateService = null,
	) {

	}                   

	   
                                                                           
                                                             
                                                                         
   
                     
    
	private const PROVENANCE_FIELDS = [
		'sourceApp',
		'subjectRegister',
		'subjectSchema',
		'subjectId',
		'subjectLabel',
		'externalReference',
		'correlationId',
	];

	   
                                
   
                                                              
   
                                                            
   
                                              
                                                                                     
                                                                                           
   
                                                                   
                                                                                    
    
	public function createRequest(array $data): array {
		                                                                    
		                                                              
		[$initiatorUserId, $initiatorDisplayName] = $this->actorResolver->resolveActingIdentity();

		$objectService = $this->settingsService->getObjectService();

		                                                                       
		                                                         
		                                                                      
		                                                                        
		                                                                       
		                                                       
		$toggles = $this->settingsService->getFeatureToggles();
		$expiryDays = (int)$toggles['signing_request_expiry_days'];
		$deadline = (new DateTimeImmutable())->modify('+' . $expiryDays . ' days');
		$defaultLevel = (string)$toggles['signing_default_level'];
		$defaultProv = (string)$toggles['signing_provider'];

		$request = [
			'documentFileId' => $data['documentFileId'] ?? '',
			'documentName' => $data['documentName'] ?? '',
			'initiatorUserId' => $initiatorUserId,
			'signatureLevel' => $data['signatureLevel'] ?? $defaultLevel,
			'signingMode' => $data['signingMode'] ?? 'sequential',
			'status' => 'PENDING',
			'provider' => $data['provider'] ?? $defaultProv,
			'deadline' => $data['deadline'] ?? $deadline->format(DateTimeInterface::ATOM),
			'signerIds' => [],
		];

		$this->validator->validateRequestData(data: $request);

		                                                                 
		                                                                    
		                                                                      
		                                                                      
		                                              
		$this->validator->validateProviderLevelPair(
			provider: (string)$request['provider'],
			level: (string)$request['signatureLevel']
		);

		                                                                    
		                                                                     
		                                                                   
		                                                                       
		                                          
		$request['guardianConsentAge'] = $this->consentGuard->appliedAge(data: $data);
		$signers = (array)($data['signers'] ?? []);
		$prepared = $this->consentGuard->prepareSigners(
			signers: $signers,
			age: $request['guardianConsentAge'],
			now: new DateTimeImmutable()
		);

		                                                                       
		                                                                        
		                                                                     
		                                                             
		                                                                        
		                                                   
		foreach (self::PROVENANCE_FIELDS as $field) {
			if (empty($data[$field]) === false) {
				$request[$field] = $data[$field];
			}
		}

		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$savedRequest = $objectService->saveObject(object: $request, register: $register, schema: $schema);
		$createdRequest = $this->toArray(object: $savedRequest);

		$requestId = $createdRequest['id'] ?? $createdRequest['uuid'] ?? '';
		$createdRequest['signerIds'] = $this->persistSigners(
			requestId: (string)$requestId,
			signers: $signers,
			prepared: $prepared
		);
		$objectService->saveObject(object: $createdRequest, register: $register, schema: $schema);

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'CREATED',
			actorUserId: $initiatorUserId,
			actorDisplayName: $initiatorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $request['signatureLevel'],
			provider: $request['provider']
		);

		return $createdRequest;
	}                     

	   
                                                                
   
                                                                           
                                                                            
                                                                              
   
                                                                 
                                                                                          
                                                                                                                              
                                                                                                       
   
                                                               
   
                                                                                    
    
	private function persistSigners(string $requestId, array $signers, array $prepared): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$links = $prepared['links'];
		$ids = [];
		$guardiansLast = array_diff_key($signers, $links) + array_intersect_key($signers, $links);

		foreach ($guardiansLast as $index => $signerData) {
			$signerData = (array)$signerData;
			$signerRecord = array_merge(
				[
					'signingRequestId' => $requestId,
					'userId' => $signerData['userId'] ?? '',
					'displayName' => $signerData['displayName'] ?? '',
					'email' => $signerData['email'] ?? '',
					'order' => $signerData['order'] ?? $index,
					'status' => 'PENDING',
				],
				($prepared['fields'][$index] ?? [])
			);

			if (isset($links[$index]) === true) {
				$signerRecord['guardianForSignerId'] = $ids[$links[$index]];
			}

			$created = $this->toArray(
				object: $objectService->saveObject(object: $signerRecord, register: $signerRegister, schema: $signerSchema)
			);
			$ids[$index] = (string)($created['id'] ?? $created['uuid'] ?? '');
		}             

		                                                                                    
		return array_values(array_replace(array_fill_keys(array_keys($signers), ''), $ids));

	}                      

	   
                               
   
                                                                         
                                                                         
                                                                          
                                                       
   
                                                                            
                                                                           
                                                                       
                                                                            
                                   
   
                                                   
                                                                        
   
                                                                         
                                                                      
                                                                         
                                                                        
                                                                          
                                                                              
                                                                              
                                               
   
                                                                       
   
                                                                   
   
                                                                         
                                                                         
                                                                          
                                                                      
                                                                  
    
	public function getRequest(string $requestId, string $callerUserId = ''): ?array {
		$objectService = $this->settingsService->getObjectService();
		                                                                       
		                                                                        
		                                                                
		                                                                 
		                           
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		$object = $objectService->find(id: $requestId, register: $register, schema: $schema);
		if ($object === null) {
			                                                                 
			                                                             
			                                                                  
			                                                
			throw new RuntimeException('Signing request not found');
		}

		$request = $this->toArray(object: $object);

		                                                                 
		                                                                     
		                                                 
		if ($callerUserId !== '') {
			$isInitiator = ($request['initiatorUserId'] ?? '') === $callerUserId;
			$isSignerInList = in_array($callerUserId, (array)($request['signerIds'] ?? []), true);

			if ($isInitiator === false && $isSignerInList === false) {
				return null;
			}
		}

		return $request;
	}                  

	   
                                                    
   
                                                                           
                                                                          
                                                                          
                                                                        
                                       
   
                                                                         
                                                                           
                                                                           
   
                                                                           
   
                                                                     
   
                                                                   
    
	public function listRequests(string $callerUserId = ''): array {
		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		                                                                     
		                                                                        
		                                                                   
		                                                                       
		                                                                     
		$query = $objectService->buildSearchQuery(
			requestParams: ['_limit' => 1000],
			register: $register,
			schema: $schema
		);

		$paginated = $objectService->searchObjectsPaginated(query: $query);
		$results = ($paginated['results'] ?? []);

		$requests = [];
		foreach ($results as $result) {
			$item = $this->toArray(object: $result);

			                                                            
			                                                             
			if ($callerUserId !== '') {
				$isInitiator = ($item['initiatorUserId'] ?? '') === $callerUserId;
				$isSignerInList = in_array($callerUserId, (array)($item['signerIds'] ?? []), true);

				if ($isInitiator === false && $isSignerInList === false) {
					continue;
				}
			}

			$requests[] = $item;
		}

		return $requests;
	}                    

	   
                                            
   
                                                   
                                                
                                                                                  
                                                                                          
                                                                                           
                                                                                           
                                                                                          
                                                                                        
                                                                                        
                                                                                          
                                                                                        
                                                                                          
                                                                                      
                                                                                         
                                                                                          
                                                                                         
                                                                                           
   
                                                          
   
                                             
   
                                                                   
                                                       
                                                       
                                                                                    
    
	public function sign(string $requestId, string $signerId, ?array $verifiedActor = null, ?array $signatureData = null): array {
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);

		$objectService = $this->settingsService->getObjectService();
		$request = $this->getRequest(requestId: $requestId);
		if ($request === null) {
			                                                                    
			                                                             
			                                                               
			                                                         
			                      
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$status = $request['status'] ?? '';

		if (in_array($status, ['PENDING', 'IN_PROGRESS'], true) === false) {
			throw new RuntimeException('Signing request is not in a signable state: ' . $status);
		}

		                                                                             
		                                                                    
		                                                                      
		                                                                    
		                                                                    
		                                            
		if ($this->mandateService !== null && $verifiedActor === null) {
			$this->mandateService->assertMaySign(request: $request, userId: $actorUserId);
		}

		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'sign'
		);

		if (($signer['status'] ?? '') !== 'PENDING') {
			throw new RuntimeException('Signer has already responded to this request');
		}

		                                                                       
		                                                                       
		                                                                         
		                                                                    
		                                                                      
		$now = new DateTimeImmutable();
		$consent = $this->consentGuard->guardSigningAct(
			requestId: $requestId,
			request: $request,
			signer: $signer + ['id' => $signerId],
			verifiedActor: $verifiedActor,
			now: $now
		);

		$signer = array_merge($signer, $consent['record']);
		$signer['status'] = 'SIGNED';
		$signer['signedAt'] = $now->format(DateTimeInterface::ATOM);
		$signer['ipAddress'] = $this->actorResolver->getClientIp();
		if ($signatureData !== null) {
			                                                               
			                                                       
			                                                   
			$signer['signatureData'] = $signatureData;
		}

		$objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'SIGNED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $request['signatureLevel'] ?? 'SES',
			provider: $request['provider'] ?? 'native',
			metadata: array_merge(
				$this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor),
				$consent['audit']
			)
		);

		$this->updateRequestStatus(requestId: $requestId, request: $request, verifiedActor: $verifiedActor);

		return $signer;
	}            

	   
                             
   
                                                   
                                                
                                            
                                                                                  
                                                                                       
                                                                                        
   
                                                          
   
                                                                         
                                                                      
   
                                                                   
                                                 
                                                       
                                                       
    
	public function decline(string $requestId, string $signerId, string $reason, ?array $verifiedActor = null): array {
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity(verifiedActor: $verifiedActor);

		$objectService = $this->settingsService->getObjectService();

		                                                                    
		                                                            
		                                                                   
		                                                               
		                                               
		                                                                        
		                                                          
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		$requestObject = $objectService->find(id: $requestId, register: $register, schema: $schema);
		if ($requestObject === null) {
			throw new RuntimeException('Signing request not found: ' . $requestId);
		}

		$request = $this->toArray(object: $requestObject);

		if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'DECLINED') === false) {
			throw new RuntimeException('Cannot decline request in status: ' . ($request['status'] ?? 'unknown'));
		}

		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();

		$signer = $this->actorResolver->loadAuthorisedSigner(
			requestId: $requestId,
			signerId: $signerId,
			verifiedActor: $verifiedActor,
			actorUserId: $actorUserId,
			action: 'decline'
		);

		$signer['status'] = 'DECLINED';
		$signer['declineReason'] = $reason;
		$objectService->saveObject(object: $signer, register: $signerRegister, schema: $signerSchema);

		$signatureLevel = $request['signatureLevel'] ?? 'SES';
		$provider = $request['provider'] ?? 'native';

		$request['status'] = 'DECLINED';
		$objectService->saveObject(object: $request, register: $register, schema: $schema);

		                                                                         
		                                                                        
		$this->emitter->emitIfDelegated(request: $request, status: 'declined');

		$metadata = $this->actorResolver->actorAuditMetadata(verifiedActor: $verifiedActor);
		$metadata['reason'] = $reason;

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'DECLINED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp(),
			signatureLevel: $signatureLevel,
			provider: $provider,
			metadata: $metadata
		);

		return $signer;
	}               

	   
                            
   
                                                   
   
                                                                    
                                                                       
                                                                   
                                                                        
                                                                        
                                                                        
                                                                        
                                                               
   
                                                                   
    
	public function cancelRequest(string $requestId): ?array {
		                                                                    
		                                                              
		[$actorUserId, $actorDisplayName] = $this->actorResolver->resolveActingIdentity();

		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();

		                                                                
		                                                                   
		                                                                
		                                        
		$request = $this->getRequest(requestId: $requestId);
		if ($request === null) {
			return null;
		}

		if ($this->isValidTransition(currentStatus: $request['status'] ?? '', newStatus: 'CANCELLED') === false) {
			throw new RuntimeException('Cannot cancel request in status: ' . ($request['status'] ?? 'unknown'));
		}

		$request['status'] = 'CANCELLED';
		$objectService->saveObject(object: $request, register: $register, schema: $schema);

		                                                                        
		                                                                           
		$this->emitter->emitIfDelegated(request: $request, status: 'cancelled');

		$this->auditService->logEvent(
			signingRequestId: $requestId,
			action: 'CANCELLED',
			actorUserId: $actorUserId,
			actorDisplayName: $actorDisplayName,
			ipAddress: $this->actorResolver->getClientIp()
		);

		return $request;
	}                     

	   
                                       
   
                                                                 
   
                                                                           
   
                                                                   
    
	public function bulkSign(array $requestIds): array {
		$results = [];
		                                                                   
		                                                                    
		                                     
		$userId = $this->actorResolver->currentUserId();

		foreach ($requestIds as $requestId) {
			try {
				$request = $this->getRequest(requestId: $requestId);
				if ($request === null) {
					                                                    
					                                           
					$results[$requestId] = [
						'success' => false,
						'error' => 'Request not accessible',
					];
					continue;
				}

				$signerIds = $request['signerIds'] ?? [];
				$targetSignerId = $this->actorResolver->findSignerForUser(signerIds: $signerIds, userId: $userId);

				$results[$requestId] = [
					'success' => false,
					'error' => 'No pending signer record found for current user',
				];
				if ($targetSignerId !== null) {
					$results[$requestId] = [
						'success' => true,
						'signer' => $this->sign(requestId: $requestId, signerId: $targetSignerId),
					];
				}
			} catch (Exception $e) {
				$results[$requestId] = [
					'success' => false,
					'error' => $e->getMessage(),
				];
			}         
		}             

		return $results;
	}                

	   
                                
   
                                                   
                                                    
   
                                            
   
                                                                   
    
	public function isValidTransition(string $currentStatus, string $newStatus): bool {
		$allowed = self::STATUS_TRANSITIONS[$currentStatus] ?? [];
		return in_array($newStatus, $allowed, true) === true;
	}                         

	   
                                                              
   
                                                   
                                                                 
                                                                                          
                                                                                         
                                                                                      
                                                                                        
                                                                                           
   
                
    
	private function updateRequestStatus(string $requestId, array $request, ?array $verifiedActor = null): void {
		$objectService = $this->settingsService->getObjectService();
		['register' => $register, 'schema' => $schema] = $this->requireSigningRequestBinding();
		['register' => $signerRegister, 'schema' => $signerSchema] = $this->requireSignerRecordBinding();
		$signerIds = $request['signerIds'] ?? [];
		$allSigned = true;
		$signers = [];

		foreach ($signerIds as $signerId) {
			$signerObj = $objectService->find(id: $signerId, register: $signerRegister, schema: $signerSchema);
			$signer = $this->toArray(object: $signerObj);
			$signers[(string)$signerId] = $signer;

			if (($signer['status'] ?? '') !== 'SIGNED') {
				$allSigned = false;
				break;
			}
		}             

		$freshObj = $objectService->find(id: $requestId, register: $register, schema: $schema);
		$freshRequest = $this->toArray(object: $freshObj);

		if ($allSigned === false) {
			$freshRequest['status'] = 'IN_PROGRESS';
			$objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);
			return;
		}

		                                                                     
		                                                                 
		                                                                   
		                                                                       
		                                                                     
		                                                    
		                                                              
		  
		                                                                     
		                                                                      
		                                                                       
		                                                                        
		                                                                      
		                                                                        
		                                                                     
		                                                               
		                                                                     
		                                                                    
		                                                                    
		                                                    
		  
		                                                                     
		                                                                   
		                                                                       
		                           
		if (($freshRequest['status'] ?? '') === 'PENDING') {
			$freshRequest['status'] = 'IN_PROGRESS';
			$freshRequest = $this->toArray(
				object: $objectService->saveObject(
					object: $freshRequest,
					register: $register,
					schema: $schema
				)
			);
		}

		                                                                 
		                                                                     
		                                                                     
		                                                                   
		                                                                     
		$freshRequest = $this->consentGuard->withConsentBasis(request: $freshRequest, signers: $signers);

		$signedDocumentRef = $this->artifactProducer->produce(request: $freshRequest, verifiedActor: $verifiedActor);

		$freshRequest['status'] = 'COMPLETED';
		$freshRequest['signedDocumentRef'] = $signedDocumentRef;
		$objectService->saveObject(object: $freshRequest, register: $register, schema: $schema);

		                                                          
		                                                                     
		                                                                     
		                                        
		$this->emitter->emitIfDelegated(
			request: $freshRequest,
			status: 'signed',
			signedDocumentRef: $signedDocumentRef
		);

	}                           

	   
                                                                 
   
                                                                         
                                                                             
                                                                         
                                                                            
   
                                                                                      
   
                                                                                    
   
                
    
	public function emitExpiredConclusion(array $request): void {
		$this->emitter->emitIfDelegated(request: $request, status: 'expired');

	}                             

	   
                                                 
   
                                                                            
                                                                              
                                                                           
   
                                                                 
   
                                                      
    
	private function toArray(mixed $object): array {
		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return $object->jsonSerialize();
		}

		return (array)$object;
	}               

	   
                                                                         
   
                                                                           
                                                                        
                                                                          
                                                                     
                                                                       
                                                                   
                                      
   
                                                                         
   
                                                                     
   
                                                 
    
	private function requireSigningRequestBinding(): array {
		$binding = $this->settingsService->resolveSigningRequestBinding();
		if ($binding === null) {
			throw new RegisterNotConfiguredException(
				message: 'Signing request register/schema not configured'
			);
		}

		return $binding;
	}                                    

	   
                                                                       
   
                                                                            
                                                                         
            
   
                                                                         
   
                                                                     
   
                                                 
    
	private function requireSignerRecordBinding(): array {
		$binding = $this->settingsService->resolveSignerRecordBinding();
		if ($binding === null) {
			throw new RegisterNotConfiguredException(
				message: 'Signer record register/schema not configured'
			);
		}

		return $binding;
	}                                  
}           

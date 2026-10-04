<?php

   
                                     
  
                                                                      
                                                                                 
                                                                             
                                                                         
                                                                            
                                                                           
                                                                           
                                                                         
                                                                             
                                                           
  
                                                                           
                                                                    
                                                                              
                                                                             
                            
  
                        
                                   
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
  
                                                      
                                                      
   

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Portal\PortalAssertionVerifier;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\PortalSigningDocumentResolver;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SigningService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use Psr\Log\LoggerInterface;
use Throwable;

   
                                                                          
  
                                                      
   
class PortalSigningReceiverController extends Controller {
	   
                                                                        
   
                                                               
                                                                         
                                                         
   
               
    
	private const THROTTLE_ACTION = 'filinq_portal_signing_assertion';

	   
                                                               
                                                                        
                                                                         
    
	private const MIN_TRUST = 'substantial';

	   
                                                               
   
                     
    
	private const TRUST_ORDER = ['low', 'substantial', 'high'];

	   
                                           
    
	private const AUDIENCE_SIGNER = 'signer';

	   
                
   
                                    
                                            
                                                                                     
                                                                       
                                                                                           
                                                                                                    
                                          
                                                                                                         
                                                                                                             
   
                
    
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PortalAssertionVerifier $verifier,
		private readonly SigningService $signingService,
		private readonly SettingsService $settingsService,
		private readonly OpenRegisterResolver $registerResolver,
		private readonly LoggerInterface $logger,
		private readonly PortalSigningDocumentResolver $documentResolver,
		private readonly IThrottler $throttler,
	) {
		parent::__construct(appName: $appName, request: $request);

	}                   

	   
                                             
   
                                                               
                                                                       
                                                                
                                                                      
                                                              
                                                                            
   
                        
   
                                                       
                                                       
                                                                                    
    
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 20, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function signDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[$verifiedActor, $signerRecord] = $context;

		$consent = (bool)$this->request->getParam('consent', false);
		$signature = $this->request->getParam('signature');

		$signatureData = ['consent' => $consent];
		if (is_string($signature) === true && $signature !== '') {
			$signatureData['signature'] = $signature;
		}

		try {
			$signer = $this->signingService->sign(
				requestId: (string)$signerRecord['signingRequestId'],
				signerId: (string)($signerRecord['id'] ?? $signerRecord['uuid'] ?? ''),
				verifiedActor: $verifiedActor,
				signatureData: $signatureData
			);
		} catch (Throwable $e) {
			                                                                
			                                                                    
			                                                                  
			                                                               
			if ($e->getCode() === Http::STATUS_FORBIDDEN) {
				$this->logger->info('Filinq: portal signing refused: ' . $e->getMessage());
				return new JSONResponse(['error' => 'signing_refused'], Http::STATUS_FORBIDDEN);
			}

			return $this->downstreamFailure(context: 'signDocument', exception: $e);
		}

		return new JSONResponse(
			[
				'status' => ($signer['status'] ?? 'signed'),
			]
		);

	}                    

	   
                                                
   
                                                                  
                                                                      
                                                                           
                             
   
                        
   
                                                       
                                                       
    
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 20, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function declineDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[$verifiedActor, $signerRecord] = $context;

		$reason = (string)$this->request->getParam('reason', '');

		try {
			$signer = $this->signingService->decline(
				requestId: (string)$signerRecord['signingRequestId'],
				signerId: (string)($signerRecord['id'] ?? $signerRecord['uuid'] ?? ''),
				reason: $reason,
				verifiedActor: $verifiedActor
			);
		} catch (Throwable $e) {
			return $this->downstreamFailure(context: 'declineDocument', exception: $e);
		}

		return new JSONResponse(
			[
				'status' => ($signer['status'] ?? 'declined'),
			]
		);

	}                       

	   
                                                    
   
                                                                         
                                                                     
                                                                 
                                                                          
                                                                       
                    
   
                        
   
                                                       
    
	#[PublicPage]
	#[NoCSRFRequired]
	#[AnonRateLimit(limit: 60, period: 60)]
	#[BruteForceProtection(action: self::THROTTLE_ACTION)]
	public function viewDocument(): JSONResponse {
		$context = $this->authoriseAct();
		if ($context instanceof JSONResponse) {
			return $context;
		}

		[, $signerRecord] = $context;

		try {
			$signingRequest = $this->signingService->getRequest(requestId: (string)$signerRecord['signingRequestId']);
		} catch (Throwable $e) {
			return $this->downstreamFailure(context: 'viewDocument', exception: $e);
		}

		if ($signingRequest === null) {
			                                                                
			return $this->forbidden();
		}

		$file = $this->documentResolver->resolve(signingRequest: $signingRequest);
		if ($file === null) {
			return new JSONResponse(['error' => 'document_unavailable'], Http::STATUS_NOT_FOUND);
		}

		try {
			$content = $file->getContent();
		} catch (Throwable $e) {
			return $this->downstreamFailure(context: 'viewDocument', exception: $e);
		}

		return new JSONResponse(
			[
				'documentName' => (string)($signingRequest['documentName'] ?? $file->getName()),
				'mimeType' => $file->getMimeType(),
				'contentBase64' => base64_encode($content),
			]
		);

	}                    

	   
                                                                           
                                                                     
                                                   
   
                                                                                
                                                                                                                                    
                                                                                                                                          
    
	private function authoriseAct(): array|JSONResponse {
		                                                                      
		$claims = $this->verifier->verify((string)$this->request->getHeader(PortalAssertionVerifier::HEADER));
		if ($claims === null) {
			                                                                
			                                                               
			$this->registerRejectedAssertion();
			return new JSONResponse(['error' => 'unauthorized'], Http::STATUS_UNAUTHORIZED);
		}

		                                                                       
		                                                                    
		                               
		$audience = (string)($claims['audience'] ?? '');
		if ($audience !== self::AUDIENCE_SIGNER) {
			return $this->forbidden();
		}

		$trust = (string)($claims['trust'] ?? '');
		if ($this->trustAtLeast(trust: $trust, minimum: self::MIN_TRUST) === false) {
			return $this->forbidden();
		}

		                                                                    
		                                                                
		                                                                    
		                                                                
		                                                                  
		                                                               
		$signerEmail = $claims['signerEmail'] ?? null;
		if (is_string($signerEmail) === false || $signerEmail === '') {
			return $this->forbidden();
		}

		                                                                   
		                                                                       
		                  
		$signingRequestId = $this->request->getParam('signingRequestId');
		if ($this->isValidOpaqueId(value: $signingRequestId) === false) {
			return $this->forbidden();
		}

		                                                                   
		                                                                      
		                                                                    
		                                           
		$signerRecord = $this->resolveInvitedSigner(email: $signerEmail, signingRequestId: (string)$signingRequestId);
		if ($signerRecord === null) {
			return $this->forbidden();
		}

		$verifiedActor = [
			'email' => $signerEmail,
			                                                             
			                                                               
			                                                             
			                                                                 
			                                                       
			                                                               
			                  
			'subjectRef' => (string)$claims['sub'],
			'identityRef' => (string)$claims['sub'],
			'trust' => $trust,
			'jti' => (string)($claims['jti'] ?? ''),
		];

		return [$verifiedActor, $signerRecord];
	}                    

	   
                                                                          
   
                                                                         
                                                                           
                                                                      
                                                                      
   
                                                               
                                                                                   
   
                                                                         
    
	private function resolveInvitedSigner(string $email, string $signingRequestId): ?array {
		try {
			$objectService = $this->settingsService->getObjectService();
		} catch (Throwable $e) {
			$objectService = null;
		}

		if ($objectService === null) {
			return null;
		}

		                                                                       
		                                                                      
		                                                                   
		                                                              
		                                                                  
		                                                                     
		                                                                       
		                                                           
		                                                           
		  
		                                                                       
		                                                              
		                                                                
		                                            
		try {
			['register' => $register, 'schema' => $schema] = $this->registerResolver->getSignerRecordRegisterAndSchema();
			$results = $objectService->findAll(
				[
					'filters' => [
						'register' => $register,
						'schema' => $schema,
						'email' => $email,
						'signingRequestId' => $signingRequestId,
					],
				],
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->debug('Filinq: portal signer lookup failed', ['reason' => $e->getMessage()]);
			return null;
		}

		if (is_iterable($results) === false) {
			return null;
		}

		foreach ($results as $entry) {
			$row = $this->normalise(row: $entry);
			if ($row === null) {
				continue;
			}

			$rowEmail = (string)($row['email'] ?? '');
			$rowReq = (string)($row['signingRequestId'] ?? '');
			if (strcasecmp($rowEmail, $email) === 0 && $rowReq === $signingRequestId) {
				return $row;
			}
		}

		return null;
	}                            

	   
                                                                         
   
                                         
   
                                     
    
	private function normalise(mixed $row): ?array {
		if (is_array($row) === true) {
			return $row;
		}

		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
			if (is_array($data) === true) {
				return $data;
			}
		}

		return null;
	}                 

	   
                                                                        
   
                                                                     
                                                                       
                                            
   
                                                       
   
                                                                 
    
	private function isValidOpaqueId(mixed $value): bool {
		if (is_string($value) === false || $value === '') {
			return false;
		}

		return (bool)preg_match('/^[A-Za-z0-9_-]+$/', $value);
	}                       

	   
                                                                       
   
                                                   
                                                            
   
                                                                    
    
	private function trustAtLeast(string $trust, string $minimum): bool {
		$trustIndex = array_search($trust, self::TRUST_ORDER, true);
		$minimumIndex = array_search($minimum, self::TRUST_ORDER, true);

		if ($trustIndex === false || $minimumIndex === false) {
			return false;
		}

		return $trustIndex >= $minimumIndex;
	}                    

	   
                                                                       
                                                                          
                                                                     
                       
   
                        
    
	private function forbidden(): JSONResponse {
		                                                                       
		                                                                        
		                                                                       
		                                            
		$this->registerRejectedAssertion();
		return new JSONResponse(['error' => 'forbidden'], Http::STATUS_FORBIDDEN);
	}                 

	   
                                                               
   
                                                               
                                                                          
                                                                          
                                                                            
                                               
   
                
    
	private function registerRejectedAssertion(): void {
		try {
			$this->throttler->registerAttempt(
				action: self::THROTTLE_ACTION,
				ip: $this->request->getRemoteAddress()
			);
		} catch (\Throwable $throttlerFailure) {
			                                                                    
			                                                               
			$this->logger->warning(
				'PortalSigningReceiverController: registerAttempt failed: ' . $throttlerFailure->getMessage()
			);
		}
	}                                 

	   
                                                                  
                                                         
   
                                                          
                                                   
   
                        
    
	private function downstreamFailure(string $context, Throwable $exception): JSONResponse {
		$this->logger->error(
			'Filinq: portal signing receiver ' . $context . ' failed: ' . $exception->getMessage(),
			['exception' => $exception]
		);

		return new JSONResponse(['error' => 'downstream_failure'], Http::STATUS_BAD_GATEWAY);
	}                         
}           

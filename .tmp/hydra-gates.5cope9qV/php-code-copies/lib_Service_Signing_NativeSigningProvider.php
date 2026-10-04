<?php

   
                          
  
                                                                
  
                     
                                        
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Service\SettingsService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

   
                                                   
  
                                                                             
                                                                          
                                                                        
                                                                          
                                                                          
                                
  
                    
                                       
                                                 
                                                                                   
                                   
  
                                                                  
   
class NativeSigningProvider implements SigningProviderInterface {

	   
                                
   
               
    
	public const STATUS_CANCELLED = 'cancelled';

	   
                                                 
   
               
    
	public const STATUS_COMPLETED = 'completed';

	   
               
   
                                                   
                                                                                        
                                                                           
                                                                                                
   
                
    
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly SettingsService $settingsService,
		private readonly IAppConfig $config,
		private readonly AssertionCanonicalizer $canonicalizer = new AssertionCanonicalizer(),
	) {

	}                   

	   
                           
   
                                          
   
                                                                   
    
	public function getIdentifier(): string {
		return 'native';
	}                     

	   
                                      
   
                                                    
                                                            
                                                          
                                        
                                                           
   
                                                                       
   
                                                                    
   
                                                                   
    
	public function initiateSigning(
		string $documentPath,
		string $documentName,
		array $signers,
		string $level,
		array $options = [],
	): array {
		                                                                
		                                                          
		                                                                         
		                                                            
		if ($this->supportsLevel(level: $level) === false) {
			throw new RuntimeException(
				'Native provider only supports SES signature level, got: ' . $level
			);
		}

		$externalId = 'native-' . bin2hex(random_bytes(16));

		$session = [
			'externalId' => $externalId,
			'documentPath' => $documentPath,
			'documentName' => $documentName,
			'signers' => $signers,
			'level' => $level,
			'status' => 'pending',
			'signatures' => [],
			'createdAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'completedAt' => null,
			'signedDocumentPath' => null,
			'markerEmbedded' => false,
		];

		$this->persistSession(session: $session);

		return [
			'success' => true,
			'externalId' => $externalId,
			'message' => 'Native SES signing session created',
		];

	}                       

	   
                                            
   
                                                                           
                                                                           
                                                                            
                                                           
                                                                          
                                                       
   
                                                            
   
                                                   
   
                                                 
   
                                                                   
    
	public function checkStatus(string $externalId): array {
		$session = $this->loadSessionByExternalId(externalId: $externalId);

		return [
			'status' => $session['status'] ?? 'pending',
			'signers' => $session['signers'] ?? [],
			'signatures' => $session['signatures'] ?? [],
			'completedAt' => $session['completedAt'] ?? null,
		];

	}                   

	   
                                
   
                                                                      
                                                         
                                                                          
                                                                      
                                                                         
                                                                        
                                                        
   
                                                            
   
                                           
   
                                                                           
                                                                       
   
                                                 
    
	public function downloadSignedDocument(string $externalId): string {
		$session = $this->loadSessionByExternalId(externalId: $externalId);

		if (($session['status'] ?? '') !== 'completed') {
			throw new RuntimeException('Signing session is not completed (pipeline not yet integrated — see issue #304)');
		}

		$signedPath = $session['signedDocumentPath'] ?? null;
		$markerEmbedded = ($session['markerEmbedded'] ?? false) === true;

		if (is_string($signedPath) === true && $signedPath !== '' && $markerEmbedded === true) {
			return $signedPath;
		}

		                                                             
		                                                                   
		                                                       
		throw new RuntimeException(
			'Signing session ' . $externalId . ' is completed but has no verifiable signed artifact '
			. '(missing signedDocumentPath or markerEmbedded); the unsigned original is never served as signed.'
		);

	}                              

	   
                                      
   
                                                                              
                                                                             
                                                                                  
                                                            
   
                                                             
   
                
   
                                                                                  
   
                                                                                  
    
	public function cancelSigning(string $externalId): void {
		$session = $this->loadSessionByExternalId(externalId: $externalId);

		                                                                         
		if (($session['status'] ?? '') === self::STATUS_CANCELLED) {
			return;
		}

		                                                                        
		                                                                           
		                                                                            
		if (($session['status'] ?? '') === self::STATUS_COMPLETED) {
			throw new RuntimeException(
				'This signing request is already completed and cannot be withdrawn. '
				. 'Its existing signatures are unaffected.'
			);
		}

		$session['status'] = self::STATUS_CANCELLED;
		$this->persistSession(session: $session);
	}                     

	   
                                                             
   
                                                     
   
                                  
   
                                                                   
    
	public function supportsLevel(string $level): bool {
		return $level === 'SES';
	}                     

	   
                                                                         
   
                                                                         
                                                                      
                                                      
                                                            
                                                                       
                                                                       
                                                                          
                                                                         
                                                                     
                                                                     
                                                              
   
                                                                            
                                                                          
                                                                             
                                                                       
                                                                          
                                                                   
                                                                        
                                                                       
                                                                      
                                
   
                                                                  
                                                                      
                                                                          
                                                                          
                                                      
   
                                                               
                                                                 
                                                                
                                                                  
                                                                 
   
                                             
   
                                                                    
                                                        
   
                                                 
                                                       
                                                                                    
    
	public function produceSignedArtifact(string $documentContent, array $context): string {
		$level = (string)($context['level'] ?? 'SES');
		if ($this->supportsLevel(level: $level) === false) {
			throw new RuntimeException(
				'Native provider cannot produce a signed artifact for unsupported level "' . $level . '": '
				. 'the native provider only supports SES (REQ-DDSTR-002).'
			);
		}

		$secret = $this->config->getValueString('filinq', 'signing_verification_secret', '');
		if ($secret === '') {
			throw new RuntimeException(
				'Cannot produce a native SES artifact: signing_verification_secret is unset. '
				. 'Configure the signing secret in Filinq admin settings before enabling signing.'
			);
		}

		$assertion = [
			'v' => 2,
			'signer' => (string)($context['signer'] ?? 'Unknown'),
			'signers' => ($context['signers'] ?? []),
			'timestamp' => (string)($context['timestamp'] ?? (new DateTimeImmutable())->format(DateTimeInterface::ATOM)),
			'level' => $level,
			'method' => 'native',
			'ip' => (string)($context['ip'] ?? ''),
		];

		                                                            
		                                                                     
		                                                                    
		                                                                   
		                                                                  
		                                               
		foreach (['portalSubjectRef', 'portalIdentityRef', 'portalTrust', 'portalJti'] as $portalField) {
			if (isset($context[$portalField]) === true && $context[$portalField] !== '') {
				$assertion[$portalField] = (string)$context[$portalField];
			}
		}

		                                                                      
		                                                                        
		                                                                         
		                                                                 
		if (empty($context['consentBasis']) === false && is_array($context['consentBasis']) === true) {
			$assertion['consentBasis'] = $context['consentBasis'];
		}

		                                                                          
		                                                                        
		                                                                       
		$canonical = $this->assembleSignedBytes(documentContent: $documentContent, payload: '');
		$contentHash = hash('sha256', $canonical);
		$payloadCore = $this->canonicalizer->canonicalJson(data: $assertion);
		$mac = hash_hmac('sha256', $contentHash . "\n" . $payloadCore, $secret);

		$assertion['mac'] = $mac;
		$payload = base64_encode((string)json_encode($assertion));

		return $this->assembleSignedBytes(documentContent: $documentContent, payload: $payload);
	}                             

	   
                                                                     
   
                                                                          
                                                                           
                                                                        
   
                                                               
                                                                        
   
                                       
    
	private function assembleSignedBytes(string $documentContent, string $payload): string {
		                                                                      
		                                                           
		                                                                      
		                                                                      
		                                                                    
		                                                                  
		                                                              
		                                                                      
		                                             
		return $documentContent
			. "\n1 0 obj\n<< /Type /Sig /SubFilter /DocuDesk.SES >>\n/DocuDesk-Signature(" . $payload . ")\nendobj\n";

	}                           

	   
                                                       
   
                                                                     
                                                                     
                                                                      
                                                                   
                              
   
                                                         
   
                
   
                                                 
    
	private function persistSession(array $session): void {
		$objectService = $this->settingsService->getObjectService();
		if ($objectService === null) {
			throw new RuntimeException('OpenRegister is not available; cannot persist signing session');
		}

		[$register, $schema] = $this->resolveSessionRegisterSchema();

		$uuid = null;
		                                                          
		if (isset($session['externalId']) === true) {
			$existing = $this->loadRawSessionByExternalId(externalId: (string)$session['externalId']);
			if ($existing !== null) {
				if (isset($existing['uuid']) === true) {
					$uuid = (string)$existing['uuid'];
				} elseif (isset($existing['id']) === true) {
					$uuid = (string)$existing['id'];
				}
			}
		}

		                                                                  
		                                                                  
		                                                                
		                                    
		if ($uuid !== null) {
			$session['id'] = $uuid;
		}

		$objectService->saveObject(object: $session, register: $register, schema: $schema);

	}                      

	   
                                                     
   
                                                       
   
                                                
   
                                                        
    
	private function loadSessionByExternalId(string $externalId): array {
		$session = $this->loadRawSessionByExternalId(externalId: $externalId);
		if ($session === null) {
			throw new RuntimeException('Native signing session not found: ' . $externalId);
		}

		return $session;
	}                               

	   
                                                           
   
                                                                       
                                                                        
                                                             
   
                                                       
   
                                                             
    
	private function loadRawSessionByExternalId(string $externalId): ?array {
		try {
			$objectService = $this->settingsService->getObjectService();
			if ($objectService === null) {
				return null;
			}

			[$register, $schema] = $this->resolveSessionRegisterSchema();

			$results = $objectService->findAll(
				[
					'filters' => [
						'register' => $register,
						'schema' => $schema,
						'externalId' => $externalId,
					],
				]
			);

			if (is_iterable($results) === false) {
				return null;
			}

			foreach ($results as $entry) {
				$row = $this->normaliseEntry(entry: $entry);
				if ($row === null) {
					continue;
				}

				if (($row['externalId'] ?? null) === $externalId) {
					return $row;
				}
			}

			return null;
		} catch (Throwable $e) {
			$this->logger->error(
				'Failed to load signing session ' . $externalId . ': ' . $e->getMessage(),
				['exception' => $e]
			);
			return null;
		}         

	}                                  

	   
                                                                    
   
                                                    
   
                                                                            
    
	private function normaliseEntry(mixed $entry): ?array {
		if (is_array($entry) === true) {
			return $entry;
		}

		if (is_object($entry) === true && method_exists($entry, 'jsonSerialize') === true) {
			$serialised = $entry->jsonSerialize();
			if (is_array($serialised) === true) {
				return $serialised;
			}
		}

		if (is_object($entry) === true && method_exists($entry, 'getObject') === true) {
			$inner = $entry->getObject();
			if (is_array($inner) === true) {
				return $inner;
			}
		}

		return null;
	}                      

	   
                                                                
   
                                                                       
                                                                            
                                                                            
                                                                             
                                                        
   
                                                       
    
	private function resolveSessionRegisterSchema(): array {
		$register = $this->config->getValueString('filinq', 'signingSession_register', 'filinq');
		$schema = $this->config->getValueString('filinq', 'signingSession_schema', 'signingSession');

		return [$register, $schema];
	}                                    
}           

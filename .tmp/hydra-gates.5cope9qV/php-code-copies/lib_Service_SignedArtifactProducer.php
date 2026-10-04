<?php

   
                           
  
                                                                              
                                                                              
                                                    
  
                                                                   
                                                                       
  
                                                                             
                                                                              
                                                     
                                                                            
                                                                             
                                                  
                                                                            
                                                                          
                                                       
  
                     
                                
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
  
                                                
                                                      
   

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Exception\DocumentFinalException;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use RuntimeException;

   
                                                                            
  
                    
                               
                                                 
                                                                                   
                                   
  
                                                
   
class SignedArtifactProducer {
	   
                
   
                                                                                        
                                                                                   
                                                                               
                                                                                               
                                                                         
   
                
    
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
		private readonly IUserSession $userSession,
		private readonly IRequest $request,
		private readonly IRootFolder $rootFolder,
		private readonly FinalDocumentService $finalDocuments,
	) {

	}                   

	   
                                                                   
   
                                                                              
                                                                                          
                                                                                      
                                                                                       
                                                                                            
                                                                   
   
                                                                            
   
                                                                                        
                                                                                
                                                                                
   
                                                 
                                                       
                                                                                 
    
	public function produce(array $request, ?array $verifiedActor = null): string {
		$fileId = (int)($request['documentFileId'] ?? 0);
		if ($fileId <= 0) {
			throw new RuntimeException('Cannot produce a signed artifact: the request has no document file id');
		}

		                                                                      
		                                                                       
		                                                                        
		                                                         
		$this->finalDocuments->assertWritable(
			fileId: $fileId,
			action: 'store a signed version of this document'
		);

		$file = $this->resolveDocumentFile(fileId: $fileId, request: $request);

		try {
			$originalContent = $file->getContent();
		} catch (\Throwable $e) {
			throw new RuntimeException('Cannot read the document to sign: ' . $e->getMessage());
		}

		                                                                        
		                                                                  
		                                                                      
		                                                                  
		                                                                
		                                                  
		$providerName = (string)($request['provider'] ?? 'native');
		$provider = $this->providerFactory->getProvider(identifier: $providerName);

		$context = $this->buildContext(request: $request, verifiedActor: $verifiedActor);

		$signedBytes = $provider->produceSignedArtifact(documentContent: $originalContent, context: $context);

		                                                                        
		                                                                          
		try {
			$file->putContent($signedBytes);
		} catch (\Throwable $e) {
			throw new RuntimeException('Cannot store the signed artifact as a new file version: ' . $e->getMessage());
		}

		                                                                      
		                                                                        
		                    
		return $fileId . ':signed:' . substr(hash('sha256', $signedBytes), 0, 16);
	}               

	   
                                          
   
                                                                              
                                                                                                        
   
                                                      
    
	private function buildContext(array $request, ?array $verifiedActor): array {
		$context = [
			'signer' => $this->resolveSignerLabel(verifiedActor: $verifiedActor),
			'signers' => ($request['signerIds'] ?? []),
			'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
			'ip' => $this->request->getRemoteAddress(),
			'level' => (string)($request['signatureLevel'] ?? 'SES'),
		];

		                                                                       
		                                                                        
		                                                                         
		                                                                     
		if (empty($request['consentBasis']) === false && is_array($request['consentBasis']) === true) {
			$context['consentBasis'] = $request['consentBasis'];
		}

		if ($verifiedActor === null) {
			return $context;
		}

		                                                            
		                                                               
		                                                                
		                                                          
		                                                   
		$portalFieldMap = [
			'subjectRef' => 'portalSubjectRef',
			'identityRef' => 'portalIdentityRef',
			'trust' => 'portalTrust',
			'jti' => 'portalJti',
		];

		foreach ($portalFieldMap as $actorKey => $contextKey) {
			if (empty($verifiedActor[$actorKey]) === false) {
				$context[$contextKey] = (string)$verifiedActor[$actorKey];
			}
		}

		return $context;
	}                    

	   
                                                           
   
                                                                             
                                                                         
                                                                              
   
                                             
                                                                   
   
                                        
   
                                                              
    
	private function resolveDocumentFile(int $fileId, array $request): File {
		$candidates = [];
		$initiator = (string)($request['initiatorUserId'] ?? '');
		if ($initiator !== '') {
			$candidates[] = $initiator;
		}

		$current = $this->userSession->getUser();
		if ($current !== null) {
			$candidates[] = $current->getUID();
		}

		foreach (array_unique($candidates) as $uid) {
			try {
				$nodes = $this->rootFolder->getUserFolder($uid)->getById($fileId);
			} catch (\Throwable $e) {
				continue;
			}

			foreach ($nodes as $node) {
				if ($node instanceof File) {
					return $node;
				}
			}
		}

		throw new RuntimeException('Cannot resolve the document file to sign: ' . $fileId);
	}                           

	   
                                                    
   
                                                                                          
                                                                                     
   
                                                                                     
    
	private function resolveSignerLabel(?array $verifiedActor = null): string {
		if ($verifiedActor !== null) {
			$email = (string)($verifiedActor['email'] ?? '');
			if ($email !== '') {
				return $email;
			}

			return 'External signer';
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return 'Unknown';
		}

		                                                                   
		                                                            
		return $user->getDisplayName();
	}                          
}           

<?php

   
                         
  
                                                                            
                                                                       
                                                                          
  
                     
                                        
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OCA\Filinq\Service\SettingsService;
use RuntimeException;

   
                                                         
  
                                                                          
                                                                               
                                                                              
                                                                               
                                                  
  
                                                                           
                                                                              
                                       
  
                    
                                       
                                                 
                                                                                   
                                   
  
                                                                                   
   
class GuardianConsentGuard {

	   
                                                              
   
                     
    
	private const ASSURANCES = ['low', 'substantial', 'high'];

	   
                                                                
   
                          
    
	private readonly GuardianAgePolicy $agePolicy;

	   
                                                  
   
                              
    
	private readonly GuardianEntryPreparer $preparer;

	   
                                                     
   
                            
    
	private readonly ConsentBasisBuilder $basisBuilder;

	   
                
   
                                                                                              
   
                
    
	public function __construct(
		private readonly SettingsService $settingsService,
	) {
		$this->agePolicy = new GuardianAgePolicy(settingsService: $settingsService);
		$this->preparer = new GuardianEntryPreparer(agePolicy: $this->agePolicy);
		$this->basisBuilder = new ConsentBasisBuilder(agePolicy: $this->agePolicy);

	}                   

	   
                                                 
   
                                                                           
   
                                                                                                 
   
                                                                                    
    
	public function appliedAge(array $data): int {
		return $this->agePolicy->appliedAge(data: $data);

	}                  

	   
                                                                               
   
                                                                                          
                                                    
                                                         
   
                                                                                                        
   
                                                                            
   
                                                                                    
    
	public function prepareSigners(array $signers, int $age, DateTimeImmutable $now): array {
		return $this->preparer->prepare(signers: $signers, age: $age, now: $now);

	}                      

	   
                                                      
   
                                                    
                                                             
                                                                                   
                                                                                                               
                                                        
   
                                                                            
                                                                           
                                  
   
                                                                        
   
                                                                                    
    
	public function guardSigningAct(
		string $requestId,
		array $request,
		array $signer,
		?array $verifiedActor,
		DateTimeImmutable $now,
	): array {
		if (($signer['role'] ?? '') === 'guardian') {
			return $this->guardGuardianAct(requestId: $requestId, request: $request, guardian: $signer, verifiedActor: $verifiedActor, now: $now);
		}

		$age = $this->agePolicy->appliedAge(data: $request);
		$birthDate = (string)($signer['birthDate'] ?? '');
		if ($birthDate === '' || $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === false) {
			return ['record' => [], 'audit' => []];
		}

		$guardianIds = $this->guardiansFor(request: $request, signerId: (string)($signer['id'] ?? ''));
		if ($guardianIds === []) {
			throw new RuntimeException(
				'A signer under ' . $age . ' signs only with a guardian, and this request names no guardian for them',
				403
			);
		}

		return [
			'record' => ['actingIdentity' => $this->actingIdentity(signer: $signer, verifiedActor: $verifiedActor, now: $now)],
			'audit' => [
				'guardianConsent' => [
					'required' => true,
					'guardianConsentAge' => $age,
					'guardianSignerIds' => $guardianIds,
				],
			],
		];

	}                       

	   
                                                                 
   
                                                                
                                                                                                      
   
                                                                                                            
   
                                                                                                             
   
                                                                                    
    
	public function consentBasis(array $request, array $signers): array {
		return $this->basisBuilder->build(request: $request, signers: $signers);

	}                    

	   
                                                                   
   
                                                                        
                                                                     
   
                                                                
                                                                                                      
   
                                                                                                         
   
                                                                                                             
   
                                                                                    
    
	public function withConsentBasis(array $request, array $signers): array {
		$basis = $this->consentBasis(request: $request, signers: $signers);
		if ($basis !== []) {
			$request['consentBasis'] = $basis;
		}

		return $request;

	}                        

	   
                           
   
                                                    
                                                             
                                                                       
                                                                                       
                                                        
   
                                                                            
   
                                                                         
    
	private function guardGuardianAct(
		string $requestId,
		array $request,
		array $guardian,
		?array $verifiedActor,
		DateTimeImmutable $now,
	): array {
		$age = $this->agePolicy->appliedAge(data: $request);
		$birthDate = (string)($guardian['birthDate'] ?? '');
		if ($birthDate !== '' && $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === true) {
			throw new RuntimeException('A guardian must have reached the age of ' . $age, 403);
		}

		$minorId = (string)($guardian['guardianForSignerId'] ?? '');
		$minor = $this->signerOnRequest(requestId: $requestId, request: $request, signerId: $minorId);
		if ($minor === null) {
			throw new RuntimeException('This guardian record points at no signer on this request', 403);
		}

		if ($this->samePerson(first: $guardian, second: $minor) === true) {
			throw new RuntimeException('A signer cannot act as their own guardian', 403);
		}

		return [
			'record' => ['actingIdentity' => $this->actingIdentity(signer: $guardian, verifiedActor: $verifiedActor, now: $now)],
			'audit' => [
				'guardianConsent' => [
					'role' => 'guardian',
					'guardianAct' => (string)($guardian['guardianAct'] ?? 'co-sign'),
					'guardianForSignerId' => $minorId,
				],
			],
		];

	}                        

	   
                                                                               
   
                                                             
                                                 
   
                                                                                                
    
	private function guardiansFor(array $request, string $signerId): array {
		$guardianIds = [];
		foreach ((array)($request['signerIds'] ?? []) as $candidateId) {
			$candidateId = (string)$candidateId;
			if ($candidateId === '' || $candidateId === $signerId) {
				continue;
			}

			$candidate = $this->loadSigner(signerId: $candidateId);
			$standsHere = ($candidate['guardianForSignerId'] ?? '') === $signerId;
			if (($candidate['role'] ?? '') === 'guardian' && $standsHere === true && ($candidate['status'] ?? '') !== 'DECLINED') {
				$guardianIds[] = $candidateId;
			}
		}

		return $guardianIds;

	}                    

	   
                                                      
   
                                                    
                                                             
                                                 
   
                                                                                                  
    
	private function signerOnRequest(string $requestId, array $request, string $signerId): ?array {
		if ($signerId === '' || in_array($signerId, (array)($request['signerIds'] ?? []), true) === false) {
			return null;
		}

		$signer = $this->loadSigner(signerId: $signerId);
		if (($signer['signingRequestId'] ?? '') !== $requestId) {
			return null;
		}

		return $signer;

	}                       

	   
                                                              
   
                                                         
                                                                
   
                                                            
    
	private function samePerson(array $first, array $second): bool {
		$firstUid = (string)($first['userId'] ?? '');
		$firstEmail = (string)($first['email'] ?? '');
		$sameUid = $firstUid !== '' && $firstUid === (string)($second['userId'] ?? '');
		$sameEmail = $firstEmail !== '' && strcasecmp($firstEmail, (string)($second['email'] ?? '')) === 0;

		return $sameUid === true || $sameEmail === true;

	}                  

	   
                                                                               
   
                                                                       
                                                                    
                                                                            
                                                                      
   
                                                          
                                                                                       
                                                        
   
                                                                               
    
	private function actingIdentity(array $signer, ?array $verifiedActor, DateTimeImmutable $now): array {
		$moment = $now->format(DateTimeInterface::ATOM);
		$evidence = $signer['identityEvidence'] ?? null;
		if (is_array($evidence) === true && (string)($evidence['provider'] ?? '') !== '') {
			return [
				'provider' => (string)$evidence['provider'],
				'assurance' => $this->assurance(value: ($evidence['assurance'] ?? '')),
				'authenticatedAt' => (string)($evidence['authenticatedAt'] ?? $moment),
			];
		}

		if ($verifiedActor !== null) {
			return [
				'provider' => 'portaliq',
				'assurance' => $this->assurance(value: ($verifiedActor['trust'] ?? '')),
				'authenticatedAt' => $moment,
			];
		}

		return ['provider' => 'nextcloud-session', 'assurance' => 'low', 'authenticatedAt' => $moment];

	}                      

	   
                                                                          
   
                                            
   
                                                  
    
	private function assurance(mixed $value): string {
		if (in_array($value, self::ASSURANCES, true) === true) {
			return (string)$value;
		}

		return 'low';

	}                 

	   
                                       
   
                                                 
   
                                                                          
   
                                                                              
    
	private function loadSigner(string $signerId): array {
		$binding = $this->settingsService->resolveSignerRecordBinding();
		if ($binding === null) {
			throw new RuntimeException('Signer record register/schema not configured');
		}

		$object = $this->settingsService->getObjectService()->find(
			id: $signerId,
			register: $binding['register'],
			schema: $binding['schema']
		);

		if (is_object($object) === true && method_exists($object, 'jsonSerialize') === true) {
			return (array)$object->jsonSerialize();
		}

		return (array)$object;

	}                  
}           

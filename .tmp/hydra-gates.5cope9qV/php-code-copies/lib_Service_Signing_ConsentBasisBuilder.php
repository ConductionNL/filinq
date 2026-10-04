<?php

   
                        
  
                                                                           
                                                                          
                                 
  
                     
                                        
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use RuntimeException;

   
                                             
  
                                                                        
                                                                              
                                                                             
                                                   
  
                    
                                       
                                                 
                                                                                   
                                   
  
                                                                                   
   
class ConsentBasisBuilder {

	   
                
   
                                                                                          
   
                
    
	public function __construct(
		private readonly GuardianAgePolicy $agePolicy,
	) {

	}                   

	   
                                                                  
   
                                                                
                                                                                                      
   
                                                                                                            
   
                                                                                                             
   
                                                                                    
    
	public function build(array $request, array $signers): array {
		$age = $this->agePolicy->appliedAge(data: $request);
		$basis = [];
		foreach ($signers as $signerId => $signer) {
			$entry = $this->entryFor(signerId: (string)$signerId, signer: $signer, signers: $signers, age: $age);
			if ($entry !== null) {
				$basis[] = $entry;
			}
		}

		return $basis;

	}             

	   
                                                                          
   
                                                 
                                                          
                                                                                           
                                       
   
                                                         
   
                                                                                             
    
	private function entryFor(string $signerId, array $signer, array $signers, int $age): ?array {
		$birthDate = (string)($signer['birthDate'] ?? '');
		$signedAt = (string)($signer['signedAt'] ?? '');
		if (($signer['role'] ?? 'signer') === 'guardian' || $birthDate === '') {
			return null;
		}

		$moment = $this->agePolicy->moment(value: $signedAt);
		if ($this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $moment) === false) {
			return null;
		}

		$guardians = $this->actedGuardians(signerId: $signerId, signers: $signers);
		if ($guardians === []) {
			throw new RuntimeException(
				'Signer ' . $signerId . ' signed under ' . $age . ' and no guardian has acted for them, so the request cannot complete',
				409
			);
		}

		return [
			'signerId' => $signerId,
			'displayName' => (string)($signer['displayName'] ?? ''),
			'guardianConsentAge' => $age,
			'evaluatedAt' => $signedAt,
			'basis' => $this->basisOf(guardians: $guardians),
			'guardians' => $guardians,
		];

	}                

	   
                                             
   
                                                         
                                                                                           
   
                                                                        
    
	private function actedGuardians(string $signerId, array $signers): array {
		$guardians = [];
		foreach ($signers as $guardianId => $candidate) {
			$pointsHere = ($candidate['guardianForSignerId'] ?? '') === $signerId;
			$acted = ($candidate['status'] ?? '') === 'SIGNED';
			if (($candidate['role'] ?? '') === 'guardian' && $pointsHere === true && $acted === true) {
				$guardians[] = $this->guardianEntry(guardianId: (string)$guardianId, guardian: $candidate);
			}
		}

		return $guardians;

	}                      

	   
                                                
   
                                                              
                                                                       
   
                                                                                                                   
    
	private function guardianEntry(string $guardianId, array $guardian): array {
		$act = (string)($guardian['guardianAct'] ?? 'co-sign');
		$entry = [
			'signerId' => $guardianId,
			'displayName' => (string)($guardian['displayName'] ?? ''),
			'guardianAct' => $act,
			'actedAt' => (string)($guardian['signedAt'] ?? ''),
			'identity' => (array)($guardian['actingIdentity'] ?? []),
		];

		if ($act === 'consent') {
			$entry['consentStatement'] = (string)($guardian['consentStatement'] ?? '');
		}

		if ((string)($guardian['guardianRef'] ?? '') !== '') {
			$entry['guardianRef'] = (string)$guardian['guardianRef'];
		}

		return $entry;

	}                     

	   
                                                                                   
   
                                                                         
   
                                                                 
    
	private function basisOf(array $guardians): string {
		foreach ($guardians as $guardian) {
			if (($guardian['guardianAct'] ?? '') === 'co-sign') {
				return 'guardian-co-signature';
			}
		}

		return 'guardian-consent';

	}               
}           

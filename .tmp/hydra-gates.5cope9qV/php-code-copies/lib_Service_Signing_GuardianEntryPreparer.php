<?php

   
                          
  
                                                                               
                                                                              
             
  
                     
                                        
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use InvalidArgumentException;

   
                                                                     
  
                                                                           
                                                                             
                                                                              
                            
  
                    
                                       
                                                 
                                                                                   
                                   
  
                                                                                   
   
class GuardianEntryPreparer {

	   
                                    
   
                     
    
	public const ACTS = ['co-sign', 'consent'];

	   
                
   
                                                                                 
   
                
    
	public function __construct(
		private readonly GuardianAgePolicy $agePolicy,
	) {

	}                   

	   
                                                                
   
                                                                                          
                                                                   
                                                         
   
                                                                                                        
                                                                             
                                                                             
                                            
   
                                                                            
   
                                                                                    
    
	public function prepare(array $signers, int $age, DateTimeImmutable $now): array {
		$fields = [];
		foreach ($signers as $index => $entry) {
			$fields[$index] = $this->entryFields(entry: (array)$entry);
		}

		$links = $this->resolveLinks(signers: $signers, fields: $fields);
		$this->assertMinorsGuarded(fields: $fields, links: $links, age: $age, now: $now);

		return ['fields' => $fields, 'links' => $links];

	}               

	   
                                          
   
                                                        
   
                                                                                          
   
                                                                             
    
	private function entryFields(array $entry): array {
		$birthDate = trim((string)($entry['birthDate'] ?? ''));
		$role = $this->roleOf(entry: $entry);
		if ($role === '') {
			return [];
		}

		$fields = ['role' => $role];
		if ($birthDate !== '') {
			if ($this->agePolicy->parseBirthDate(value: $birthDate) === null) {
				throw new InvalidArgumentException('A birth date must be an ISO 8601 date (YYYY-MM-DD)', 400);
			}

			$fields['birthDate'] = $birthDate;
		}

		if ($role === 'guardian') {
			$fields = array_merge($fields, $this->guardianFields(entry: $entry));
		}

		return $fields;

	}                   

	   
                                                                        
   
                                                        
   
                                               
   
                                                                       
    
	private function roleOf(array $entry): string {
		$role = trim((string)($entry['role'] ?? ''));
		$named = trim((string)($entry['guardianFor'] ?? '')) !== '';
		$hasBirthDate = trim((string)($entry['birthDate'] ?? '')) !== '';

		if ($role === '' && $named === true) {
			$role = 'guardian';
		}

		if ($role === '' && $hasBirthDate === true) {
			$role = 'signer';
		}

		if ($role !== '' && in_array($role, ['signer', 'guardian'], true) === false) {
			throw new InvalidArgumentException('A signer role is signer or guardian, not "' . $role . '"', 400);
		}

		return $role;

	}              

	   
                                             
   
                                                          
   
                                                                                       
   
                                                                                                       
    
	private function guardianFields(array $entry): array {
		$act = trim((string)($entry['guardianAct'] ?? 'co-sign'));
		if (in_array($act, self::ACTS, true) === false) {
			throw new InvalidArgumentException('A guardian act is co-sign or consent, not "' . $act . '"', 400);
		}

		$fields = ['guardianAct' => $act];
		$statement = trim((string)($entry['consentStatement'] ?? ''));
		if ($act === 'consent' && $statement === '') {
			throw new InvalidArgumentException('A consent act needs the statement the guardian consents to', 400);
		}

		if ($statement !== '') {
			$fields['consentStatement'] = $statement;
		}

		$reference = trim((string)($entry['guardianRef'] ?? ''));
		if ($reference !== '') {
			$fields['guardianRef'] = $reference;
		}

		return $fields;

	}                      

	   
                                                                    
   
                                                                
                                                                                         
   
                                                                                     
   
                                                                                                                
    
	private function resolveLinks(array $signers, array $fields): array {
		$links = [];
		foreach ($fields as $index => $entryFields) {
			if (($entryFields['role'] ?? '') !== 'guardian') {
				continue;
			}

			$named = trim((string)(((array)$signers[$index])['guardianFor'] ?? ''));
			$target = $this->findEntry(signers: $signers, key: $named, except: $index);
			if ($target === null || ($fields[$target]['role'] ?? 'signer') === 'guardian') {
				throw new InvalidArgumentException(
					'A guardian entry must name another signer on this request in guardianFor',
					400
				);
			}

			$links[$index] = $target;
		}

		return $links;

	}                    

	   
                                                            
   
                                                                
                                                                
                                                                      
   
                                                                
    
	private function findEntry(array $signers, string $key, int|string $except): int|string|null {
		if ($key === '') {
			return null;
		}

		foreach ($signers as $index => $entry) {
			if ($index !== $except && $this->entryMatches(entry: (array)$entry, key: $key) === true) {
				return $index;
			}
		}

		return null;

	}                 

	   
                                                       
   
                                                           
                                                   
   
                                                                            
    
	private function entryMatches(array $entry, string $key): bool {
		$userId = (string)($entry['userId'] ?? '');
		$email = (string)($entry['email'] ?? '');

		return ($userId !== '' && $userId === $key) || ($email !== '' && strcasecmp($email, $key) === 0);

	}                    

	   
                                                                                   
   
                                                                                         
                                                                                           
                                       
                                                         
   
                
   
                                                   
    
	private function assertMinorsGuarded(array $fields, array $links, int $age, DateTimeImmutable $now): void {
		foreach ($fields as $index => $entryFields) {
			$birthDate = (string)($entryFields['birthDate'] ?? '');
			if ($birthDate === '' || $this->agePolicy->isUnderAge(birthDate: $birthDate, age: $age, moment: $now) === false) {
				continue;
			}

			if (($entryFields['role'] ?? '') === 'guardian') {
				throw new InvalidArgumentException('A guardian must have reached the age of ' . $age, 400);
			}

			if (in_array($index, $links, true) === false) {
				throw new InvalidArgumentException(
					'A signer under ' . $age . ' needs a guardian on this request, and this request names none for them',
					400
				);
			}
		}

	}                           
}           

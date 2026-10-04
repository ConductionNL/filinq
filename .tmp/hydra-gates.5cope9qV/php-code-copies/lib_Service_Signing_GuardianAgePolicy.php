<?php

   
                      
  
                                                                             
                                                                           
          
  
                     
                                        
                                                  
                                  
                                                                                    
                           
                                    
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use DateTimeImmutable;
use Exception;
use OCA\Filinq\Service\SettingsService;

   
                                                                      
  
                                                                            
                                                                           
                                                                              
                                  
  
                    
                                       
                                                 
                                                                                   
                                   
  
                                                                                   
   
class GuardianAgePolicy {

	   
                                                  
   
            
    
	public const DEFAULT_AGE = 16;

	   
                                                      
   
               
    
	public const SETTING = 'signing_guardian_consent_age';

	   
                
   
                                                                     
   
                
    
	public function __construct(
		private readonly SettingsService $settingsService,
	) {

	}                   

	   
                                                 
   
                                                                            
   
                                                                                        
   
                                                                                    
    
	public function appliedAge(array $data): int {
		$requested = $this->positiveInt(value: ($data['guardianConsentAge'] ?? null));

		return max($this->configuredAge(), ($requested ?? 0));

	}                  

	   
                                                                
   
                                                                           
                                                                          
   
                                                         
                                       
                                                           
   
                                         
   
                                                                                    
    
	public function isUnderAge(string $birthDate, int $age, DateTimeImmutable $moment): bool {
		$born = $this->parseBirthDate(value: $birthDate);
		if ($born === null || $born > $moment) {
			return true;
		}

		return $born->diff($moment)->y < $age;

	}                  

	   
                                 
   
                                            
   
                                                                                                            
   
                                                                                    
    
	public function parseBirthDate(string $value): ?DateTimeImmutable {
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1) {
			return null;
		}

		if (checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1]) === false) {
			return null;
		}

		return new DateTimeImmutable($value . 'T00:00:00+00:00');

	}                      

	   
                                         
   
                                                             
   
                                                                                              
   
                                                                                    
    
	public function moment(string $value): DateTimeImmutable {
		if ($value === '') {
			return new DateTimeImmutable();
		}

		try {
			return new DateTimeImmutable($value);
		} catch (Exception) {
			return new DateTimeImmutable();
		}

	}              

	   
                                                        
   
                                                                                  
    
	private function configuredAge(): int {
		$toggles = $this->settingsService->getFeatureToggles();

		return ($this->positiveInt(value: ($toggles[self::SETTING] ?? null)) ?? self::DEFAULT_AGE);

	}                     

	   
                                                                   
   
                                      
   
                                                                                
    
	private function positiveInt(mixed $value): ?int {
		if (is_string($value) === true && preg_match('/^\d+$/', trim($value)) === 1) {
			$value = (int)trim($value);
		}

		if (is_int($value) === false || $value < 1) {
			return null;
		}

		return $value;

	}                   
}           

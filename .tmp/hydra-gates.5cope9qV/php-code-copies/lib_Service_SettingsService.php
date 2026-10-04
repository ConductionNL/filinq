<?php

   
                   
  
                                                              
                                                             
                                                               
                                         
  
                     
                                
                                                  
                                  
                                                                                    
                           
                                    
  
                                              
                                              
                                              
                                              
                                                                 
  
                                                                    
                                    
   

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;

   
                                                             
  
                    
                               
                                                 
                                                                                   
                                   
  
                                                                 
                                                                                                                                                                      
   
class SettingsService {

	   
                                                                      
   
                                   
    
	private readonly string $appName;

	   
                               
   
                                                         
                                                   
                                                                                
                                                                
                                                                  
                                                                                                   
                                                                                           
   
                
   
                                                                  
    
	public function __construct(
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
		private readonly RegisterDiscoveryService $discoveryService,
		private readonly SettingsInitializer $initializer,
		private readonly OcrService $ocrService,
		private readonly LegalBasisProposalService $legalBasisProposal,
		private readonly OpenRegisterAvailabilityService $openRegister,
	) {
		$this->appName = 'filinq';

	}                   

	   
                                                                      
   
                                                                                 
    
	private function isOpenRegisterInstalled(): bool {
		return $this->openRegister->isInstalled();
	}                               

	   
                                                                    
   
                                                                                 
   
                                                             
   
                                               
    
	public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService {
		return $this->openRegister->getObjectService();
	}                        

	   
                                                    
   
                                                           
   
                                                     
   
                                               
    
	public function initialize(): array {
		return $this->initializer->initialize();
	}                  

	   
                                                                        
   
                                                                         
                                                              
                                                                      
                                                                        
                                                                       
                                                                   
                                                                        
                                                                          
                                                                        
                      
   
                                                                       
                                                           
   
                                                         
   
                                               
    
	public function getFeatureToggles(): array {
		return $this->loadFeatureToggles();
	}                         

	   
                                                
   
                                                        
   
                                               
    
	private function loadFeatureToggles(): array {
		return [
			'publication_objection_period_days' => (int)$this->config->getValueString(
				$this->appName,
				'publication_objection_period_days',
				'28'
			),
			'enable_language_detection' => $this->config->getValueString(
				$this->appName,
				'enable_language_detection',
				'1'
			) === '1',
			'enable_keyword_extraction' => $this->config->getValueString(
				$this->appName,
				'enable_keyword_extraction',
				'1'
			) === '1',
			'enable_topic_classification' => $this->config->getValueString(
				$this->appName,
				'enable_topic_classification',
				'1'
			) === '1',
			'signing_enabled' => $this->config->getValueString(
				$this->appName,
				'signing_enabled',
				'0'
			) === '1',
			'signing_provider' => $this->config->getValueString(
				$this->appName,
				'signing_provider',
				'native'
			),
			'signing_default_level' => $this->config->getValueString(
				$this->appName,
				'signing_default_level',
				'SES'
			),
			'signing_request_expiry_days' => (int)$this->config->getValueString(
				$this->appName,
				'signing_request_expiry_days',
				'30'
			),
			                                                                                                    
			'signing_guardian_consent_age' => $this->config->getValueString($this->appName, 'signing_guardian_consent_age', '16'),
			                                                           
			                                                             
			                                                                
			                                                          
			                                                        
			                                                    
			'filinq.anonymisation.default_output_format' => $this->config->getValueString(
				$this->appName,
				'filinq.anonymisation.default_output_format',
				'pdf-only'
			),
			                                                              
			                                                              
			'ocr_enabled' => $this->config->getValueString(
				$this->appName,
				'ocr_enabled',
				'1'
			) === '1',
			'ocr_languages' => $this->config->getValueString(
				$this->appName,
				'ocr_languages',
				'nld+eng'
			),
			'ocr_dpi' => (int)$this->config->getValueString(
				$this->appName,
				'ocr_dpi',
				'300'
			),
			                                                             
			                                                          
			                                                          
			                                                  
			'filinq.grondslagen.entity_type_bases' => $this->legalBasisProposal->getMapping(),
			                                                                    
			                                                                  
			                                                            
			                                                         
			'filinq.anonymisation.enabled_entity_types' => $this->legalBasisProposal->getEnabledEntityTypes(),
			                                                           
			                                                             
			                                                            
			                                                               
			                                                             
			                                    
			'filinq.confidentiality.label_vocabulary' => $this->getConfidentialityVocabulary(),
			                                                           
			                                                                    
			'filinq.confidentiality.prioritise_analysis' => $this->config->getValueBool(
				$this->appName,
				'filinq.confidentiality.prioritise_analysis',
				false
			),
		];

	}                          

	   
                                                                         
                                                                        
                                   
   
                                                                        
   
                                                                                                                                                                              
    
	private function getConfidentialityVocabulary(): array {
		$raw = $this->config->getValueString(
			$this->appName,
			ConfidentialityLabelService::VOCABULARY_KEY,
			''
		);
		if ($raw === '') {
			return ConfidentialityLabelService::DEFAULT_VOCABULARY;
		}

		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false || empty($decoded) === true) {
			return ConfidentialityLabelService::DEFAULT_VOCABULARY;
		}

		return $decoded;
	}                                    

	   
                                         
   
                                                                                     
   
                                                                  
    
	public function getOcrStatus(): array {
		return [
			'tesseractAvailable' => $this->ocrService->isTesseractAvailable(),
			'tesseractVersion' => $this->ocrService->getTesseractVersion(),
		];

	}                    

	   
                         
   
                                                                   
   
                                                        
   
                                               
                                                                  
    
	public function getAllSettings(): array {
		$data = [
			'objectTypes' => ['publicationConsent', 'template', 'templateVersion'],
			'openRegisters' => false,
			'availableRegisters' => [],
		];

		try {
			if ($this->isOpenRegisterInstalled() === true) {
				$data['openRegisters'] = true;
				$data['availableRegisters'] = $this->discoveryService->fetchAvailableRegisters();
			}
		} catch (\RuntimeException $e) {
			$this->logger->info(
				'OpenRegister service not available',
				['exception' => $e->getMessage()]
			);
		}         

		try {
			$data['configuration'] = $this->discoveryService->loadObjectTypeConfiguration(
				$data['objectTypes']
			);
			$data = array_merge($data, $this->loadFeatureToggles());
			$data['ocrStatus'] = $this->getOcrStatus();

			                                                               
			                                                               
			$data['grondslagEntityTypes'] = $this->legalBasisProposal->getSelectableEntityTypes();
			$data['grondslagBases'] = $this->legalBasisProposal->getAvailableBases();

			                                                               
			                                                               
			                                                             
			$data['grondslagEntityTypes'] = $this->legalBasisProposal->getSelectableEntityTypes();
			$data['grondslagBases'] = $this->legalBasisProposal->getAvailableBases();

			return $data;
		} catch (Exception $e) {
			throw new RuntimeException('Failed to retrieve settings: ' . $e->getMessage());
		}         

	}                      

	   
                                                 
   
                                            
   
                                            
    
	private function convertValueToString(mixed $value): string {
		if (is_array($value) === true || is_object($value) === true) {
			return json_encode($value);
		}

		return (string)$value;
	}                            

	   
                                                                    
   
                                                                   
                                                                           
                                                                           
                                                                          
   
                           
    
	private const WRITABLE_KEYS = [
		'publicationConsent_register',
		'publicationConsent_schema',
		'publicationConsent_source',
		'template_register',
		'template_schema',
		'template_source',
		'publication_objection_period_days',
		'enable_language_detection',
		'enable_keyword_extraction',
		'enable_topic_classification',
		'signing_enabled',
		'signing_provider',
		'signing_default_level',
		'signing_request_expiry_days',
		'signing_guardian_consent_age',
		'ocr_enabled',
		'ocr_languages',
		'ocr_dpi',
		'filinq.confidentiality.label_vocabulary',
		'filinq.confidentiality.prioritise_analysis',
	];

	   
                                     
   
                                                                         
                                                                           
                                                    
   
                                                                 
   
                                                                   
   
                                                      
   
                                               
    
	public function updateSettings(array $data): array {
		try {
			foreach ($data as $key => $value) {
				if (empty($key) === true) {
					$this->logger->warning(
						'Skipping empty key in updateSettings',
						['value' => $value]
					);
					continue;
				}

				if (in_array($key, self::WRITABLE_KEYS, true) === false) {
					$this->logger->warning(
						'Skipping non-allowlisted key in updateSettings',
						['key' => $key]
					);
					unset($data[$key]);
					continue;
				}

				$stringValue = $this->convertValueToString(value: $value);
				$this->config->setValueString($this->appName, $key, $stringValue);
				$data[$key] = $this->config->getValueString($this->appName, $key);
			}             

			$this->logger->info(
				'Settings updated successfully',
				['updatedKeys' => array_keys($data)]
			);

			return $data;
		} catch (Exception $e) {
			throw new RuntimeException('Failed to update settings: ' . $e->getMessage());
		}         

	}                      

	   
                                                                           
   
                                                                            
                                                                          
                                                                  
                                                                        
                                                                             
                                                                             
                                            
   
                                                                                         
   
                                                 
    
	public function resolveSigningRequestBinding(): ?array {
		$register = $this->config->getValueString('filinq', 'signingRequest_register', '');
		$schema = $this->config->getValueString('filinq', 'signingRequest_schema', '');
		if ($register === '' || $schema === '') {
			return null;
		}

		return ['register' => $register, 'schema' => $schema];
	}                                    

	   
                                                                         
   
                                                                            
                                                                         
            
   
                                                                                         
   
                                                 
    
	public function resolveSignerRecordBinding(): ?array {
		$register = $this->config->getValueString('filinq', 'signerRecord_register', '');
		$schema = $this->config->getValueString('filinq', 'signerRecord_schema', '');
		if ($register === '' || $schema === '') {
			return null;
		}

		return ['register' => $register, 'schema' => $schema];
	}                                  

	   
                                                                                
   
                                                                                         
   
                                                                    
    
	public function resolveFinancialExtractionBinding(): ?array {
		$register = $this->config->getValueString('filinq', 'financialExtraction_register', '');
		$schema = $this->config->getValueString('filinq', 'financialExtraction_schema', '');
		if ($register === '' || $schema === '') {
			return null;
		}

		return ['register' => $register, 'schema' => $schema];
	}                                         

	   
                                                                             
   
                                                                                         
   
                                                         
    
	public function resolveGlAccountBookingBinding(): ?array {
		$register = $this->config->getValueString('filinq', 'glAccountBooking_register', '');
		$schema = $this->config->getValueString('filinq', 'glAccountBooking_schema', '');
		if ($register === '' || $schema === '') {
			return null;
		}

		return ['register' => $register, 'schema' => $schema];
	}                                      

	   
                                                                                 
   
                                                                                         
   
                                                         
    
	public function resolveGlAccountMappingRuleBinding(): ?array {
		$register = $this->config->getValueString('filinq', 'glAccountMappingRule_register', '');
		$schema = $this->config->getValueString('filinq', 'glAccountMappingRule_schema', '');
		if ($register === '' || $schema === '') {
			return null;
		}

		return ['register' => $register, 'schema' => $schema];
	}                                          
}           

<?php
/**
 * Settings Service
 *
 * Service for handling settings-related operations in DocuDesk.
 * Provides functionality for retrieving and saving settings.
 * Delegates initialization to SettingsInitializer and register
 * discovery to RegisterDiscoveryService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/specs/admin-settings/spec.md
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for handling settings-related operations in DocuDesk
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.1
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-optionally-suggest-batchfolder-analysis-priority-req-ddfcl-003
 */
class SettingsService
{

    /**
     * The application name for identification and configuration purposes
     *
     * @var string The name of the app
     */
    private readonly string $appName;

    /**
     * SettingsService constructor
     *
     * @param IAppConfig                      $config            App configuration interface
     * @param LoggerInterface                 $logger            Logger interface
     * @param RegisterDiscoveryService        $discoveryService  Register discovery service
     * @param SettingsInitializer             $initializer       Settings initializer
     * @param OcrService                      $ocrService        OCR service for Tesseract status
     * @param GrondslagProposalService        $grondslagProposal Grondslag-per-entity-type proposal service
     * @param OpenRegisterAvailabilityService $openRegister      OpenRegister availability resolver
     *
     * @return void
     *
     * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger,
        private readonly RegisterDiscoveryService $discoveryService,
        private readonly SettingsInitializer $initializer,
        private readonly OcrService $ocrService,
        private readonly GrondslagProposalService $grondslagProposal,
        private readonly OpenRegisterAvailabilityService $openRegister
    ) {
        $this->appName = 'docudesk';

    }//end __construct()

    /**
     * Checks if OpenRegister is installed and meets version requirements
     *
     * @return bool True if OpenRegister is installed and meets version requirements
     */
    private function isOpenRegisterInstalled(): bool
    {
        return $this->openRegister->isInstalled();

    }//end isOpenRegisterInstalled()

    /**
     * Attempts to retrieve the OpenRegister service from the container
     *
     * @return \OCA\OpenRegister\Service\ObjectService|null The OpenRegister service
     *
     * @throws \RuntimeException If the service is not available
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function getObjectService(): ?\OCA\OpenRegister\Service\ObjectService
    {
        return $this->openRegister->getObjectService();

    }//end getObjectService()

    /**
     * Initializes the app with all required components
     *
     * @return array<string, mixed> The initialization results
     *
     * @throws \RuntimeException If initialization fails
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function initialize(): array
    {
        return $this->initializer->initialize();

    }//end initialize()

    /**
     * The feature toggles alone, without the rest of the settings payload.
     *
     * {@see getAllSettings()} assembles what the ADMIN SETTINGS PAGE needs:
     * every available register with its schemas, the object-type
     * configuration, OCR status and the grondslag selector data. That is
     * correct for a settings screen and catastrophic on a write path — the
     * register/schema discovery alone issued 1,471 `SchemaMapper::find()`
     * calls (one per schema on the instance) when it was reached from
     * {@see \OCA\DocuDesk\EventListener\EnrichmentRunner}, which runs inside
     * an unrelated app's object save. Measured 2026-07-29: 96% of ALL schema
     * reads during an OpenRegister object create originated there, and the
     * create took 9-17s.
     *
     * Anything that only needs a toggle MUST call this instead. These are
     * plain IAppConfig reads and touch no register or schema.
     *
     * @return array<string, mixed> Feature toggle settings.
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function getFeatureToggles(): array
    {
        return $this->loadFeatureToggles();

    }//end getFeatureToggles()

    /**
     * Load feature toggle settings from app config
     *
     * @return array<string, mixed> Feature toggle settings
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    private function loadFeatureToggles(): array
    {
        return [
            'publication_objection_period_days'            => (int) $this->config->getValueString(
                $this->appName,
                'publication_objection_period_days',
                '28'
            ),
            'enable_language_detection'                    => $this->config->getValueString(
                $this->appName,
                'enable_language_detection',
                '1'
            ) === '1',
            'enable_keyword_extraction'                    => $this->config->getValueString(
                $this->appName,
                'enable_keyword_extraction',
                '1'
            ) === '1',
            'enable_topic_classification'                  => $this->config->getValueString(
                $this->appName,
                'enable_topic_classification',
                '1'
            ) === '1',
            'signing_enabled'                              => $this->config->getValueString(
                $this->appName,
                'signing_enabled',
                '0'
            ) === '1',
            'signing_provider'                             => $this->config->getValueString(
                $this->appName,
                'signing_provider',
                'native'
            ),
            'signing_default_level'                        => $this->config->getValueString(
                $this->appName,
                'signing_default_level',
                'SES'
            ),
            'signing_request_expiry_days'                  => (int) $this->config->getValueString(
                $this->appName,
                'signing_request_expiry_days',
                '30'
            ),
            // Anonymise-output-as-pdf-by-default — tenant-wide default
            // for the anonymise endpoint's `outputFormat` request param.
            // 'pdf-only' (default) converts via the cascade and deletes the
            // native anonymised intermediate so only the PDF remains;
            // 'pdf' converts but keeps the native intermediate too;
            // 'preserve' returns it in the native input format.
            'docudesk.anonymisation.default_output_format' => $this->config->getValueString(
                $this->appName,
                'docudesk.anonymisation.default_output_format',
                'pdf-only'
            ),
            // OCR document scanning (ocr-document-scanning) — tenant-wide
            // toggles read by OcrService for scanned-PDF text extraction.
            'ocr_enabled'                                  => $this->config->getValueString(
                $this->appName,
                'ocr_enabled',
                '1'
            ) === '1',
            'ocr_languages'                                => $this->config->getValueString(
                $this->appName,
                'ocr_languages',
                'nld+eng'
            ),
            'ocr_dpi'                                      => (int) $this->config->getValueString(
                $this->appName,
                'ocr_dpi',
                '300'
            ),
            // Propose-grondslag-per-entity-type — instance-global map of
            // entity type → base slug(s), used to pre-fill a proposed
            // grondslag onto freshly-detected entities. Decoded to an
            // object so the settings UI can bind it directly.
            'docudesk.grondslagen.entity_type_bases'       => $this->grondslagProposal->getMapping(),
            // Entity types left enabled for automatic detection. Returned as an
            // explicit list (all curated types when unset) so the settings UI
            // renders the selector all-on by default; an empty/complete
            // selection is treated as "all types" at detection time.
            'docudesk.anonymisation.enabled_entity_types'  => $this->grondslagProposal->getEnabledEntityTypes(),
            // Files-confidential-labels — read-only sensitivity signal
            // ingested from files_confidential (TSCP/BAILS system tags).
            // The vocabulary maps tag/label name to a normalised level;
            // decoded to an object so the settings UI can bind it directly
            // and falls back to the seeded TSCP/BAILS default names when
            // unset (design.md Open Questions).
            'docudesk.confidentiality.label_vocabulary'    => $this->getConfidentialityVocabulary(),
            // Off by default: purely a suggestion signal that reorders
            // batch/folder analysis, never gates/blocks/redacts (design.md D3).
            'docudesk.confidentiality.prioritise_analysis' => $this->config->getValueBool(
                $this->appName,
                'docudesk.confidentiality.prioritise_analysis',
                false
            ),
        ];

    }//end loadFeatureToggles()

    /**
     * Read the configured confidentiality-label vocabulary for the settings
     * UI, falling back to ConfidentialityLabelService's default TSCP/BAILS
     * names when unset or unreadable.
     *
     * @return array<string, int> Map of label/tag name to normalised level
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
     */
    private function getConfidentialityVocabulary(): array
    {
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

    }//end getConfidentialityVocabulary()

    /**
     * Get Tesseract OCR availability status
     *
     * @return array{tesseractAvailable: bool, tesseractVersion: string|null} OCR status
     *
     * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.2
     */
    public function getOcrStatus(): array
    {
        return [
            'tesseractAvailable' => $this->ocrService->isTesseractAvailable(),
            'tesseractVersion'   => $this->ocrService->getTesseractVersion(),
        ];

    }//end getOcrStatus()

    /**
     * Retrieve all settings
     *
     * @return array<string, mixed> The current settings configuration
     *
     * @throws RuntimeException If settings retrieval fails
     *
     * @spec openspec/specs/admin-settings/spec.md
     * @spec openspec/changes/ocr-document-scanning/tasks.md#task-4.3
     */
    public function getAllSettings(): array
    {
        $data = [
            'objectTypes'        => ['publicationConsent', 'template', 'templateVersion'],
            'openRegisters'      => false,
            'availableRegisters' => [],
        ];

        try {
            if ($this->isOpenRegisterInstalled() === true) {
                $data['openRegisters']      = true;
                $data['availableRegisters'] = $this->discoveryService->fetchAvailableRegisters();
            }
        } catch (\RuntimeException $e) {
            $this->logger->info(
                'OpenRegister service not available',
                ['exception' => $e->getMessage()]
            );
        }//end try

        try {
            $data['configuration'] = $this->discoveryService->loadObjectTypeConfiguration(
                $data['objectTypes']
            );
            $data = array_merge($data, $this->loadFeatureToggles());
            $data['ocrStatus'] = $this->getOcrStatus();

            // Data for the grondslag-per-entity-type selector: the curated
            // entity types and the available `base` records (slug + name).
            $data['grondslagEntityTypes'] = $this->grondslagProposal->getSelectableEntityTypes();
            $data['grondslagBases']       = $this->grondslagProposal->getAvailableBases();

            // Data for the grondslag-per-entity-type selector: the curated
            // entity types and the available `base` records (slug + name).
            // Both degrade to safe defaults when OpenRegister is absent.
            $data['grondslagEntityTypes'] = $this->grondslagProposal->getSelectableEntityTypes();
            $data['grondslagBases']       = $this->grondslagProposal->getAvailableBases();

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to retrieve settings: '.$e->getMessage());
        }//end try

    }//end getAllSettings()

    /**
     * Convert a setting value to string for storage
     *
     * @param mixed $value The value to convert
     *
     * @return string The string representation
     */
    private function convertValueToString(mixed $value): string
    {
        if (is_array($value) === true || is_object($value) === true) {
            return json_encode($value);
        }

        return (string) $value;

    }//end convertValueToString()

    /**
     * Keys that are permitted to be written via the settings endpoint.
     *
     * This allowlist prevents any authenticated user (wave-3 C1) from
     * overwriting security-sensitive keys such as signing_verification_secret
     * through the open settings POST endpoint.  Secret keys (signing_* tokens
     * etc.) must be managed through dedicated, separately-secured endpoints.
     *
     * @var array<int, string>
     */
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
        'ocr_enabled',
        'ocr_languages',
        'ocr_dpi',
        'docudesk.confidentiality.label_vocabulary',
        'docudesk.confidentiality.prioritise_analysis',
    ];

    /**
     * Update the settings configuration
     *
     * Only keys present in WRITABLE_KEYS may be written; all other keys are
     * silently skipped.  This prevents escalation via security-sensitive keys
     * such as signing_verification_secret (wave-3 C1).
     *
     * @param array<string, mixed> $data The settings data to update
     *
     * @return array<string, mixed> The updated settings configuration
     *
     * @throws \RuntimeException If settings update fails
     *
     * @spec openspec/specs/admin-settings/spec.md
     */
    public function updateSettings(array $data): array
    {
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
            }//end foreach

            $this->logger->info(
                'Settings updated successfully',
                ['updatedKeys' => array_keys($data)]
            );

            return $data;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to update settings: '.$e->getMessage());
        }//end try

    }//end updateSettings()

    /**
     * Resolve the signingRequest register/schema binding, failing closed.
     *
     * These bindings live here, next to the settings surface that writes them,
     * rather than in each consumer: every call site used to read them inline
     * with an empty-string default and pass the result straight into
     * saveObject()/find(). Unconfigured, that wrote signing requests — the
     * audit trail behind an eIDAS-level signature — into register '' and schema
     * '', silently. Mirrors OpenRegisterResolver::getRegisterAndSchema(), which
     * already does exactly this for templates.
     *
     * @return array{register: string, schema: string}|null The binding, or null when unset.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function resolveSigningRequestBinding(): ?array
    {
        $register = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema   = $this->config->getValueString('docudesk', 'signingRequest_schema', '');
        if ($register === '' || $schema === '') {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end resolveSigningRequestBinding()

    /**
     * Resolve the signerRecord register/schema binding, failing closed.
     *
     * A signer record carries the identity a signature is attributed to, so an
     * unconfigured binding loses exactly the evidence a signature exists to
     * provide.
     *
     * @return array{register: string, schema: string}|null The binding, or null when unset.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function resolveSignerRecordBinding(): ?array
    {
        $register = $this->config->getValueString('docudesk', 'signerRecord_register', '');
        $schema   = $this->config->getValueString('docudesk', 'signerRecord_schema', '');
        if ($register === '' || $schema === '') {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end resolveSignerRecordBinding()

    /**
     * Resolve the financialExtraction register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string}|null The binding, or null when unset.
     *
     * @spec openspec/specs/financial-document-field-extraction/spec.md
     */
    public function resolveFinancialExtractionBinding(): ?array
    {
        $register = $this->config->getValueString('docudesk', 'financialExtraction_register', '');
        $schema   = $this->config->getValueString('docudesk', 'financialExtraction_schema', '');
        if ($register === '' || $schema === '') {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end resolveFinancialExtractionBinding()

    /**
     * Resolve the glAccountBooking register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string}|null The binding, or null when unset.
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function resolveGlAccountBookingBinding(): ?array
    {
        $register = $this->config->getValueString('docudesk', 'glAccountBooking_register', '');
        $schema   = $this->config->getValueString('docudesk', 'glAccountBooking_schema', '');
        if ($register === '' || $schema === '') {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end resolveGlAccountBookingBinding()

    /**
     * Resolve the glAccountMappingRule register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string}|null The binding, or null when unset.
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function resolveGlAccountMappingRuleBinding(): ?array
    {
        $register = $this->config->getValueString('docudesk', 'glAccountMappingRule_register', '');
        $schema   = $this->config->getValueString('docudesk', 'glAccountMappingRule_schema', '');
        if ($register === '' || $schema === '') {
            return null;
        }

        return ['register' => $register, 'schema' => $schema];

    }//end resolveGlAccountMappingRuleBinding()
}//end class

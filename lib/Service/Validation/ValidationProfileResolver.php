<?php
/**
 * Validation Profile Resolver
 *
 * Loads the configured validation profiles JSON and resolves the effective
 * profile (allowed mimes, required metadata fields, per-check severities) for
 * a document type, falling back to the shipped default profile.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Validation;

use OCA\DocuDesk\Service\DocumentValidationService;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Resolves the effective validation profile for a document type.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Validation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 */
class ValidationProfileResolver
{

    /**
     * App config key for the validation profiles JSON.
     *
     * @var string
     */
    private const CONFIG_PROFILES = 'validation.profiles';

    /**
     * The five file-level checks plus the metadata check, in catalogue order.
     *
     * @var array<int, string>
     */
    private const ALL_CHECKS = [
        DocumentValidationService::CHECK_FORMAT_NOT_ALLOWED,
        DocumentValidationService::CHECK_EXTENSION_MIME,
        DocumentValidationService::CHECK_FILE_UNREADABLE,
        DocumentValidationService::CHECK_PDF_ENCRYPTED,
        DocumentValidationService::CHECK_TEXT_LAYER_MISSING,
        DocumentValidationService::CHECK_METADATA_INCOMPLETE,
    ];

    /**
     * The severities a profile may assign to a check.
     *
     * @var array<int, string>
     */
    private const ALL_SEVERITIES = [
        DocumentValidationService::SEVERITY_OFF,
        DocumentValidationService::SEVERITY_WARNING,
        DocumentValidationService::SEVERITY_BLOCKING,
    ];

    /**
     * Default mime allowlist shipped with the default profile.
     *
     * @var array<int, string>
     */
    private const DEFAULT_ALLOWED_MIMES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
        'application/vnd.oasis.opendocument.text',
        'text/plain',
        'text/markdown',
        'text/html',
    ];

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger    Logger.
     * @param IAppConfig      $appConfig App configuration.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IAppConfig $appConfig
    ) {

    }//end __construct()

    /**
     * Resolve the validation profile for a document type, falling back to default.
     *
     * @param string $documentType The document type.
     *
     * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
     *
     * @spec openspec/specs/document-validation-checks/spec.md
     */
    public function resolve(string $documentType): array
    {
        $defaults = $this->defaultProfile();

        $raw = $this->rawProfile(documentType: $documentType);
        if (is_array($raw) === false) {
            return $defaults;
        }

        return [
            'allowedMimes'   => $this->overrideList(raw: $raw, key: 'allowedMimes', fallback: $defaults['allowedMimes']),
            'requiredFields' => $this->overrideList(raw: $raw, key: 'requiredFields', fallback: $defaults['requiredFields']),
            'severities'     => $this->mergeSeverities(raw: $raw, defaults: $defaults['severities']),
        ];

    }//end resolve()

    /**
     * Pick the raw profile entry for a document type, or the 'default' entry.
     *
     * @param string $documentType The document type.
     *
     * @return mixed The raw profile entry, or null when neither exists.
     */
    private function rawProfile(string $documentType): mixed
    {
        $profiles = $this->loadProfiles();

        if ($documentType !== '' && isset($profiles[$documentType]) === true) {
            return $profiles[$documentType];
        }

        if (isset($profiles['default']) === true) {
            return $profiles['default'];
        }

        return null;

    }//end rawProfile()

    /**
     * Read a list override off a raw profile, keeping the fallback when the
     * key is absent or not a list.
     *
     * @param array<string, mixed> $raw      The raw profile entry.
     * @param string               $key      The override key.
     * @param array<int, string>   $fallback The default list.
     *
     * @return array<int, string> The effective list.
     */
    private function overrideList(array $raw, string $key, array $fallback): array
    {
        if (isset($raw[$key]) === true && is_array($raw[$key]) === true) {
            return array_values($raw[$key]);
        }

        return $fallback;

    }//end overrideList()

    /**
     * Merge a raw profile's severity overrides onto the defaults, ignoring
     * unknown check ids and invalid severity values.
     *
     * @param array<string, mixed>  $raw      The raw profile entry.
     * @param array<string, string> $defaults The default severities.
     *
     * @return array<string, string> The effective severities.
     */
    private function mergeSeverities(array $raw, array $defaults): array
    {
        if (isset($raw['severities']) === false || is_array($raw['severities']) === false) {
            return $defaults;
        }

        $severities = $defaults;
        foreach ($raw['severities'] as $check => $sev) {
            if (in_array($check, self::ALL_CHECKS, true) === true
                && in_array($sev, self::ALL_SEVERITIES, true) === true
            ) {
                $severities[$check] = $sev;
            }
        }

        return $severities;

    }//end mergeSeverities()

    /**
     * The shipped default profile (every check warn-only).
     *
     * @return array{allowedMimes:array<int,string>, requiredFields:array<int,string>, severities:array<string,string>}
     */
    private function defaultProfile(): array
    {
        $severities = [];
        foreach (self::ALL_CHECKS as $check) {
            $severities[$check] = DocumentValidationService::SEVERITY_WARNING;
        }

        return [
            'allowedMimes'   => self::DEFAULT_ALLOWED_MIMES,
            'requiredFields' => [],
            'severities'     => $severities,
        ];

    }//end defaultProfile()

    /**
     * Load and decode the configured profiles JSON.
     *
     * @return array<string, mixed> The decoded profiles (empty on error).
     */
    private function loadProfiles(): array
    {
        $raw = $this->appConfig->getValueString('docudesk', self::CONFIG_PROFILES, '');
        if ($raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logger->warning('Invalid docudesk.validation.profiles JSON; using defaults.');
            return [];
        }

        if (is_array($decoded) === false) {
            return [];
        }

        return $decoded;

    }//end loadProfiles()
}//end class

<?php
/**
 * Open Register Resolver
 *
 * Service for resolving OpenRegister register/schema configuration
 * and validating namespace strings. Extracted from TemplateService
 * to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\RegisterNotConfiguredException;

/**
 * Service for resolving OpenRegister configuration and namespace validation
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class OpenRegisterResolver
{
    /**
     * Constructor for OpenRegisterResolver
     *
     * @param SettingsService $settingsService Settings service for register/schema IDs
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService
    ) {

    }//end __construct()

    /**
     * Get the template register and schema IDs from settings
     *
     * @return array{register: string, schema: string} Register and schema IDs
     *
     * @throws RegisterNotConfiguredException If template register/schema is not configured
     *
     * @spec openspec/specs/openregister-bridge/spec.md
     */
    public function getRegisterAndSchema(): array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['template_register'] ?? '';
        $schema   = $settings['configuration']['template_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            throw new RegisterNotConfiguredException(message: 'Template register/schema not configured');
        }

        return ['register' => $register, 'schema' => $schema];

    }//end getRegisterAndSchema()

    /**
     * Get the template version register and schema IDs from settings
     *
     * @return array{register: string, schema: string} Register and schema IDs
     *
     * @throws RegisterNotConfiguredException If template version register/schema is not configured
     *
     * @spec openspec/specs/openregister-bridge/spec.md
     */
    public function getVersionRegisterAndSchema(): array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['templateVersion_register'] ?? '';
        $schema   = $settings['configuration']['templateVersion_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            throw new RegisterNotConfiguredException(
                message: 'Template version register/schema not configured'
            );
        }

        return ['register' => $register, 'schema' => $schema];

    }//end getVersionRegisterAndSchema()

    /**
     * Validate that a namespace string is a valid Nextcloud app ID
     *
     * @param string $namespace The namespace to validate
     *
     * @return bool True if valid
     *
     * @throws Exception If the namespace is invalid
     *
     * @spec openspec/specs/openregister-bridge/spec.md
     */
    public function validateNamespace(string $namespace): bool
    {
        if (preg_match(pattern: '/^[a-z0-9]+$/', subject: $namespace) !== 1) {
            throw new Exception(
                message: 'Invalid namespace: must be lowercase alphanumeric only',
                code: 400
            );
        }

        return true;

    }//end validateNamespace()

    /**
     * Resolve the financialExtraction register/schema binding, failing closed.
     *
     * SettingsService owns the READ and returns null when either half is unset;
     * this turns that null into the same RegisterNotConfiguredException the
     * template accessors above already raise. The split exists so the two
     * concerns sit where they belong — the settings surface reads config, this
     * resolver decides that an unset binding is an error — and it keeps the
     * exception type out of SettingsService, whose object coupling is at its
     * PHPMD ceiling.
     *
     * @return array{register: string, schema: string} The resolved binding.
     *
     * @throws RegisterNotConfiguredException If the binding is not configured.
     *
     * @spec openspec/specs/financial-document-field-extraction/spec.md
     */
    public function getFinancialExtractionRegisterAndSchema(): array
    {
        $binding = $this->settingsService->resolveFinancialExtractionBinding();
        if ($binding === null) {
            throw new RegisterNotConfiguredException(
                message: 'Financial extraction register/schema not configured'
            );
        }

        return $binding;

    }//end getFinancialExtractionRegisterAndSchema()

    /**
     * Resolve the glAccountBooking register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string} The resolved binding.
     *
     * @throws RegisterNotConfiguredException If the binding is not configured.
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function getGlAccountBookingRegisterAndSchema(): array
    {
        $binding = $this->settingsService->resolveGlAccountBookingBinding();
        if ($binding === null) {
            throw new RegisterNotConfiguredException(
                message: 'GL account booking register/schema not configured'
            );
        }

        return $binding;

    }//end getGlAccountBookingRegisterAndSchema()

    /**
     * Resolve the glAccountMappingRule register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string} The resolved binding.
     *
     * @throws RegisterNotConfiguredException If the binding is not configured.
     *
     * @spec openspec/specs/ai-gl-account-suggestion/spec.md
     */
    public function getGlAccountMappingRuleRegisterAndSchema(): array
    {
        $binding = $this->settingsService->resolveGlAccountMappingRuleBinding();
        if ($binding === null) {
            throw new RegisterNotConfiguredException(
                message: 'GL account mapping rule register/schema not configured'
            );
        }

        return $binding;

    }//end getGlAccountMappingRuleRegisterAndSchema()

    /**
     * Resolve the signerRecord register/schema binding, failing closed.
     *
     * @return array{register: string, schema: string} The resolved binding.
     *
     * @throws RegisterNotConfiguredException If the binding is not configured.
     *
     * @spec openspec/specs/document-signing/spec.md
     */
    public function getSignerRecordRegisterAndSchema(): array
    {
        $binding = $this->settingsService->resolveSignerRecordBinding();
        if ($binding === null) {
            throw new RegisterNotConfiguredException(
                message: 'Signer record register/schema not configured'
            );
        }

        return $binding;

    }//end getSignerRecordRegisterAndSchema()
}//end class

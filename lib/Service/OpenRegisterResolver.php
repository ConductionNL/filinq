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
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;

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
     * @throws Exception If template register/schema is not configured
     */
    public function getRegisterAndSchema(): array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['template_register'] ?? '';
        $schema   = $settings['configuration']['template_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            throw new Exception(message: 'Template register/schema not configured', code: 503);
        }

        return ['register' => $register, 'schema' => $schema];

    }//end getRegisterAndSchema()

    /**
     * Get the template version register and schema IDs from settings
     *
     * @return array{register: string, schema: string} Register and schema IDs
     *
     * @throws Exception If template version register/schema is not configured
     */
    public function getVersionRegisterAndSchema(): array
    {
        $settings = $this->settingsService->getAllSettings();
        $register = $settings['configuration']['templateVersion_register'] ?? '';
        $schema   = $settings['configuration']['templateVersion_schema'] ?? '';

        if (empty($register) === true || empty($schema) === true) {
            throw new Exception(
                message: 'Template version register/schema not configured',
                code: 503
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
}//end class

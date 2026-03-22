<?php
/**
 * WOO Profile Service
 *
 * Manages WOO entity category profiles for anonymization.
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

use OCP\IAppConfig;

/**
 * Service for managing WOO entity category profiles
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class WooProfileService
{

    /**
     * @var array<int, string>
     */
    private const DEFAULT_ANONYMIZE = ['PERSON', 'BSN', 'PHONE', 'EMAIL', 'IBAN', 'ADDRESS'];

    /**
     * @var array<int, string>
     */
    private const DEFAULT_KEEP = ['ORGANIZATION', 'LOCATION', 'DATE'];


    /**
     * Constructor for WooProfileService
     *
     * @param IAppConfig $appConfig App configuration
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $appConfig
    ) {

    }//end __construct()


    /**
     * Get the active WOO entity profile
     *
     * @return array{anonymize: array<int, string>, keep: array<int, string>} The profile
     */
    public function getProfile(): array
    {
        $stored = $this->appConfig->getValueString('docudesk', 'docudesk_woo_entity_profiles', '');

        if ($stored !== '') {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) === true && isset($decoded['anonymize']) === true && isset($decoded['keep']) === true) {
                return $decoded;
            }
        }

        return ['anonymize' => self::DEFAULT_ANONYMIZE, 'keep' => self::DEFAULT_KEEP];

    }//end getProfile()


    /**
     * Save a WOO entity profile
     *
     * @param array{anonymize: array<int, string>, keep: array<int, string>} $profile The profile
     *
     * @return void
     */
    public function saveProfile(array $profile): void
    {
        $this->appConfig->setValueString('docudesk', 'docudesk_woo_entity_profiles', json_encode($profile));

    }//end saveProfile()


    /**
     * Check if an entity type should be anonymized
     *
     * @param string $entityType The entity type
     *
     * @return bool True if should be anonymized
     */
    public function shouldAnonymize(string $entityType): bool
    {
        $profile = $this->getProfile();

        return in_array($entityType, $profile['anonymize'], true);

    }//end shouldAnonymize()


}//end class

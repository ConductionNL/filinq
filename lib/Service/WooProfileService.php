<?php
declare(strict_types=1);
namespace OCA\DocuDesk\Service;
use OCP\IAppConfig;
/**
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class WooProfileService
{
    private const DEFAULT_ANONYMIZE = ['PERSON', 'BSN', 'PHONE', 'EMAIL', 'IBAN', 'ADDRESS'];
    private const DEFAULT_KEEP      = ['ORGANIZATION', 'LOCATION', 'DATE'];


    public function __construct(private readonly IAppConfig $appConfig)
    {

    }//end __construct()


    /**
     * @return array{anonymize: array<string>, keep: array<string>}
     */
    public function getProfile(): array
    {
        $stored = $this->appConfig->getValueString('docudesk', 'docudesk_woo_entity_profiles', '');
        if ($stored !== '') {
            $decoded = json_decode($stored, true);
            if (is_array($decoded) === true && isset($decoded['anonymize'], $decoded['keep']) === true) {
                return $decoded;
            }
        }

        return ['anonymize' => self::DEFAULT_ANONYMIZE, 'keep' => self::DEFAULT_KEEP];

    }//end getProfile()


    public function saveProfile(array $profile): void
    {
        $this->appConfig->setValueString('docudesk', 'docudesk_woo_entity_profiles', json_encode($profile));

    }//end saveProfile()


    public function shouldAnonymize(string $entityType): bool
    {
        return in_array($entityType, $this->getProfile()['anonymize'], true);

    }//end shouldAnonymize()


}//end class

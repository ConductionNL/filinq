<?php
/**
 * Anonymization Dashboard Widget
 *
 * Nextcloud Dashboard widget for quick document anonymization.
 *
 * @category  Dashboard
 * @package   OCA\DocuDesk\Dashboard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Dashboard;

use OCA\DocuDesk\AppInfo\Application;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\IIconWidget;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Dashboard widget for document anonymization
 */
class AnonymizationWidget implements IWidget, IIconWidget
{


    public function __construct(
        private readonly IURLGenerator $urlGenerator
    ) {

    }//end __construct()


    public function getId(): string
    {
        return 'docudesk-anonymization';

    }//end getId()


    public function getTitle(): string
    {
        return 'Document Anonymization';

    }//end getTitle()


    public function getOrder(): int
    {
        return 20;

    }//end getOrder()


    public function getIconClass(): string
    {
        return 'icon-docudesk';

    }//end getIconClass()


    public function getIconUrl(): string
    {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
        );

    }//end getIconUrl()


    public function getUrl(): ?string
    {
        return $this->urlGenerator->linkToRouteAbsolute('docudesk.dashboard.page');

    }//end getUrl()


    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function load(): void
    {
        Util::addScript(Application::APP_ID, 'docudesk-dashboard');

    }//end load()


}//end class

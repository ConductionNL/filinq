<?php
/**
 * File Entities Dashboard Widget
 *
 * Nextcloud Dashboard widget showing a table of processed files
 * with entity counts and anonymization status.
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
 * Dashboard widget for file entities overview
 *
 * @category Dashboard
 * @package  OCA\DocuDesk\Dashboard
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileEntitiesWidget implements IWidget, IIconWidget
{


    /**
     * Constructor for FileEntitiesWidget
     *
     * @param IURLGenerator $urlGenerator URL generator for building URLs
     *
     * @return void
     */
    public function __construct(
        private readonly IURLGenerator $urlGenerator
    ) {

    }//end __construct()


    /**
     * Get the unique widget identifier
     *
     * @return string The widget ID
     */
    public function getId(): string
    {
        return 'docudesk-file-entities';

    }//end getId()


    /**
     * Get the widget display title
     *
     * @return string The widget title
     */
    public function getTitle(): string
    {
        return 'File Entities';

    }//end getTitle()


    /**
     * Get the widget display order
     *
     * @return int The widget order
     */
    public function getOrder(): int
    {
        return 21;

    }//end getOrder()


    /**
     * Get the widget icon CSS class
     *
     * @return string The icon CSS class
     */
    public function getIconClass(): string
    {
        return 'icon-docudesk';

    }//end getIconClass()


    /**
     * Get the widget icon URL
     *
     * @return string The absolute URL to the widget icon
     */
    public function getIconUrl(): string
    {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
        );

    }//end getIconUrl()


    /**
     * Get the widget URL
     *
     * @return string|null The URL the widget links to
     */
    public function getUrl(): ?string
    {
        return $this->urlGenerator->linkToRouteAbsolute('docudesk.dashboard.page');

    }//end getUrl()


    /**
     * Load the widget scripts
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function load(): void
    {
        Util::addScript(Application::APP_ID, 'docudesk-dashboard');

    }//end load()


}//end class

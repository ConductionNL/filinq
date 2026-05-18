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
     * @param IURLGenerator $urlGenerator The URL generator service
     */
    public function __construct(
        private readonly IURLGenerator $urlGenerator
    ) {

    }//end __construct()


    /**
     * Returns the unique widget identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return 'docudesk-file-entities';

    }//end getId()


    /**
     * Returns the widget display title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'File Entities';

    }//end getTitle()


    /**
     * Returns the widget display order
     *
     * @return int
     */
    public function getOrder(): int
    {
        return 21;

    }//end getOrder()


    /**
     * Returns the CSS icon class for the widget
     *
     * @return string
     */
    public function getIconClass(): string
    {
        return 'icon-docudesk';

    }//end getIconClass()


    /**
     * Returns the URL to the widget icon
     *
     * @return string
     */
    public function getIconUrl(): string
    {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
        );

    }//end getIconUrl()


    /**
     * Returns the URL the widget links to
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->urlGenerator->linkToRouteAbsolute('docudesk.dashboard.page');

    }//end getUrl()


    /**
     * Loads the widget scripts and styles
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function load(): void
    {
        // Shared vendor chunks emitted by webpack splitChunks (see webpack.config.js).
        Util::addScript(Application::APP_ID, Application::APP_ID.'-shared-vendor');
        Util::addScript(Application::APP_ID, Application::APP_ID.'-shared-nc-vue');
        Util::addScript(Application::APP_ID, 'docudesk-dashboard');

    }//end load()


}//end class

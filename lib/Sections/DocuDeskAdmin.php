<?php

/**
 * Admin section for DocuDesk settings
 *
 * @category  Sections
 * @package   OCA\DocuDesk\Sections
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Sections;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Admin section for DocuDesk settings
 *
 * This class defines the admin section where DocuDesk settings will appear
 * in the Nextcloud admin panel.
 *
 * @category Sections
 * @package  OCA\DocuDesk\Sections
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/docudesk
 */
class DocuDeskAdmin implements IIconSection
{

    /**
     * L10N service for translations
     *
     * @var IL10N
     */
    private IL10N $l;

    /**
     * URL generator for creating URLs
     *
     * @var IURLGenerator
     */
    private IURLGenerator $urlGenerator;


    /**
     * Constructor for DocuDeskAdmin section
     *
     * @param IL10N         $l            L10N service for translations
     * @param IURLGenerator $urlGenerator URL generator service
     *
     * @return void
     */
    public function __construct(IL10N $l, IURLGenerator $urlGenerator)
    {
        $this->l            = $l;
        $this->urlGenerator = $urlGenerator;

    }//end __construct()


    /**
     * Get the icon for the admin section
     *
     * @return string URL to the section icon
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    public function getIcon(): string
    {
        return $this->urlGenerator->imagePath('core', 'actions/settings-dark.svg');

    }//end getIcon()


    /**
     * Get the ID of the admin section
     *
     * @return string The section ID
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    public function getID(): string
    {
        return 'docudesk';

    }//end getID()


    /**
     * Get the name of the admin section
     *
     * @return string The translated section name
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    public function getName(): string
    {
        return $this->l->t('DocuDesk');

    }//end getName()


    /**
     * Get the priority of the admin section
     *
     * @return int The section priority (0-100)
     *
     * @psalm-return   int
     * @phpstan-return int
     */
    public function getPriority(): int
    {
        return 97;

    }//end getPriority()


}//end class

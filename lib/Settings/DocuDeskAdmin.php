<?php

/**
 * Admin settings for DocuDesk
 *
 * @category  Settings
 * @package   OCA\DocuDesk\Settings
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * DocuDesk is free software: you can redistribute it and/or modify
 * it under the terms of the European Union Public License (EUPL),
 * version 1.2 only (the "Licence"), appearing in the file LICENSE
 * included in the packaging of this file.
 *
 * DocuDesk is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * European Union Public License for more details.
 *
 * You should have received a copy of the European Union Public License
 * along with DocuDesk. If not, see <https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12>.
 */

namespace OCA\DocuDesk\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;

/**
 * Admin settings for DocuDesk
 *
 * This class handles the admin settings page for DocuDesk.
 *
 * @category Settings
 * @package  OCA\DocuDesk\Settings
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/docudesk
 */
class DocuDeskAdmin implements ISettings
{


    /**
     * L10N service for translations
     *
     * @var IL10N $l
     */
    private IL10N $localization;


    /**
     * Constructor for DocuDeskAdmin
     *
     * @param IL10N $localization L10N service for translations
     *
     * @return void
     */
    public function __construct(IL10N $localization)
    {
        $this->localization = $localization;

    }//end __construct()


    /**
     * Get the admin settings form
     *
     * @return TemplateResponse The template response for the admin settings
     *
     * @psalm-return   TemplateResponse
     * @phpstan-return TemplateResponse
     */
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse('docudesk', 'settings/admin', [], '');

    }//end getForm()


    /**
     * Get the section ID for the admin settings
     *
     * @return string The section ID
     *
     * @psalm-return   string
     * @phpstan-return string
     */
    public function getSection(): string
    {
        return 'docudesk';

    }//end getSection()


    /**
     * Get the priority for the admin settings
     *
     * @return int The priority (0-100)
     *
     * @psalm-return   int
     * @phpstan-return int
     */
    public function getPriority(): int
    {
        return 10;

    }//end getPriority()


}//end class

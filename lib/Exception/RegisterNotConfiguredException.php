<?php

/**
 * Register Not Configured Exception
 *
 * Raised when an OpenRegister register/schema pair required by a
 * docudesk service is not yet configured via IAppConfig. Lets callers
 * distinguish missing-configuration (UI should render a calm
 * "not configured yet" empty state) from real errors (which deserve
 * a 5xx and a log).
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;

/**
 * Typed exception for missing register/schema configuration
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RegisterNotConfiguredException extends RuntimeException
{

}//end class

<?php

/**
 * An email that could not be filed
 *
 * Carries a stable reason code for the failed record. The message never
 * holds any of the email's content.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use RuntimeException;

/**
 * Thrown with a reason code: the email stays in the inbox with a failed record.
 */
class EmailNotFiled extends RuntimeException {
}//end class

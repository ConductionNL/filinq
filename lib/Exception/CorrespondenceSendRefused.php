<?php

/**
 * A letter that cannot be sent by email
 *
 * Thrown by CorrespondenceSendService with the HTTP status the controller
 * answers: 400 for a request that names no recipient, 404 for a recipient
 * the caller cannot read, 422 for a recipient without an email address or an
 * instance without a mail server. Nothing is composed or sent when it is
 * thrown.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/letter-email-sending/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refused send, carrying its HTTP status as the code.
 *
 * @spec openspec/changes/letter-email-sending/tasks.md#task-2
 */
class CorrespondenceSendRefused extends RuntimeException {
}//end class

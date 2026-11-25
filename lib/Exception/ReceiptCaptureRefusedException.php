<?php

/**
 * Receipt Capture Refused Exception
 *
 * A receipt photo the capture route does not take: too large, or of a type
 * or format it cannot read. The code is the HTTP status the route answers.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refused receipt photo, carrying the HTTP status to answer with.
 *
 * @spec openspec/changes/archive/2026-11-25-extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 */
class ReceiptCaptureRefusedException extends RuntimeException {
}//end class

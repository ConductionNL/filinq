<?php

/**
 * Scan Split Failed Exception
 *
 * Raised while a scanned batch is cut into documents when one step (reading
 * the pages, finding anything to deliver, writing a segment) fails. The
 * message is the reason the batch records.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A failed step of a batch split, carrying the reason the batch records.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class ScanSplitFailedException extends RuntimeException {
}//end class

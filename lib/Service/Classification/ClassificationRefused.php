<?php

/**
 * A classification decision that cannot be taken
 *
 * Thrown by ClassificationDecisionService with the HTTP status the
 * controller answers: 404 for a file the reviewer cannot reach or that has
 * no suggestion, 409 for a suggestion already decided, 422 for an unknown
 * document type.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Classification;

use RuntimeException;

/**
 * A refused confirm or reject, carrying its HTTP status as the code.
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
 */
class ClassificationRefused extends RuntimeException {
}//end class

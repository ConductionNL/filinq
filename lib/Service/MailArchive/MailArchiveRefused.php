<?php

/**
 * A mail archive that cannot be split
 *
 * The message is shown to the archivist as the import's failure reason, so it
 * never carries the content of a message.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use RuntimeException;

/**
 * Thrown when an archive's format is unknown or its converter is missing.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-the-container-format-is-detected-and-split-on-the-server-req-mai-002
 */
class MailArchiveRefused extends RuntimeException {
}//end class

<?php

/**
 * The merge job store could not be read, so nobody is told the job is gone.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A read of the merge job store that failed, rather than one that found nothing.
 *
 * 🔴 THIS EXCEPTION EXISTS BECAUSE THE TWO ANSWERS LOOKED THE SAME. The store
 * swallowed a read failure into `null`, and `null` means "there is no merge
 * with that id". So a register that was down, or a schema that did not
 * resolve, reached the polling caller as a 404: the person watching a queued
 * merge was told their merge did not exist, while it sat in the queue. The
 * client then stops polling, because a merge that is gone is not coming back.
 *
 * 🔑 IT IS NOT THROWN FOR A GENUINE ABSENCE. An id nobody ever queued still
 * reads as `null`, and still answers 404, because that is a true answer.
 *
 * The queue read raises for the same reason from the other side: an empty
 * queue and an unreadable queue both made MergeDocumentsJob report a clean
 * run over a queue it never saw.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */
class MergeJobStoreUnreadableException extends RuntimeException {

}//end class

<?php

/**
 * The download agreements could not be read, so nothing is served.
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
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A read of the agreement store that failed, rather than one that found nothing.
 *
 * 🔴 THIS EXCEPTION EXISTS BECAUSE THE TWO ANSWERS LOOKED THE SAME. The store
 * used to swallow a read failure into an empty list, and an empty list means
 * "no agreement was declared", which means "this file is not gated". So a
 * register that was down, or a schema that did not resolve, read as permission
 * to download. Raising instead of returning keeps the failure a failure, and
 * the gate turns it into a refusal.
 *
 * 🔑 IT IS NOT THROWN WHEN THE SCHEMA IS SIMPLY ABSENT. An instance that never
 * imported `downloadAgreement` gates nothing, which is a real answer rather
 * than a failed read, and refusing every download there would take the whole
 * download surface down over a feature nobody enabled.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */
class AgreementStoreUnreadableException extends RuntimeException {

}//end class

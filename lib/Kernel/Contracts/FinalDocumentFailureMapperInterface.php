<?php

/**
 * Contract of FinalDocumentFailureMapper
 *
 * The public surface of the FinalDocumentFailureMapper service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Throwable;

/**
 * Contract of FinalDocumentFailureMapper.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FinalDocumentFailureMapperInterface {
	/**
	 * Shape one failure.
	 *
	 * The caller asked for something the document's state does not allow, so a
	 * refusal is a 409 and the sentence it carries is the one to show. A
	 * service refusal with a message of its own is a 400, a missing document a
	 * 404, and anything else is logged and answered with the caller's fallback.
	 *
	 * @param Throwable $exception The failure.
	 * @param string $fallback The localised message for an unrecognised failure.
	 *
	 * @return array{status: int, body: array<string, mixed>} The status code and the body.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function shape(Throwable $exception, string $fallback): array;
}//end interface

<?php

/**
 * A generation refused over its plain-language counterpart.
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
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A plain-language rendition that may not be produced or may not leave.
 *
 * 🔴 THE REFUSAL NAMES WHAT IS MISSING, NOT THAT SOMETHING IS. "The generation
 * was refused" sends the handler looking through a template for a hole they
 * cannot see; "the bezwaartermijn could not be resolved" tells them where to
 * look. REQ-DIO-04's fourth scenario asks for the unresolved statement BY NAME.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-in-and-out-of-the-building/specs/letter-correspondence-generation/spec.md
 */
class PlainRenditionRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string             $message    Why it was refused, naming what is missing.
	 * @param array<int, string> $unresolved The statements that could not be resolved.
	 * @param Throwable|null     $previous   The underlying failure, when there was one.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly array $unresolved = [],
		?Throwable $previous = null,
	) {
		// CODE 400, deliberately, as UploadRefusedException does: the
		// controllers map a 4xx exception code straight onto the HTTP status, so
		// a refusal carrying 0 would answer 500 and read as a broken server
		// rather than as a letter that is not ready to leave.
		parent::__construct($message, 400, $previous);

	}//end __construct()

	/**
	 * The statements that could not be resolved.
	 *
	 * @return array<int, string> The statement keys.
	 */
	public function getUnresolved(): array {
		return $this->unresolved;

	}//end getUnresolved()
}//end class

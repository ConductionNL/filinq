<?php

/**
 * Upload Refused Exception
 *
 * Raised when an upload does not obey the administered policy: an extension the
 * policy does not allow, a media type the bytes turn out to be, a file over the
 * maximum, or a type that could not be read at all while the policy refuses
 * those.
 *
 * The message names what was DETECTED, not what the file was called. A .exe
 * renamed to .pdf is the case this check exists for, and a refusal that quoted
 * the filename back would say nothing about why.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * An upload the policy refuses.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/case-documents-and-the-flat-list/specs/document-register/spec.md
 */
class UploadRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message Why the upload was refused, naming what was detected.
	 * @param string $detectedType The media type read from the bytes, when one was read.
	 * @param string $policy The policy that refused it.
	 * @param Throwable|null $previous The underlying failure, when there was one.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly string $detectedType = '',
		private readonly string $policy = '',
		?Throwable $previous = null,
	) {
		// CODE 400, deliberately. The controllers in this app map an exception
		// code in the 4xx range straight onto the HTTP status, so a refusal
		// that carried 0 would answer 500 and read as a broken server rather
		// than as a file the policy does not allow.
		parent::__construct(message: $message, code: 400, previous: $previous);

	}//end __construct()

	/**
	 * The media type read from the file's bytes.
	 *
	 * @return string The detected type, or an empty string when none was read.
	 */
	public function getDetectedType(): string {
		return $this->detectedType;

	}//end getDetectedType()

	/**
	 * The policy that refused the upload.
	 *
	 * @return string The policy name.
	 */
	public function getPolicy(): string {
		return $this->policy;

	}//end getPolicy()
}//end class

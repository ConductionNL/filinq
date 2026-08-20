<?php

/**
 * Comparison Exception
 *
 * Typed exception carrying an HTTP status code and a machine-readable reason
 * for the document-comparison flow, so the controller can translate service
 * failures into the correct HTTP response without leaking detail.
 *
 * @category  Exception
 * @package   OCA\DocuDesk\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Exception;

use RuntimeException;

/**
 * Exception for the document-comparison flow.
 *
 * @category Exception
 * @package  OCA\DocuDesk\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ComparisonException extends RuntimeException {

	/**
	 * HTTP status code for this failure.
	 *
	 * @var integer
	 */
	private int $statusCode;

	/**
	 * Machine-readable reason code.
	 *
	 * @var string
	 */
	private string $reason;

	/**
	 * Constructor.
	 *
	 * @param int $statusCode HTTP status code.
	 * @param string $reason Machine-readable reason code.
	 * @param string $message Human-readable message.
	 *
	 * @return void
	 */
	public function __construct(int $statusCode, string $reason, string $message = '') {
		parent::__construct(message: $message);
		$this->statusCode = $statusCode;
		$this->reason = $reason;

	}//end __construct()

	/**
	 * Get the HTTP status code.
	 *
	 * @return int The status code.
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}//end getStatusCode()

	/**
	 * Get the machine-readable reason code.
	 *
	 * @return string The reason code.
	 */
	public function getReason(): string {
		return $this->reason;
	}//end getReason()
}//end class

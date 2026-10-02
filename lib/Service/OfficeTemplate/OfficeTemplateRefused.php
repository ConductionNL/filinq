<?php

/**
 * Office template refusal
 *
 * A refused office upload or import: the HTTP status is the code (422 by
 * default), the reason a short machine word (macro, size, mime, extension,
 * corrupt, unknown-tags, conversion) and the details whatever the caller
 * needs to fix it, such as the unknown tags.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use RuntimeException;

/**
 * A refused office upload, carrying its HTTP status as the code.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 */
class OfficeTemplateRefused extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string               $message The sentence the caller shows.
	 * @param string               $reason  The machine reason.
	 * @param array<string, mixed> $details What the caller needs to fix it.
	 * @param int                  $code    The HTTP status.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly string $reason,
		private readonly array $details = [],
		int $code = 422,
	) {
		parent::__construct($message, $code);

	}//end __construct()

	/**
	 * The machine reason.
	 *
	 * @return string The reason.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()

	/**
	 * The body of the refusal response.
	 *
	 * @return array<string, mixed> error, reason and the details.
	 */
	public function toResponse(): array {
		return ['error' => $this->getMessage(), 'reason' => $this->reason] + $this->details;

	}//end toResponse()
}//end class

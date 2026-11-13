<?php

/**
 * Office Source Refused Exception
 *
 * Raised when an uploaded office template is refused: macro-enabled, over the
 * size cap, not the kind of file its extension says, or carrying merge tags
 * the bound schema does not know while unknown tags are set to block.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * An office template upload that is refused, answered with HTTP 422.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeSourceRefusedException extends RuntimeException implements ErrorDetailsInterface {

	/**
	 * The file is macro-enabled.
	 */
	public const REASON_MACRO = 'macro';

	/**
	 * The file is over the size cap.
	 */
	public const REASON_SIZE = 'size';

	/**
	 * The file is not the kind its extension says, or not an office file at all.
	 */
	public const REASON_TYPE = 'type';

	/**
	 * The file has merge tags the bound schema does not know.
	 */
	public const REASON_UNKNOWN_TAGS = 'unknownTags';

	/**
	 * Constructor.
	 *
	 * @param string             $message     Why the file was refused.
	 * @param string             $reason      One of the REASON_* constants.
	 * @param array<int, string> $unknownTags The unknown merge tags, for REASON_UNKNOWN_TAGS.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		string $message,
		private readonly string $reason,
		private readonly array $unknownTags = [],
	) {
		parent::__construct(message: $message, code: 422);
	}//end __construct()

	/**
	 * Why the file was refused.
	 *
	 * @return string One of the REASON_* constants.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function getReason(): string {
		return $this->reason;
	}//end getReason()

	/**
	 * The reason and, when tags were refused, which ones.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function getErrorDetails(): array {
		$details = ['reason' => $this->reason];
		if ($this->unknownTags !== []) {
			$details['unknownTags'] = $this->unknownTags;
		}

		return $details;
	}//end getErrorDetails()
}//end class

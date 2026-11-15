<?php

/**
 * AI Prompt
 *
 * A prompt for a local AI text task and the user the task runs for.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Extraction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

/**
 * A prompt and the user its AI task is attributed to (null: no user).
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
final class AiPrompt {

	/**
	 * @param string $text The prompt.
	 * @param string $requestedBy Nextcloud user id, or '' to run without a user.
	 */
	public function __construct(
		public readonly string $text,
		private readonly string $requestedBy,
	) {
	}//end __construct()

	/**
	 * The user the task is attributed to, or null when there is none.
	 *
	 * @return string|null
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function userId(): ?string {
		if ($this->requestedBy === '') {
			return null;
		}

		return $this->requestedBy;
	}//end userId()
}//end class

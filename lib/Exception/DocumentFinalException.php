<?php

/**
 * Document Final Exception
 *
 * Typed exception raised when a write is refused because the document version
 * it would change is final. It carries the sentence the caller shows the user
 * plus the facts that sentence is built from, so an API client, an editor and
 * a background job all report the same refusal without re-deriving it.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * Exception carrying the refusal of a write to a final document version.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class DocumentFinalException extends RuntimeException {

	/**
	 * Facts about the final version, for a caller that renders its own message.
	 *
	 * Keys: `uuid`, `fileId`, `status`, `finalisedBy`, `finalisedByName`,
	 * `finalisedAt`, `finalReason`, `unfrozen`.
	 *
	 * @var array<string, mixed>
	 */
	private array $version;

	/**
	 * Constructor.
	 *
	 * @param string $message The refusal sentence, naming the version, its state, who made it final and when.
	 * @param array<string, mixed> $version The facts about the final version.
	 *
	 * @return void
	 */
	public function __construct(string $message, array $version = []) {
		parent::__construct(message: $message);
		$this->version = $version;

	}//end __construct()

	/**
	 * The facts about the final version that refused the write.
	 *
	 * @return array<string, mixed> The version facts.
	 */
	public function getVersion(): array {
		return $this->version;

	}//end getVersion()
}//end class

<?php

/**
 * Anonymize Run
 *
 * The file, entities, overrides and run options of one anonymisation.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

/**
 * What one anonymisation runs on: the file, the entities, the acknowledged overrides and the runner's options.
 *
 * The acting user is the `userId` run option.
 *
 * @spec openspec/specs/anonymization/spec.md
 */
class AnonymizeRun {

	/**
	 * @param int                              $fileId    The Nextcloud file ID
	 * @param array<array<string, mixed>>      $entities  The entities to anonymize
	 * @param array<int, array<string, mixed>> $overrides Acknowledged override entries {ruleId, entityId, reason?}
	 * @param array<string, mixed>             $options   Run options forwarded to the runner (appendBasisSummary,
	 *                                                    outputFormat, unredactedEntities, scope, dossierKey,
	 *                                                    reversible, userId)
	 */
	public function __construct(
		public readonly int $fileId,
		public readonly array $entities,
		public readonly array $overrides,
		public readonly array $options,
	) {
	}//end __construct()

	/**
	 * The acting user's UID.
	 *
	 * @return string The UID, '' when none.
	 *
	 * @spec openspec/specs/anonymization/spec.md
	 */
	public function userId(): string {
		return (string)($this->options['userId'] ?? '');
	}//end userId()
}//end class

<?php

/**
 * EML Anonymize Request
 *
 * What one EML anonymisation is asked to redact, and where.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The source EML, the entities to redact, and the placeholder-numbering
 * scope (`scope` and `dossierKey` from the run options).
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class EmlAnonymizeRequest {

	/**
	 * @param int                             $fileId         Source Nextcloud file ID.
	 * @param mixed                           $node           Source EML file node.
	 * @param array<int, array<string,mixed>> $mappedEntities Entities to redact.
	 * @param array<string, mixed>            $options        Run options carrying `scope` and `dossierKey`.
	 */
	public function __construct(
		public readonly int $fileId,
		public readonly mixed $node,
		public readonly array $mappedEntities,
		private readonly array $options,
	) {
	}//end __construct()

	/**
	 * The placeholder-numbering scope.
	 *
	 * @return string The scope.
	 */
	public function scope(): string {
		return $this->options['scope'];
	}//end scope()

	/**
	 * The stable dossier folder id, or null.
	 *
	 * @return string|null The dossier key.
	 */
	public function dossierKey(): ?string {
		return $this->options['dossierKey'];
	}//end dossierKey()
}//end class

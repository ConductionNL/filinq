<?php

/**
 * Consent request
 *
 * One entity detected in one document, for which a consent record is asked.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The document, the detected entity and where its consent record lives.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */
final class ConsentRequest {

	/**
	 * Constructor.
	 *
	 * @param string               $documentId The document UUID
	 * @param string               $entityType The entity type
	 * @param string               $entityText The detected entity text
	 * @param string               $register   The register ID of the consent records
	 * @param string               $schema     The schema ID of the consent records
	 * @param array<string, mixed> $extra      Additional consent data: entityKey, publicationBases, contactEmail, contactAddress
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $documentId,
		public readonly string $entityType,
		public readonly string $entityText,
		public readonly string $register,
		public readonly string $schema,
		public readonly array $extra = [],
	) {

	}//end __construct()
}//end class

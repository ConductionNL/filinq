<?php

/**
 * Consent status change
 *
 * A proposed update of a consent record and the user who makes it.
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
 * The proposed fields of a consent update and its actor.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */
final class ConsentStatusChange {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data  The proposed update, with `evidence` for a status move that needs it
	 * @param string               $actor The user making the change, recorded on each status event
	 *
	 * @return void
	 */
	public function __construct(
		public readonly array $data,
		public readonly string $actor = '',
	) {

	}//end __construct()
}//end class

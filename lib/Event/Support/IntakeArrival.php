<?php

/**
 * Intake arrival
 *
 * How an intake document arrived: the channel's own reference, the arrival
 * time and the message it came with as an attachment.
 *
 * @category  Event
 * @package   OCA\Filinq\Event\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event\Support;

/**
 * How an intake document arrived: the channel's own reference, the arrival
 * time and the message it came with as an attachment.
 *
 * @category Event
 * @package  OCA\Filinq\Event\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
final class IntakeArrival {

	/**
	 * Constructor.
	 *
	 * @param string      $sourceRef   The channel's own reference for this delivery
	 * @param string|null $receivedAt  When it arrived, ISO 8601, or null for now
	 * @param string      $arrivedWith The intake document of the message this file came as an attachment of
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function __construct(
		public readonly string $sourceRef = '',
		public readonly ?string $receivedAt = null,
		public readonly string $arrivedWith = '',
	) {

	}//end __construct()
}//end class

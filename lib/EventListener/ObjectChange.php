<?php

/**
 * Object Change
 *
 * What an OpenRegister object event changed.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
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

namespace OCA\Filinq\EventListener;

/**
 * The object payload after the change, the payload before it, and the kind of change.
 *
 * @spec openspec/specs/metadata-enrichment/spec.md
 */
class ObjectChange {

	/**
	 * @param array<string, mixed> $objectData    The object payload after the change
	 * @param string               $reason        'created' | 'updated' | 'deleted'
	 * @param array<string, mixed> $oldObjectData The payload before the change, empty when the event has none
	 */
	public function __construct(
		public readonly array $objectData,
		public readonly string $reason,
		public readonly array $oldObjectData = [],
	) {
	}//end __construct()
}//end class

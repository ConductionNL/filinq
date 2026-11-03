<?php

/**
 * Request origin
 *
 * Who asked for the work an event reports on: the requesting app and user.
 *
 * @category  Event
 * @package   OCA\Filinq\Event\Support
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

namespace OCA\Filinq\Event\Support;

/**
 * Who asked for the work an event reports on: the requesting app and user.
 *
 * @category Event
 * @package  OCA\Filinq\Event\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/financial-document-field-extraction/spec.md
 */
final class RequestOrigin {

	/**
	 * Constructor.
	 *
	 * @param string $sourceApp   App id of the requester (e.g. 'shillinq')
	 * @param string $requestedBy Nextcloud user id that asked for the work
	 *
	 * @return void
	 *
	 * @spec openspec/specs/financial-document-field-extraction/spec.md
	 */
	public function __construct(
		public readonly string $sourceApp,
		public readonly string $requestedBy,
	) {

	}//end __construct()
}//end class

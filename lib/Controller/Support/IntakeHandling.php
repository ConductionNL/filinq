<?php

/**
 * Intake handling
 *
 * Handling an intake document: the inbox, detaching documents and routing them.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
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

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Kernel\Contracts\IntakeDetachmentServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRoutingServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;

/**
 * Handling an intake document: the inbox, detaching documents and routing them.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
final class IntakeHandling {

	/**
	 * Constructor.
	 *
	 * @param IntakeServiceInterface           $intake     The intake inbox
	 * @param IntakeDetachmentServiceInterface $detachment Detaches documents from a message
	 * @param IntakeRoutingServiceInterface    $routing    Routes a document
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function __construct(
		public readonly IntakeServiceInterface $intake,
		public readonly IntakeDetachmentServiceInterface $detachment,
		public readonly IntakeRoutingServiceInterface $routing,
	) {

	}//end __construct()
}//end class

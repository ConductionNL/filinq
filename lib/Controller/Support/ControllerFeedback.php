<?php

/**
 * Controller feedback
 *
 * The translator for the messages a controller answers with, and the logger for what went wrong.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * The translator for the messages a controller answers with, and the logger for what went wrong.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
final class ControllerFeedback {

	/**
	 * Constructor.
	 *
	 * @param IL10N           $l10n   Translations for the answers
	 * @param LoggerInterface $logger Records failures
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function __construct(
		public readonly IL10N $l10n,
		public readonly LoggerInterface $logger,
	) {

	}//end __construct()
}//end class

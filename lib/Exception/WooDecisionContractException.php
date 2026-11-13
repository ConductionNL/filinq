<?php

/**
 * Woo Decision Contract Exception
 *
 * A Woo decision the generator was asked to render does not meet the
 * `woo-decision` data contract.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
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

namespace OCA\Filinq\Exception;

use UnexpectedValueException;

/**
 * Carries every problem found, not only the first.
 *
 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-the-woo-decision-data-contract-is-checked-before-anything-is-rendered-req-ddwrw-010
 */
final class WooDecisionContractException extends UnexpectedValueException {

	/**
	 * Constructor.
	 *
	 * @param array<int, string> $problems Every problem, each a sentence.
	 */
	public function __construct(
		public readonly array $problems,
	) {
		parent::__construct(
			message: 'The Woo decision cannot be rendered: ' . implode(' ', $problems),
			code: 400
		);
	}//end __construct()
}//end class

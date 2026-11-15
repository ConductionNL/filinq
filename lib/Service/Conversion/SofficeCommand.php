<?php

/**
 * Soffice Command
 *
 * How soffice is started for one export: the binary, the timeout and the input filter.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

/**
 * The soffice binary, the timeout in seconds and the extra arguments that
 * choose how soffice opens the source.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-conversion/spec.md
 */
final class SofficeCommand {

	/**
	 * Constructor.
	 *
	 * @param string             $binary      Path to the soffice binary.
	 * @param int                $timeout     Timeout in seconds.
	 * @param array<int, string> $inputFilter Extra arguments that choose how soffice opens the source.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function __construct(
		public readonly string $binary,
		public readonly int $timeout,
		public readonly array $inputFilter = [],
	) {

	}//end __construct()
}//end class

<?php

/**
 * Anonymise call
 *
 * One validated anonymise request: the file, the raw request parameters,
 * the normalised body and the resolved output format.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel
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

namespace OCA\Filinq\Kernel;

/**
 * The file, parameters, body and output format of one anonymise request.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/anonymization/spec.md
 */
final class AnonymizeCall {

	/**
	 * Constructor.
	 *
	 * @param int                  $fileId       The Nextcloud file ID
	 * @param array<string, mixed> $params       Raw request parameters (scope, dossierKey, filters)
	 * @param array<string, mixed> $request      The normalised body from validateBody()
	 * @param string               $outputFormat The resolved output format
	 *
	 * @return void
	 */
	public function __construct(
		public readonly int $fileId,
		public readonly array $params,
		public readonly array $request,
		public readonly string $outputFormat,
	) {

	}//end __construct()
}//end class

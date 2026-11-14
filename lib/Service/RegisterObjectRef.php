<?php

/**
 * Register object reference
 *
 * Where one OpenRegister object lives: its id, its register and its schema.
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
 * The id, register and schema of one stored object.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 */
final class RegisterObjectRef {

	/**
	 * Constructor.
	 *
	 * @param string $id       The object UUID
	 * @param string $register The register id or slug
	 * @param string $schema   The schema id or slug
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $register,
		public readonly string $schema,
	) {

	}//end __construct()
}//end class

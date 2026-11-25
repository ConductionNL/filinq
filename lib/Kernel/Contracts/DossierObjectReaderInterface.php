<?php

/**
 * Contract of DossierObjectReader
 *
 * The public surface of the DossierObjectReader service that callers in other
 * components use, so they depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;


/**
 * Contract of DossierObjectReader.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DossierObjectReaderInterface {
	/**
	 * The object's payload as an array.
	 *
	 * @param object $object The OpenRegister object.
	 *
	 * @return array<string, mixed> The payload.
	 *
	 * @spec openspec/changes/archive/2026-11-25-dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function payloadOf(object $object): array;

	/**
	 * Every object of a schema in filinq's register.
	 *
	 * @param object $objectService OpenRegister's object service.
	 * @param string $schema The schema slug.
	 *
	 * @return array<int, object> The objects.
	 *
	 * @spec openspec/changes/archive/2026-11-25-dossier-management-ui/specs/dossier-management-ui/spec.md
	 */
	public function findAllOf(object $objectService, string $schema): array;
}//end interface

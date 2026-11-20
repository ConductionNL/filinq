<?php

/**
 * MDTO Sidecar Input
 *
 * What one MDTO sidecar is built from.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * A record (its OpenRegister `retention` block included), its supplement,
 * the facts of its files, a dossier's members, and the organisation that
 * OpenRegister names archiefvormer.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */
final class MdtoSidecarInput {

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed>             $record           The record (`uuid`, `title`, `retention`).
	 * @param array<string, mixed>             $supplement       Its mdtoSupplement.
	 * @param string                           $organisationId   OpenRegister's `organisation_identifier`.
	 * @param string                           $organisationName OpenRegister's `organisation_name`.
	 * @param array<int, array<string, mixed>> $files            File facts (name, format, size, checksum, checksumDate).
	 * @param array<int, array<string, mixed>> $members          A dossier's member documents (`uuid`, `title`).
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function __construct(
		public readonly array $record,
		public readonly array $supplement,
		public readonly string $organisationId,
		public readonly string $organisationName='OpenRegister',
		public readonly array $files=[],
		public readonly array $members=[],
	) {

	}//end __construct()

	/**
	 * The record's UUID.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function uuid(): string {
		return (string) ($this->record['uuid'] ?? '');

	}//end uuid()

	/**
	 * The record's name: its title, else its naam.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function name(): string {
		return trim((string) ($this->record['title'] ?? ($this->record['naam'] ?? '')));

	}//end name()

	/**
	 * The record's OpenRegister retention block, read only.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-mdto-core-attributes-have-a-single-source-of-truth-req-ddtmm-001
	 */
	public function retention(): array {
		return (array) ($this->record['retention'] ?? []);

	}//end retention()
}//end class

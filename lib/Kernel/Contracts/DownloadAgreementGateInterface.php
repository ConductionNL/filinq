<?php

/**
 * Contract of DownloadAgreementGate
 *
 * The public surface of the DownloadAgreementGate service, so callers in other
 * components depend on this contract instead of the implementation.
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
 * Contract of DownloadAgreementGate.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface DownloadAgreementGateInterface {
	/**
	 * Nobody has accepted anything for this file.
	 *
	 * @var string
	 */
	public const NOT_ACCEPTED = 'not_accepted';

	/**
	 * An older version was accepted, and the terms have changed since.
	 *
	 * @var string
	 */
	public const VERSION_MOVED_ON = 'version_moved_on';

	/**
	 * The acceptance could not be written, so nothing is served.
	 *
	 * @var string
	 */
	public const NOT_RECORDED = 'not_recorded';

	/**
	 * The conditions could not be read, so it is not known whether any apply.
	 *
	 * @var string
	 */
	public const NOT_KNOWN = 'not_known';

	/**
	 * Whether this person may have this file yet.
	 *
	 * @param string $document The document being downloaded.
	 * @param string $person   Who is asking.
	 *
	 * @return array<string, mixed> `mayDownload`, and the agreement to show when not.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function check(string $document, string $person): array;

	/**
	 * Record an acceptance, and say whether the file may now be served.
	 *
	 * @param string $document The document.
	 * @param string $person   Who accepted.
	 * @param string $version  Which version they were shown.
	 *
	 * @return array<string, mixed> `mayDownload`, and why not when it is false.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function accept(string $document, string $person, string $version): array;
}//end interface

<?php

/**
 * Contract of CaseArchiveService
 *
 * The public surface of the CaseArchiveService service, so callers in other
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

use RuntimeException;

/**
 * Contract of CaseArchiveService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface CaseArchiveServiceInterface {
	/**
	 * The schema holding the jobs.
	 *
	 * @var string
	 */
	public const SCHEMA = 'archiveJob';

	/**
	 * The ceiling used when an instance declared none, in bytes.
	 *
	 * @var int
	 */
	public const DEFAULT_CEILING = 2147483648;

	/**
	 * A file left out because the bundle would go over the ceiling.
	 *
	 * @var string
	 */
	public const REASON_CEILING = 'ceiling';

	/**
	 * A file left out because the person asking may not read it.
	 *
	 * @var string
	 */
	public const REASON_PERMISSION = 'permission';

	/**
	 * A file left out because it could not be read when the archive was written.
	 *
	 * @var string
	 */
	public const REASON_MISSING = 'missing';

	/**
	 * The app config key holding the administered ceiling, in bytes.
	 *
	 * @var string
	 */
	public const CONFIG_CEILING = 'case_archive_ceiling_bytes';

	/**
	 * What this bundle would be, before anybody starts building it.
	 *
	 * The caller shows this to the person asking. Being told afterwards that
	 * eleven documents did not fit is being told too late.
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return array{files: int, bytes: int, ceilingBytes: int, exceedsCeiling: bool} The preflight.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function preflight(array $domain, int $ceiling = 2147483648): array;

	/**
	 * Build the manifest of one bundle: what goes in, and what does not.
	 *
	 * The files are taken in name order and added until the ceiling is reached.
	 * Everything after that is left out with `ceiling` as its reason, so the
	 * manifest says which documents are missing rather than only that some are.
	 *
	 * @param array<string, mixed> $domain The object.
	 * @param int $ceiling The ceiling in force, in bytes.
	 *
	 * @return array{included: array<int, array<string, mixed>>, excluded: array<int, array<string, mixed>>, bytes: int} The manifest.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function manifestFor(array $domain, int $ceiling = 2147483648): array;

	/**
	 * The ceiling in force: the administered one, which a caller may lower but never raise.
	 *
	 * @param int $requested The ceiling a caller asked for, or 0 for none.
	 *
	 * @return int The ceiling in bytes.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function ceiling(int $requested = 0): int;

	/**
	 * Build the bundle of one object: one archive with the files and a manifest, recorded as a job.
	 *
	 * The archive is written to the requester's `Case bundles` folder as a
	 * zip holding every included file and `manifest.json`. A file that
	 * cannot be read at that moment moves to the excluded list with reason
	 * `missing`, so the manifest in the archive and the job agree. When the
	 * job cannot be recorded the archive is deleted again and the call
	 * fails: an unrecorded bundle is never handed over (REQ-DFT-02, #1210).
	 *
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param int $requestedCeiling A lower ceiling the caller asks for, or 0.
	 *
	 * @return array<string, mixed> The manifest, plus `archive` with the new file's id, name and path.
	 *
	 * @throws RuntimeException When the archive cannot be written or the job cannot be recorded.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function build(array $domain, int $requestedCeiling = 0): array;

	/**
	 * Record one bundle as a job, so what was handed over can be checked later.
	 *
	 * @param array<string, mixed> $domain The object.
	 * @param array<string, mixed> $manifest The manifest of what went in and what did not.
	 * @param int $ceiling The ceiling in force.
	 * @param int $fileId The archive's own file id, when one was written.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws RuntimeException When the job cannot be stored.
	 *
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	public function record(array $domain, array $manifest, int $ceiling, int $fileId = 0): array;
}//end interface

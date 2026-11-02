<?php

/**
 * Contract of OutputLayoutResolver
 *
 * The public surface of the OutputLayoutResolver service, so callers in other
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
 * Contract of OutputLayoutResolver.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OutputLayoutResolverInterface {
	/**
	 * App-config key for the configurable subfolder name.
	 */
	public const SUBFOLDER_CONFIG_KEY = 'anonymisation.output_subfolder_name';

	/**
	 * Default subfolder name when the config key is unset or invalid.
	 */
	public const DEFAULT_SUBFOLDER_NAME = 'anonymised';

	/**
	 * Regex that valid subfolder names must match.
	 */
	public const SUBFOLDER_NAME_REGEX = '/^[a-z0-9_-]+$/';

	/**
	 * Resolve the canonical batch-output relative path for a given source.
	 *
	 * Output shape: `<sourceFolderPath>/<subfolder>/<cleanBaseName>.<extension>`.
	 *
	 * @param string $sourceFolderPath Absolute Nextcloud path of the source
	 *                                 folder (must start with `/`).
	 * @param string $sourceBaseName Base name of the source file (no extension).
	 * @param string $extension File extension (without leading dot).
	 *
	 * @return string Canonical destination path.
	 */
	public function resolveBatchDestination(string $sourceFolderPath, string $sourceBaseName, string $extension): string;

	/**
	 * Strip a trailing `_anonymized` suffix from the supplied base name.
	 *
	 * Public so source-discovery filters (`BatchExtractionService`,
	 * `FolderBatchService`, `FolderExtractionJob`) can quickly test whether
	 * a candidate file is itself a prior anonymisation output.
	 *
	 * @param string $baseName Source base name (without extension).
	 *
	 * @return string Base name with every trailing `_anonymized` stripped, if present
	 *                (a re-anonymised legacy output carries two).
	 */
	public function stripLegacyAnonymizedSuffix(string $baseName): string;

	/**
	 * Indicate whether the given base name is itself a prior anonymisation
	 * output (used by the source-discovery filter).
	 *
	 * @param string $baseName Source base name (without extension).
	 *
	 * @return bool True iff the base name ends with the legacy `_anonymized` suffix.
	 */
	public function isLegacyAnonymizedOutput(string $baseName): bool;

	/**
	 * Indicate whether a full file name (with extension) is itself a prior
	 * anonymisation output, i.e. its base name ends with `_anonymized`.
	 *
	 * This is the file-name-oriented discriminator used by the folder-flow
	 * source filter (`FolderExtractionJob`) — it strips the extension first
	 * and then defers to {@see isLegacyAnonymizedOutput()} so there is a
	 * single source of truth for the suffix semantics.
	 *
	 * @param string $fileName Full file name including extension.
	 *
	 * @return bool True iff the base name ends with the legacy `_anonymized` suffix.
	 */
	public function hasAnonymizedSuffix(string $fileName): bool;

	/**
	 * Alias for {@see getSubfolderName()} expressing the "read from config"
	 * intent at the folder-flow call-site (`FolderExtractionJob`).
	 *
	 * @return string A safe, validated subfolder name.
	 */
	public function readSubfolderName(): string;

	/**
	 * Resolve the configured subfolder name with validation and fallback.
	 *
	 * Reads `filinq.anonymisation.output_subfolder_name`; falls back to
	 * `anonymised` and logs a warning when the value does not match the
	 * `/^[a-z0-9_-]+$/` validation regex.
	 *
	 * @return string A safe, validated subfolder name.
	 *
	 * @spec openspec/specs/folder-analysis-anonymization/spec.md#requirement-folder-driven-anonymisation-must-write-outputs-to-the-configured-subfolder
	 */
	public function getSubfolderName(): string;
}//end interface

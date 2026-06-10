<?php

/**
 * OutputLayoutResolver
 *
 * Pure-helper that decides where a batch anonymisation flow should write its
 * redacted output. The resolver implements two contracts from the
 * `anonymisation-batch-output-folder-layout` and
 * `anonymisation-folder-output-folder-layout` openspec changes:
 *
 *   - Batch outputs land in `<source>/<subfolder>/<clean-base>.<ext>`
 *     instead of the legacy `<source>/<base>_anonymized.<ext>` location.
 *   - The configured subfolder name comes from `IAppConfig`
 *     (`docudesk.anonymisation.output_subfolder_name`, default `anonymised`)
 *     and is validated against `/^[a-z0-9_-]+$/`.
 *   - Source-side `_anonymized` suffixes on the base name are stripped so
 *     re-running the batch on its own output does not pile up `_anonymized`
 *     chains (`Report_anonymized` → `Report`).
 *
 * The resolver is intentionally pure: it does NOT touch the filesystem and
 * does NOT move any files. Callers in `BatchAnonymizeService` /
 * `FolderBatchService` / `FolderExtractionJob` are responsible for the move
 * + create-subfolder-if-missing semantics; the resolver only returns the
 * canonical target path so those callers stay testable in isolation.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Computes batch-output destinations for the anonymisation pipeline.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
final class OutputLayoutResolver
{


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
    private const SUBFOLDER_NAME_REGEX = '/^[a-z0-9_-]+$/';

    /**
     * Trailing-`_anonymized` strip pattern; matches the literal suffix on
     * the base name (post-strip of the extension) so `Report_anonymized`
     * becomes `Report` while `_anonymized_summary` is untouched.
     */
    private const LEGACY_SUFFIX_REGEX = '/_anonymized$/';

    /**
     * Constructor.
     *
     * @param IAppConfig      $config App configuration provider.
     * @param LoggerInterface $logger Logger for the invalid-config warning.
     */
    public function __construct(
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Resolve the canonical batch-output relative path for a given source.
     *
     * Output shape: `<sourceFolderPath>/<subfolder>/<cleanBaseName>.<extension>`.
     *
     * @param string $sourceFolderPath Absolute Nextcloud path of the source
     *                                 folder (must start with `/`).
     * @param string $sourceBaseName   Base name of the source file (no extension).
     * @param string $extension        File extension (without leading dot).
     *
     * @return string Canonical destination path.
     */
    public function resolveBatchDestination(
        string $sourceFolderPath,
        string $sourceBaseName,
        string $extension
    ): string {
        $subfolder      = $this->getSubfolderName();
        $cleanBase      = $this->stripLegacyAnonymizedSuffix($sourceBaseName);
        $normalisedExt  = ltrim($extension, '.');

        $folder = rtrim($sourceFolderPath, '/');
        $tail   = $normalisedExt === '' ? $cleanBase : ($cleanBase.'.'.$normalisedExt);

        return $folder.'/'.$subfolder.'/'.$tail;

    }//end resolveBatchDestination()


    /**
     * Strip a trailing `_anonymized` suffix from the supplied base name.
     *
     * Public so source-discovery filters (`BatchExtractionService`,
     * `FolderBatchService`, `FolderExtractionJob`) can quickly test whether
     * a candidate file is itself a prior anonymisation output.
     *
     * @param string $baseName Source base name (without extension).
     *
     * @return string Base name with one trailing `_anonymized` stripped, if present.
     */
    public function stripLegacyAnonymizedSuffix(string $baseName): string
    {
        $stripped = preg_replace(self::LEGACY_SUFFIX_REGEX, '', $baseName);
        return is_string($stripped) === true ? $stripped : $baseName;

    }//end stripLegacyAnonymizedSuffix()


    /**
     * Indicate whether the given base name is itself a prior anonymisation
     * output (used by the source-discovery filter).
     *
     * @param string $baseName Source base name (without extension).
     *
     * @return bool True iff the base name ends with the legacy `_anonymized` suffix.
     */
    public function isLegacyAnonymizedOutput(string $baseName): bool
    {
        return preg_match(self::LEGACY_SUFFIX_REGEX, $baseName) === 1;

    }//end isLegacyAnonymizedOutput()


    /**
     * Resolve the configured subfolder name with validation and fallback.
     *
     * Reads `docudesk.anonymisation.output_subfolder_name`; falls back to
     * `anonymised` and logs a warning when the value does not match the
     * `/^[a-z0-9_-]+$/` validation regex.
     *
     * @return string A safe, validated subfolder name.
     */
    public function getSubfolderName(): string
    {
        $value = $this->config->getValueString(
            'docudesk',
            self::SUBFOLDER_CONFIG_KEY,
            self::DEFAULT_SUBFOLDER_NAME
        );

        if (preg_match(self::SUBFOLDER_NAME_REGEX, $value) !== 1) {
            $this->logger->warning(
                'OutputLayoutResolver: configured subfolder name fails validation '
                .'(/^[a-z0-9_-]+$/), falling back to default.',
                [
                    'configured' => $value,
                    'default'    => self::DEFAULT_SUBFOLDER_NAME,
                ]
            );
            return self::DEFAULT_SUBFOLDER_NAME;
        }

        return $value;

    }//end getSubfolderName()


}//end class

<?php

/**
 * Output Layout Resolver
 *
 * Computes the destination path for batch-anonymised files under the
 * configurable output subfolder.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCP\Files\Folder;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Resolves the destination NC path for a batch-anonymised output file.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
 */
class OutputLayoutResolver
{

    /**
     * Config key for the output subfolder name.
     *
     * @var string
     */
    private const CONFIG_KEY = 'anonymisation.output_subfolder_name';

    /**
     * Default output subfolder name.
     *
     * @var string
     */
    public const DEFAULT_SUBFOLDER = 'anonymised';

    /**
     * Valid subfolder name pattern: lowercase letters, digits, hyphens, underscores.
     *
     * @var string
     */
    private const VALID_SUBFOLDER_PATTERN = '/^[a-z0-9_-]+$/';

    /**
     * Constructor for OutputLayoutResolver.
     *
     * @param IAppConfig      $appConfig App configuration.
     * @param LoggerInterface $logger    Logger for warning on invalid config.
     *
     * @return void
     */
    public function __construct(
        private readonly IAppConfig $appConfig,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Resolve the absolute NC path for the batch output file.
     *
     * Strips a trailing `_anonymized` suffix from $sourceBaseName (regex
     * `s/_anonymized$//`) so cascading suffixes are avoided. The configured
     * subfolder name is validated at read time; invalid values fall back to
     * the default with a warning.
     *
     * @param Folder $sourceFolder   The source folder node.
     * @param string $sourceBaseName Base name of the original source file (no extension).
     * @param string $extension      File extension including the leading dot, e.g. `.pdf`.
     *
     * @return string Absolute NC path of the form `<sourceFolder>/<subfolder>/<cleanBase><ext>`.
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
     */
    public function resolveBatchDestination(
        Folder $sourceFolder,
        string $sourceBaseName,
        string $extension
    ): string {
        $subfolder = $this->readSubfolderName();
        $cleanBase = $this->stripAnonymizedSuffix(baseName: $sourceBaseName);

        return $sourceFolder->getPath().'/'.$subfolder.'/'.$cleanBase.$extension;

    }//end resolveBatchDestination()

    /**
     * Read the configured subfolder name from app config.
     *
     * Falls back to the default when the stored value is missing, empty, or
     * fails the validation regex.
     *
     * @return string Validated subfolder name.
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
     */
    public function readSubfolderName(): string
    {
        $value = $this->appConfig->getValueString(
            app: 'docudesk',
            key: self::CONFIG_KEY,
            default: self::DEFAULT_SUBFOLDER
        );

        if ($this->isValidSubfolderName(name: $value) === true) {
            return $value;
        }

        $this->logger->warning(
            'docudesk.anonymisation.output_subfolder_name has an invalid value; '
            .'falling back to default.',
            ['configured' => $value, 'default' => self::DEFAULT_SUBFOLDER]
        );

        return self::DEFAULT_SUBFOLDER;

    }//end readSubfolderName()

    /**
     * Validate a subfolder name against the allowed pattern.
     *
     * Allowed: non-empty, lowercase letters, digits, hyphens, underscores.
     * Dots, slashes, spaces and other characters are rejected.
     *
     * @param string $name The subfolder name to validate.
     *
     * @return bool True when valid.
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
     */
    public function isValidSubfolderName(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        return preg_match(self::VALID_SUBFOLDER_PATTERN, $name) === 1;

    }//end isValidSubfolderName()

    /**
     * Strip a trailing `_anonymized` suffix from a base name.
     *
     * Only the suffix at the very end of the string (before the extension,
     * which has already been separated by the caller) is removed. A mid-name
     * occurrence such as `foo_anonymized_v2` is left intact.
     *
     * @param string $baseName The file base name without extension.
     *
     * @return string Base name with trailing `_anonymized` stripped.
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-1
     */
    public function stripAnonymizedSuffix(string $baseName): string
    {
        return preg_replace('/_anonymized$/', '', $baseName) ?? $baseName;

    }//end stripAnonymizedSuffix()
}//end class

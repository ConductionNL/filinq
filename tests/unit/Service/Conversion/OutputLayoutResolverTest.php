<?php

/**
 * Unit tests for OutputLayoutResolver.
 *
 * Exercises the four-corner contract from
 * `anonymisation-batch-output-folder-layout` task 9 + the source-discovery
 * filter from `anonymisation-folder-output-folder-layout` task 3:
 *   - clean filename → no suffix stripped
 *   - legacy `_anonymized` suffix → stripped
 *   - `_anonymized` mid-name → preserved
 *   - invalid configured subfolder name → fallback to default with warning
 *   - `isLegacyAnonymizedOutput` discriminates correctly for source-discovery.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for OutputLayoutResolver.
 *
 * @internal
 * @coversDefaultClass \OCA\DocuDesk\Service\Conversion\OutputLayoutResolver
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class OutputLayoutResolverTest extends TestCase
{


    /**
     * Build a resolver backed by mocked IAppConfig + logger.
     *
     * @param string|null $configuredSubfolder Value to return from getValueString;
     *                                         null means "use the default".
     *
     * @return OutputLayoutResolver
     */
    private function buildResolver(?string $configuredSubfolder=null): OutputLayoutResolver
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')
            ->willReturnCallback(
                static function (string $app, string $key, string $default) use ($configuredSubfolder): string {
                    return $configuredSubfolder ?? $default;
                }
            );

        $logger = $this->createMock(LoggerInterface::class);
        return new OutputLayoutResolver($config, $logger);

    }//end buildResolver()


    /**
     * Clean filenames are not modified.
     *
     * @return void
     */
    public function testCleanFilenameIsUnchanged(): void
    {
        $resolver = $this->buildResolver();
        $path     = $resolver->resolveBatchDestination('/Files/cases', 'Report', 'pdf');

        self::assertSame('/Files/cases/anonymised/Report.pdf', $path);

    }//end testCleanFilenameIsUnchanged()


    /**
     * Trailing `_anonymized` is stripped from the base name (no `_anonymized_anonymized`).
     *
     * @return void
     */
    public function testTrailingAnonymizedIsStripped(): void
    {
        $resolver = $this->buildResolver();
        $path     = $resolver->resolveBatchDestination('/Files/cases', 'Report_anonymized', 'pdf');

        self::assertSame('/Files/cases/anonymised/Report.pdf', $path);

    }//end testTrailingAnonymizedIsStripped()


    /**
     * `_anonymized` mid-name is preserved (only the trailing literal is special).
     *
     * @return void
     */
    public function testMidNameAnonymizedIsPreserved(): void
    {
        $resolver = $this->buildResolver();
        $path     = $resolver->resolveBatchDestination('/Files/cases', '_anonymized_summary', 'pdf');

        self::assertSame('/Files/cases/anonymised/_anonymized_summary.pdf', $path);

    }//end testMidNameAnonymizedIsPreserved()


    /**
     * A custom subfolder name is honoured when it passes validation.
     *
     * @return void
     */
    public function testCustomSubfolderNameHonoured(): void
    {
        $resolver = $this->buildResolver('redacted');
        $path     = $resolver->resolveBatchDestination('/Files/cases', 'Report', 'pdf');

        self::assertSame('/Files/cases/redacted/Report.pdf', $path);

    }//end testCustomSubfolderNameHonoured()


    /**
     * An invalid configured subfolder name falls back to the default and emits a warning.
     *
     * @return void
     */
    public function testInvalidSubfolderFallsBackToDefault(): void
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturn('bad name with spaces');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('falling back to default'), self::isType('array'));

        $resolver = new OutputLayoutResolver($config, $logger);
        $path     = $resolver->resolveBatchDestination('/Files/cases', 'Report', 'pdf');

        self::assertSame('/Files/cases/anonymised/Report.pdf', $path);

    }//end testInvalidSubfolderFallsBackToDefault()


    /**
     * `isLegacyAnonymizedOutput` discriminates as the source-discovery filter expects.
     *
     * @return void
     */
    public function testIsLegacyAnonymizedOutputDiscriminates(): void
    {
        $resolver = $this->buildResolver();

        self::assertTrue($resolver->isLegacyAnonymizedOutput('Report_anonymized'));
        self::assertFalse($resolver->isLegacyAnonymizedOutput('Report'));
        self::assertFalse($resolver->isLegacyAnonymizedOutput('_anonymized_summary'));

    }//end testIsLegacyAnonymizedOutputDiscriminates()


    /**
     * Folder paths with a trailing slash are normalised (no double-slash in output).
     *
     * @return void
     */
    public function testTrailingSlashIsNormalised(): void
    {
        $resolver = $this->buildResolver();
        $path     = $resolver->resolveBatchDestination('/Files/cases/', 'Report', 'pdf');

        self::assertSame('/Files/cases/anonymised/Report.pdf', $path);

    }//end testTrailingSlashIsNormalised()


}//end class

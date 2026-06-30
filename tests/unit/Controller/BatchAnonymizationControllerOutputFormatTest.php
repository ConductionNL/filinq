<?php

/**
 * Unit tests for BatchAnonymizationController outputFormat handling
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Shape-level coverage for the anonymise-pdf-only-output-mode change on the
 * batch endpoint: the widened three-value enum and the new pdf-only default.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchAnonymizationControllerOutputFormatTest extends TestCase
{


    /**
     * The batch controller's VALID_OUTPUT_FORMATS allow-list must contain
     * pdf-only, pdf and preserve (task 1.2).
     *
     * @return void
     */
    public function testBatchValidOutputFormatsContainsPdfOnly(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/BatchAnonymizationController.php');

        $this->assertStringContainsString(
            'self::VALID_OUTPUT_FORMATS',
            $content,
            'Batch controller must expose a VALID_OUTPUT_FORMATS allow-list'
        );
        $this->assertMatchesRegularExpression(
            "/'pdf-only'\\s*,\\s*'pdf'\\s*,\\s*'preserve'/",
            $content,
            'Batch VALID_OUTPUT_FORMATS must contain pdf-only, pdf and preserve'
        );

    }//end testBatchValidOutputFormatsContainsPdfOnly()


    /**
     * The batch controller must resolve to the new pdf-only default when no
     * per-call value is supplied and when the tenant setting is malformed
     * (task 4.5).
     *
     * @return void
     */
    public function testBatchDefaultsToPdfOnly(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/BatchAnonymizationController.php');

        $this->assertMatchesRegularExpression(
            "/self::DEFAULT_OUTPUT_FORMAT_KEY\\s*,\\s*'pdf-only'/s",
            $content,
            'tenant default read must fall back to pdf-only'
        );
        $this->assertStringContainsString(
            "return 'pdf-only';",
            $content,
            'malformed tenant setting must fall back to pdf-only'
        );

    }//end testBatchDefaultsToPdfOnly()


    /**
     * The batch controller must read the tenant default via the documented
     * IAppConfig key.
     *
     * @return void
     */
    public function testBatchReadsTenantDefaultOutputFormat(): void
    {
        $content = file_get_contents(__DIR__ . '/../../../lib/Controller/BatchAnonymizationController.php');

        $this->assertStringContainsString(
            'docudesk.anonymisation.default_output_format',
            $content,
            'Batch controller must read tenant default via the documented IAppConfig key'
        );

    }//end testBatchReadsTenantDefaultOutputFormat()


}//end class
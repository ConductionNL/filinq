<?php

/**
 * Unit tests for ConversionFailedException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Exception;

use OCA\DocuDesk\Exception\ConversionFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConversionFailedException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConversionFailedExceptionTest extends TestCase
{

    /**
     * Default constructor produces code 422 and empty attempts.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testDefaultConstructorYields422AndEmptyAttempts(): void
    {
        $e = new ConversionFailedException();

        $this->assertSame(422, $e->getCode());
        $this->assertSame([], $e->getAttempts());
        $this->assertStringContainsString('failed', strtolower($e->getMessage()));

    }//end testDefaultConstructorYields422AndEmptyAttempts()


    /**
     * Attempts are returned exactly as provided.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testAttemptsRoundtrip(): void
    {
        $attempts = [
            ['backend' => 'office_app',          'available' => false, 'supports' => true,  'reason' => 'Not installed'],
            ['backend' => 'libreoffice_headless', 'available' => false, 'supports' => true,  'reason' => 'Binary not found'],
            ['backend' => 'phpword',              'available' => true,  'supports' => false, 'reason' => 'XLSX unsupported'],
        ];

        $e = new ConversionFailedException(attempts: $attempts);

        $this->assertSame($attempts, $e->getAttempts());
        $this->assertCount(3, $e->getAttempts());

    }//end testAttemptsRoundtrip()


    /**
     * Custom message and code are preserved.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testCustomMessageAndCodePreserved(): void
    {
        $e = new ConversionFailedException(
            message: 'Custom conversion failure.',
            code: 500
        );

        $this->assertSame('Custom conversion failure.', $e->getMessage());
        $this->assertSame(500, $e->getCode());

    }//end testCustomMessageAndCodePreserved()


    /**
     * Exception is a RuntimeException.
     *
     * @return void
     *
     * @spec openspec/changes/anonymise-output-format-flag/tasks.md#task-4
     */
    public function testIsRuntimeException(): void
    {
        $e = new ConversionFailedException();
        $this->assertInstanceOf(\RuntimeException::class, $e);

    }//end testIsRuntimeException()


}//end class

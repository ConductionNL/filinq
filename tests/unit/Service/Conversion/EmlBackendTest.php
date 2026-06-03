<?php

/**
 * Unit tests for EmlBackend
 *
 * Covers the EML stub backend: isAvailable() always returns false until
 * OpenRegister ships EML support, canHandle() claims message/rfc822, and
 * convert() throws defensively.
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
 * @spec openspec/changes/pdf-conversion-service/tasks.md#task-11
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\Conversion\EmlBackend;
use OCP\Files\File;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EmlBackend
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlBackendTest extends TestCase
{

    /**
     * App config mock.
     *
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $appConfig;

    /**
     * Logger mock.
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;

    /**
     * Backend under test.
     *
     * @var EmlBackend
     */
    private EmlBackend $backend;

    /**
     * Set up the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appConfig = $this->createMock(originalClassName: IAppConfig::class);
        $this->logger    = $this->createMock(originalClassName: LoggerInterface::class);

        $this->backend = new EmlBackend(
            appConfig: $this->appConfig,
            logger: $this->logger,
        );

    }//end setUp()

    /**
     * Test that name() returns the stable identifier 'eml'
     *
     * @return void
     */
    public function testNameReturnsEml(): void
    {
        $this->assertSame(expected: 'eml', actual: $this->backend->name());

    }//end testNameReturnsEml()

    /**
     * Test that isAvailable() returns false regardless of tenant flag
     *
     * @return void
     */
    public function testIsAvailableReturnsFalseAlways(): void
    {
        $this->appConfig->method('getValueString')->willReturn('true');
        $this->assertFalse(condition: $this->backend->isAvailable());

    }//end testIsAvailableReturnsFalseAlways()

    /**
     * Test that isAvailable() returns false even when tenant flag is 'false'
     *
     * @return void
     */
    public function testIsAvailableReturnsFalseWhenFlagFalse(): void
    {
        $this->appConfig->method('getValueString')->willReturn('false');
        $this->assertFalse(condition: $this->backend->isAvailable());

    }//end testIsAvailableReturnsFalseWhenFlagFalse()

    /**
     * Test that canHandle() returns true for message/rfc822 MIME
     *
     * @return void
     */
    public function testCanHandleEmlMime(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'message/rfc822', extension: 'eml')
        );

    }//end testCanHandleEmlMime()

    /**
     * Test that canHandle() returns true for .eml extension
     *
     * @return void
     */
    public function testCanHandleEmlExtension(): void
    {
        $this->assertTrue(
            condition: $this->backend->canHandle(mimeType: 'application/octet-stream', extension: 'eml')
        );

    }//end testCanHandleEmlExtension()

    /**
     * Test that canHandle() returns false for non-EML formats
     *
     * @return void
     */
    public function testCannotHandleDocxMime(): void
    {
        $this->assertFalse(
            condition: $this->backend->canHandle(
                mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                extension: 'docx'
            )
        );

    }//end testCannotHandleDocxMime()

    /**
     * Test that convert() throws ConversionFailedException as a defensive backstop
     *
     * @return void
     */
    public function testConvertThrowsDefensively(): void
    {
        $source = $this->createMock(originalClassName: File::class);
        $source->method('getPath')->willReturn('/u/admin/test.eml');

        $this->expectException(exception: ConversionFailedException::class);
        $this->backend->convert(source: $source);

    }//end testConvertThrowsDefensively()

    /**
     * Test that the attempt record in the thrown exception has the correct shape
     *
     * @return void
     */
    public function testConvertAttemptRecordShape(): void
    {
        $source = $this->createMock(originalClassName: File::class);
        $source->method('getPath')->willReturn('/u/admin/test.eml');

        try {
            $this->backend->convert(source: $source);
            $this->fail(message: 'Expected ConversionFailedException');
        } catch (ConversionFailedException $e) {
            $attempts = $e->getAttempts();
            $this->assertNotEmpty(actual: $attempts);
            $this->assertSame(expected: 'eml', actual: $attempts[0]['name']);
            $this->assertFalse(condition: $attempts[0]['available']);
            $this->assertTrue(condition: $attempts[0]['supports']);
        }

    }//end testConvertAttemptRecordShape()
}//end class

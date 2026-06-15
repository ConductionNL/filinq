<?php

/**
 * Unit tests for MetricsCollector
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
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-5.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\MetricsCollector;
use OCP\IAppConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MetricsCollector
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MetricsCollectorTest extends TestCase
{

    /**
     * @var MetricsCollector
     */
    private MetricsCollector $collector;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;

    /**
     * @var IDBConnection|MockObject
     */
    private IDBConnection|MockObject $mockDatabase;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfig   = $this->createMock(IAppConfig::class);
        $this->mockDatabase = $this->createMock(IDBConnection::class);
        $this->mockLogger   = $this->createMock(LoggerInterface::class);

        $this->collector = new MetricsCollector(
            config: $this->mockConfig,
            database: $this->mockDatabase,
            logger: $this->mockLogger,
        );

    }//end setUp()

    /**
     * Test countDocuments returns zero when register/schema not configured.
     *
     * @return void
     */
    public function testCountDocumentsReturnsZeroWhenNotConfigured(): void
    {
        $this->mockConfig->method('getValueString')->willReturn('');

        $count = $this->collector->countDocuments();

        $this->assertSame(0, $count);

    }//end testCountDocumentsReturnsZeroWhenNotConfigured()

    /**
     * Test countTemplates returns zero when register/schema not configured.
     *
     * @return void
     */
    public function testCountTemplatesReturnsZeroWhenNotConfigured(): void
    {
        $this->mockConfig->method('getValueString')->willReturn('');

        $count = $this->collector->countTemplates();

        $this->assertSame(0, $count);

    }//end testCountTemplatesReturnsZeroWhenNotConfigured()

    /**
     * Test countDocuments returns count from database query.
     *
     * @return void
     */
    public function testCountDocumentsReturnsCountFromDatabase(): void
    {
        $this->mockConfig->method('getValueString')->willReturn('register-id');

        $mockResult = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['fetchOne', 'closeCursor'])
            ->getMock();
        $mockResult->method('fetchOne')->willReturn('5');

        $mockExpr = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['eq'])
            ->getMock();
        $mockExpr->method('eq')->willReturn('1=1');

        $mockQb = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['select', 'createFunction', 'from', 'where', 'andWhere', 'createNamedParameter', 'executeQuery', 'expr'])
            ->getMock();
        $mockQb->method('select')->willReturnSelf();
        $mockQb->method('createFunction')->willReturn('COUNT(*) AS cnt');
        $mockQb->method('from')->willReturnSelf();
        $mockQb->method('where')->willReturnSelf();
        $mockQb->method('andWhere')->willReturnSelf();
        $mockQb->method('createNamedParameter')->willReturn(':param');
        $mockQb->method('expr')->willReturn($mockExpr);
        $mockQb->method('executeQuery')->willReturn($mockResult);

        $this->mockDatabase->method('getQueryBuilder')->willReturn($mockQb);

        $count = $this->collector->countDocuments();

        $this->assertSame(5, $count);

    }//end testCountDocumentsReturnsCountFromDatabase()

    /**
     * Test countDocuments returns zero on database exception.
     *
     * @return void
     */
    public function testCountDocumentsReturnsZeroOnDatabaseException(): void
    {
        $this->mockConfig->method('getValueString')->willReturn('some-register');

        $this->mockDatabase->method('getQueryBuilder')
            ->willThrowException(new \Exception('DB error'));

        $count = $this->collector->countDocuments();

        $this->assertSame(0, $count);

    }//end testCountDocumentsReturnsZeroOnDatabaseException()
}//end class

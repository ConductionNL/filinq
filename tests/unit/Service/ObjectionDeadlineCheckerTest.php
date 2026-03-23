<?php

/**
 * Unit tests for ObjectionDeadlineChecker
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ObjectionDeadlineChecker;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ObjectionDeadlineChecker
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ObjectionDeadlineCheckerTest extends TestCase
{

    /**
     * @var ObjectionDeadlineChecker
     */
    private ObjectionDeadlineChecker $checker;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var ContainerInterface|MockObject
     */
    private ContainerInterface|MockObject $mockContainer;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockConfig;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger     = $this->createMock(LoggerInterface::class);
        $this->mockContainer  = $this->createMock(ContainerInterface::class);
        $this->mockAppManager = $this->createMock(IAppManager::class);
        $this->mockConfig     = $this->createMock(IAppConfig::class);

        $this->checker = new ObjectionDeadlineChecker(
            $this->mockLogger,
            $this->mockContainer,
            $this->mockAppManager,
            $this->mockConfig
        );

    }//end setUp()


    /**
     * Test getObjectionPeriodDays returns configured value
     *
     * @return void
     */
    public function testGetObjectionPeriodDaysReturnsConfiguredValue(): void
    {
        $this->mockConfig->method('getValueString')
            ->with('docudesk', 'publication_objection_period_days', '28')
            ->willReturn('14');

        $this->assertEquals(14, $this->checker->getObjectionPeriodDays());

    }//end testGetObjectionPeriodDaysReturnsConfiguredValue()


    /**
     * Test getObjectionPeriodDays returns default value
     *
     * @return void
     */
    public function testGetObjectionPeriodDaysReturnsDefault(): void
    {
        $this->mockConfig->method('getValueString')
            ->with('docudesk', 'publication_objection_period_days', '28')
            ->willReturn('28');

        $this->assertEquals(28, $this->checker->getObjectionPeriodDays());

    }//end testGetObjectionPeriodDaysReturnsDefault()


    /**
     * Test calculateDeadline returns future date
     *
     * @return void
     */
    public function testCalculateDeadlineReturnsFutureDate(): void
    {
        $this->mockConfig->method('getValueString')
            ->willReturn('28');

        $deadline = $this->checker->calculateDeadline();
        $now      = new \DateTime();

        $this->assertGreaterThan($now, $deadline);

    }//end testCalculateDeadlineReturnsFutureDate()


    /**
     * Test checkObjectionDeadline throws when OpenRegister not installed
     *
     * @return void
     */
    public function testCheckObjectionDeadlineThrowsWhenNotInstalled(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to check objection deadline');

        $this->mockAppManager->method('getInstalledApps')
            ->willReturn([]);

        $this->checker->checkObjectionDeadline('uuid-1', 'reg-1', 'sch-1');

    }//end testCheckObjectionDeadlineThrowsWhenNotInstalled()


}//end class

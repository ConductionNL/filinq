<?php

/**
 * Unit tests for DossierCheckedOnListener
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\DossierCheckedOnListener;
use OCA\Filinq\Service\LegalBasesSummaryService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DossierCheckedOnListener
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DossierCheckedOnListenerTest extends TestCase {

	/**
	 * The listener under test.
	 *
	 * @var DossierCheckedOnListener
	 */
	private DossierCheckedOnListener $listener;

	/**
	 * Mock logger.
	 *
	 * @var MockObject&LoggerInterface
	 */
	private MockObject $logger;

	/**
	 * Mock grondslagen summary service.
	 *
	 * @var MockObject&LegalBasesSummaryService
	 */
	private MockObject $grondslagenSummaryService;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);
		$this->grondslagenSummaryService = $this->createMock(originalClassName: LegalBasesSummaryService::class);

		$this->listener = new DossierCheckedOnListener(
			logger: $this->logger,
			summaryService: $this->grondslagenSummaryService,
		);

	}//end setUp()

	/**
	 * Test that listener can be instantiated with injected dependencies
	 *
	 * @return void
	 */
	public function testListenerCanBeInstantiated(): void {
		$this->assertInstanceOf(expected: DossierCheckedOnListener::class, actual: $this->listener);

	}//end testListenerCanBeInstantiated()

	/**
	 * Test that non-ObjectUpdatedEvent is silently ignored
	 *
	 * @return void
	 */
	public function testHandleIgnoresNonObjectUpdatedEvent(): void {
		$genericEvent = $this->createMock(originalClassName: Event::class);

		// Should not throw.
		$this->listener->handle(event: $genericEvent);

		$this->addToAssertionCount(count: 1);

	}//end testHandleIgnoresNonObjectUpdatedEvent()
}//end class

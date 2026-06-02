<?php

/**
 * Unit tests for DossierCheckedOnListener
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\EventListener;

use OCA\DocuDesk\EventListener\DossierCheckedOnListener;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DossierCheckedOnListener
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DossierCheckedOnListenerTest extends TestCase
{

    /**
     * @var DossierCheckedOnListener
     */
    private DossierCheckedOnListener $listener;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->listener = new DossierCheckedOnListener();

    }//end setUp()

    /**
     * Test that non-ObjectUpdatedEvent is silently ignored
     *
     * @return void
     */
    public function testHandleIgnoresNonObjectUpdatedEvent(): void
    {
        $genericEvent = $this->createMock(Event::class);

        // Should not throw.
        $this->listener->handle(event: $genericEvent);

        $this->addToAssertionCount(1);

    }//end testHandleIgnoresNonObjectUpdatedEvent()

    /**
     * Test that listener can be instantiated
     *
     * @return void
     */
    public function testListenerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DossierCheckedOnListener::class, $this->listener);

    }//end testListenerCanBeInstantiated()
}//end class

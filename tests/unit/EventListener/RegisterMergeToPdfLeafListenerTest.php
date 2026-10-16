<?php

/**
 * Unit tests for RegisterMergeToPdfLeafListener
 *
 * The merge-to-PDF leaf reaches OpenRegister's catalogue on both halves, and
 * the registrar wires the listener.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/specs/document-merge/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\AppInfo\IntegrationLeafRegistrar;
use OCA\Filinq\EventListener\RegisterMergeToPdfLeafListener;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the merge-to-PDF leaf registration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RegisterMergeToPdfLeafListenerTest extends TestCase {

	/**
	 * A listener whose translator returns its source string.
	 *
	 * @return RegisterMergeToPdfLeafListener The listener under test.
	 */
	private function listener(): RegisterMergeToPdfLeafListener {
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->onlyMethods(['t'])
			->getMock();
		$l10n->method('t')->willReturnArgument(0);

		return new RegisterMergeToPdfLeafListener($l10n, $this->createMock(LoggerInterface::class));

	}//end listener()

	/**
	 * The leaf reaches the catalogue as a render surface for one object.
	 *
	 * @return void
	 */
	public function testContributesTheMergeLeaf(): void {
		$event = new RegisterLeafProvidersEvent();
		$this->listener()->handle($event);

		$leaves = $event->getLeaves();
		$this->assertCount(1, $leaves);

		$descriptor = $leaves[0]['descriptor'];
		$this->assertSame('filinq-merge-to-pdf', $descriptor->getId());
		$this->assertSame('Merge to PDF', $descriptor->getLabel());
		$this->assertSame([LeafDescriptor::KIND_RENDER_SURFACE], $descriptor->getKinds());
		$this->assertSame('filinq', $descriptor->getRequiredApp());
		$this->assertSame(['detail-page', 'single-entity'], $descriptor->getSurfaces());
		$this->assertSame(LeafDescriptor::RENDER_MODE_MOUNT, $descriptor->getRenderMode());
		$this->assertNull($leaves[0]['provider']);

	}//end testContributesTheMergeLeaf()

	/**
	 * Both halves name the same id, surfaces, icon and render mode.
	 *
	 * @return void
	 */
	public function testBothHalvesDeclareTheSameLeaf(): void {
		$js = file_get_contents(__DIR__ . '/../../../src/integrations/registerMergeToPdfLeaf.js');
		$this->assertIsString($js);

		$this->assertSame(1, preg_match("/MERGE_INTEGRATION_ID = '([^']+)'/", $js, $id));
		$this->assertSame(RegisterMergeToPdfLeafListener::LEAF_ID, $id[1]);

		$this->assertSame(1, preg_match('/export const MERGE_SURFACES = \[([^\]]*)\]/', $js, $surfaces));
		preg_match_all("/'([^']+)'/", $surfaces[1], $entries);
		$this->assertSame(RegisterMergeToPdfLeafListener::SURFACES, $entries[1]);

		$this->assertStringContainsString("renderMode: 'mount'", $js);
		$this->assertStringContainsString("icon: '" . RegisterMergeToPdfLeafListener::ICON . "'", $js);
		$this->assertStringContainsString("requiredApp: 'filinq'", $js);

		$entry = file_get_contents(__DIR__ . '/../../../src/leaves.js');
		$this->assertIsString($entry);
		$this->assertStringContainsString('registerMergeToPdfLeaf()', $entry);

		$main = file_get_contents(__DIR__ . '/../../../src/main.js');
		$this->assertIsString($main);
		$this->assertStringContainsString('registerMergeToPdfLeaf()', $main);

	}//end testBothHalvesDeclareTheSameLeaf()

	/**
	 * The registrar wires the listener, so the descriptor reaches a live instance.
	 *
	 * @return void
	 */
	public function testTheRegistrarWiresTheListener(): void {
		$wired = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->method('registerEventListener')->willReturnCallback(
			function (string $event, string $listener) use (&$wired): void {
				$wired[] = [$event, $listener];
			}
		);

		(new IntegrationLeafRegistrar())->register($context);

		$this->assertContains(
			[IntegrationLeafRegistrar::LEAF_EVENT, RegisterMergeToPdfLeafListener::class],
			$wired
		);

	}//end testTheRegistrarWiresTheListener()
}//end class

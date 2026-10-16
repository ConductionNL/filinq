<?php

/**
 * Unit tests for RegisterDocumentIntakeLeafListener
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
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\AppInfo\IntegrationLeafRegistrar;
use OCA\Filinq\EventListener\RegisterDocumentIntakeLeafListener;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the document intake leaf registration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RegisterDocumentIntakeLeafListenerTest extends TestCase {

	/**
	 * The leaf reaches the catalogue as a render surface for one object.
	 *
	 * @return void
	 */
	public function testContributesTheDocumentIntakeLeaf(): void {
		$l10n = $this->getMockBuilder(IL10N::class)->disableOriginalConstructor()->onlyMethods(['t'])->getMock();
		$l10n->method('t')->willReturnArgument(0);

		$event = new RegisterLeafProvidersEvent();
		(new RegisterDocumentIntakeLeafListener($l10n, $this->createMock(LoggerInterface::class)))->handle($event);

		$descriptor = $event->getLeaves()[0]['descriptor'];
		$this->assertSame('filinq-document-intake', $descriptor->getId());
		$this->assertSame('Documents waiting to be filed', $descriptor->getLabel());
		$this->assertSame([LeafDescriptor::KIND_RENDER_SURFACE], $descriptor->getKinds());
		$this->assertSame(['detail-page', 'single-entity'], $descriptor->getSurfaces());
		$this->assertSame(LeafDescriptor::RENDER_MODE_MOUNT, $descriptor->getRenderMode());

	}//end testContributesTheDocumentIntakeLeaf()

	/**
	 * Both halves agree, both bundles register it, and the registrar wires it.
	 *
	 * @return void
	 */
	public function testBothHalvesAgreeAndTheRegistrarWiresIt(): void {
		$js = file_get_contents(__DIR__ . '/../../../src/integrations/registerDocumentIntakeLeaf.js');
		$this->assertIsString($js);
		$this->assertSame(1, preg_match("/DOCUMENT_INTAKE_INTEGRATION_ID = '([^']+)'/", $js, $id));
		$this->assertSame(RegisterDocumentIntakeLeafListener::LEAF_ID, $id[1]);
		$this->assertSame(1, preg_match('/export const DOCUMENT_INTAKE_SURFACES = \[([^\]]*)\]/', $js, $surfaces));
		preg_match_all("/'([^']+)'/", $surfaces[1], $entries);
		$this->assertSame(RegisterDocumentIntakeLeafListener::SURFACES, $entries[1]);
		$this->assertStringContainsString("icon: '" . RegisterDocumentIntakeLeafListener::ICON . "'", $js);
		$this->assertStringContainsString("renderMode: 'mount'", $js);

		foreach (['leaves.js', 'main.js'] as $bundle) {
			$source = file_get_contents(__DIR__ . '/../../../src/' . $bundle);
			$this->assertIsString($source);
			$this->assertStringContainsString('registerDocumentIntakeLeaf()', $source);
		}

		$wired = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->method('registerEventListener')->willReturnCallback(
			function (string $event, string $listener) use (&$wired): void {
				$wired[] = [$event, $listener];
			}
		);
		(new IntegrationLeafRegistrar())->register($context);

		$this->assertContains([IntegrationLeafRegistrar::LEAF_EVENT, RegisterDocumentIntakeLeafListener::class], $wired);

	}//end testBothHalvesAgreeAndTheRegistrarWiresIt()
}//end class

<?php

/**
 * Unit tests for RegisterSigningFolderLeafListener
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
 * @spec openspec/changes/signing-folder-across-cases/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\AppInfo\IntegrationLeafRegistrar;
use OCA\Filinq\EventListener\RegisterSigningFolderLeafListener;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the signing folder leaf registration.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RegisterSigningFolderLeafListenerTest extends TestCase {

	/**
	 * The leaf reaches the catalogue as a dashboard render surface: the folder is per signer, not per record.
	 *
	 * @return void
	 */
	public function testContributesTheSigningFolderLeaf(): void {
		$l10n = $this->getMockBuilder(IL10N::class)->disableOriginalConstructor()->onlyMethods(['t'])->getMock();
		$l10n->method('t')->willReturnArgument(0);

		$event = new RegisterLeafProvidersEvent();
		(new RegisterSigningFolderLeafListener($l10n, $this->createMock(LoggerInterface::class)))->handle($event);

		$descriptor = $event->getLeaves()[0]['descriptor'];
		$this->assertSame('filinq-signing-folder', $descriptor->getId());
		$this->assertSame('Waiting for your signature', $descriptor->getLabel());
		$this->assertSame([LeafDescriptor::KIND_RENDER_SURFACE], $descriptor->getKinds());
		$this->assertSame(['user-dashboard', 'app-dashboard'], $descriptor->getSurfaces());
		$this->assertSame(LeafDescriptor::RENDER_MODE_MOUNT, $descriptor->getRenderMode());

	}//end testContributesTheSigningFolderLeaf()

	/**
	 * Both halves agree, both bundles register it, and the registrar wires it.
	 *
	 * @return void
	 */
	public function testBothHalvesAgreeAndTheRegistrarWiresIt(): void {
		$js = file_get_contents(__DIR__ . '/../../../src/integrations/registerSigningFolderLeaf.js');
		$this->assertIsString($js);
		$this->assertSame(1, preg_match("/SIGNING_FOLDER_INTEGRATION_ID = '([^']+)'/", $js, $id));
		$this->assertSame(RegisterSigningFolderLeafListener::LEAF_ID, $id[1]);
		$this->assertSame(1, preg_match('/export const SIGNING_FOLDER_SURFACES = \[([^\]]*)\]/', $js, $surfaces));
		preg_match_all("/'([^']+)'/", $surfaces[1], $entries);
		$this->assertSame(RegisterSigningFolderLeafListener::SURFACES, $entries[1]);
		$this->assertStringContainsString("icon: '" . RegisterSigningFolderLeafListener::ICON . "'", $js);
		$this->assertStringContainsString("renderMode: 'mount'", $js);

		foreach (['leaves.js', 'main.js'] as $bundle) {
			$source = file_get_contents(__DIR__ . '/../../../src/' . $bundle);
			$this->assertIsString($source);
			$this->assertStringContainsString('registerSigningFolderLeaf()', $source);
		}

		$wired = [];
		$context = $this->createMock(IRegistrationContext::class);
		$context->method('registerEventListener')->willReturnCallback(
			function (string $event, string $listener) use (&$wired): void {
				$wired[] = [$event, $listener];
			}
		);
		(new IntegrationLeafRegistrar())->register($context);

		$this->assertContains([IntegrationLeafRegistrar::LEAF_EVENT, RegisterSigningFolderLeafListener::class], $wired);

	}//end testBothHalvesAgreeAndTheRegistrarWiresIt()
}//end class

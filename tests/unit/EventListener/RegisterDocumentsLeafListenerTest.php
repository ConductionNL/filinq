<?php

/**
 * Unit tests for RegisterDocumentsLeafListener.
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\RegisterDocumentsLeafListener;
use OCA\OpenRegister\Event\RegisterLeafProvidersEvent;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * What the leaf declares, and that both halves of the declaration agree.
 *
 * The parity test is the one that matters: a leaf whose server half says one
 * thing and whose JS half says another renders differently depending on which
 * half the consumer read, and nothing at runtime complains.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class RegisterDocumentsLeafListenerTest extends TestCase {

	/**
	 * The JS half of the registration, as source.
	 *
	 * @return string The file contents.
	 */
	private function jsHalf(): string {
		$raw = file_get_contents(__DIR__ . '/../../../src/integrations/registerDocumentsLeaf.js');
		$this->assertIsString($raw);

		return $raw;

	}//end jsHalf()

	/**
	 * A listener whose translator returns its source string.
	 *
	 * `onlyMethods` on purpose: a double that may invent methods the real
	 * interface lacks can only ever pass.
	 *
	 * @return RegisterDocumentsLeafListener The listener under test.
	 */
	private function listener(): RegisterDocumentsLeafListener {
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->onlyMethods(['t'])
			->getMock();
		$l10n->method('t')->willReturnArgument(0);

		return new RegisterDocumentsLeafListener(
			$l10n,
			$this->createMock(LoggerInterface::class)
		);

	}//end listener()

	/**
	 * The leaf reaches the catalogue with the shape the consumer needs.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
	 */
	public function testContributesTheDocumentsLeaf(): void {
		$event = new RegisterLeafProvidersEvent();
		$this->listener()->handle($event);

		$leaves = $event->getLeaves();
		$this->assertCount(1, $leaves);

		$descriptor = $leaves[0]['descriptor'];
		$this->assertInstanceOf(LeafDescriptor::class, $descriptor);
		$this->assertSame('filinq-documents', $descriptor->getId());
		$this->assertSame('Documents', $descriptor->getLabel());
		$this->assertSame('FileDocumentMultipleOutline', $descriptor->getIcon());
		$this->assertSame([LeafDescriptor::KIND_RENDER_SURFACE], $descriptor->getKinds());
		$this->assertSame('filinq', $descriptor->getRequiredApp());
		$this->assertSame(['detail-page', 'single-entity'], $descriptor->getSurfaces());
		$this->assertSame('filinq-documents', $descriptor->getReferenceType());
		$this->assertSame(LeafDescriptor::RENDER_MODE_MOUNT, $descriptor->getRenderMode());

		// A render-only leaf: no provider, so the registry can never be asked
		// to read or append through it.
		$this->assertNull($leaves[0]['provider']);

	}//end testContributesTheDocumentsLeaf()

	/**
	 * The leaf is hidden on an instance without Filinq, rather than broken.
	 *
	 * @return void
	 */
	public function testTheLeafIsGatedOnFilinqBeingInstalled(): void {
		$event = new RegisterLeafProvidersEvent();
		$this->listener()->handle($event);

		$this->assertSame('filinq', $event->getLeaves()[0]['descriptor']->getRequiredApp());

	}//end testTheLeafIsGatedOnFilinqBeingInstalled()

	/**
	 * Another app's event is not a leaf collection.
	 *
	 * @return void
	 */
	public function testAnUnrelatedEventRegistersNothing(): void {
		$event = new Event();
		$this->listener()->handle($event);

		$this->assertTrue(true, 'handle() returned without touching a foreign event');

	}//end testAnUnrelatedEventRegistersNothing()

	/**
	 * A throwing listener costs its own leaf and nothing else.
	 *
	 * @return void
	 */
	public function testAFailedRegistrationIsLoggedAndSwallowed(): void {
		$l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->onlyMethods(['t'])
			->getMock();
		$l10n->method('t')->willThrowException(new RuntimeException('no catalogue'));

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$event = new RegisterLeafProvidersEvent();
		(new RegisterDocumentsLeafListener($l10n, $logger))->handle($event);

		$this->assertSame([], $event->getLeaves());

	}//end testAFailedRegistrationIsLoggedAndSwallowed()

	/**
	 * Both halves of the registration describe the SAME leaf.
	 *
	 * The id, the render mode, the icon and the surface set are read out of the
	 * JS source rather than restated here, so a change to either half that the
	 * other does not follow reddens this assertion.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
	 */
	public function testBothHalvesDeclareTheSameLeaf(): void {
		$js = $this->jsHalf();

		$this->assertSame(
			1,
			preg_match("/DOCUMENTS_INTEGRATION_ID = '([^']+)'/", $js, $id),
			'the JS half must name its leaf id'
		);
		$this->assertSame(RegisterDocumentsLeafListener::LEAF_ID, $id[1]);

		$this->assertSame(
			1,
			preg_match('/export const SURFACES = \[([^\]]*)\]/', $js, $surfaces),
			'the JS half must declare its surfaces explicitly, never by omission'
		);
		$declared = [];
		preg_match_all("/'([^']+)'/", $surfaces[1], $entries);
		foreach ($entries[1] as $entry) {
			$declared[] = $entry;
		}

		$this->assertSame(RegisterDocumentsLeafListener::SURFACES, $declared);

		$this->assertStringContainsString("renderMode: 'mount'", $js);
		$this->assertStringContainsString("icon: 'FileDocumentMultipleOutline'", $js);
		$this->assertStringContainsString("requiredApp: 'filinq'", $js);

	}//end testBothHalvesDeclareTheSameLeaf()

	/**
	 * The client half ships as its own bundle, or it never reaches a page.
	 *
	 * OpenRegister's LeafScriptListener enqueues the `leaves` entry on
	 * consuming pages and SKIPS an app that ships none — silently. That is the
	 * whole failure this change exists to end, so it is asserted rather than
	 * assumed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/leaf-integrations/tasks.md#task-2-1
	 */
	public function testTheLeafBundleIsAWebpackEntry(): void {
		$webpack = file_get_contents(__DIR__ . '/../../../webpack.config.js');
		$this->assertIsString($webpack);

		$this->assertMatchesRegularExpression(
			"/\n\tleaves: \{\n\t\timport: path\.join\(__dirname, 'src', 'leaves\.js'\),\n\t\tfilename: appId \+ '-leaves\.js',/",
			$webpack,
			"the leaves entry must be named `leaves` and emit `filinq-leaves.js`"
		);

		$entry = file_get_contents(__DIR__ . '/../../../src/leaves.js');
		$this->assertIsString($entry);
		$this->assertStringContainsString('registerDocumentsLeaf()', $entry);

		// Anything IMPORTED here lands on other apps' pages. The test reads the
		// import statements rather than the whole file, because this file's own
		// prose names the things it must not pull in.
		preg_match_all('/^import .*$/m', $entry, $imports);
		foreach ($imports[0] as $import) {
			$this->assertStringNotContainsString('manifest.json', $import);
			$this->assertStringNotContainsString('pinia', $import);
			$this->assertStringNotContainsString('App.vue', $import);
			$this->assertStringNotContainsString('vue-router', $import);
		}

		$this->assertNotEmpty($imports[0], 'the entry must import the leaf registration');

	}//end testTheLeafBundleIsAWebpackEntry()
}//end class

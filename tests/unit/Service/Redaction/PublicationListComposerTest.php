<?php

/**
 * Tests for the composed publication list.
 *
 * 🔴 THE ASSERTION THAT MATTERS IS ABOUT WHAT A READER CAN REACH. A list that
 * says it is composed of redacted copies, and carries one original's file id in
 * an entry nobody renders today, publishes that original the first time
 * somebody renders it. So the test does not ask the composer what it thinks it
 * linked: it serialises the whole composed list and asserts that the original's
 * id, name and path are not anywhere in it.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCA\Filinq\Service\Redaction\PublicationListComposer;
use OCA\Filinq\Service\SavedViewReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PublicationListComposer.
 */
class PublicationListComposerTest extends TestCase {

	/**
	 * The saved view.
	 *
	 * @var SavedViewReader&MockObject
	 */
	private SavedViewReader $views;

	/**
	 * The source-to-copy pairing.
	 *
	 * @var AnonymizationLinkReader&MockObject
	 */
	private AnonymizationLinkReader $links;

	/**
	 * The composer under test.
	 *
	 * @var PublicationListComposer
	 */
	private PublicationListComposer $composer;

	/**
	 * Wire the composer over two doubles that answer nothing by default.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->views = $this->getMockBuilder(SavedViewReader::class)
			->disableOriginalConstructor()
			->onlyMethods(['records'])
			->getMock();

		$this->links = $this->getMockBuilder(AnonymizationLinkReader::class)
			->disableOriginalConstructor()
			->onlyMethods(['forSource'])
			->getMock();

		$this->composer = new PublicationListComposer(views: $this->views, links: $this->links);

	}//end setUp()

	/**
	 * A list composes itself over a view whose records are all redacted.
	 *
	 * @return void
	 */
	public function testAPublicationListComposesItself(): void {
		$this->views->method('records')->willReturn(
			[
				['title' => 'Besluit maart', 'fileId' => 11],
				['title' => 'Besluit april', 'fileId' => 12],
			]
		);
		$this->links->method('forSource')->willReturnCallback(
			fn (int $sourceFileId): array => [
				'sourceFileId' => $sourceFileId,
				'anonymizedFileId' => ($sourceFileId + 1000),
				'anonymizedFileName' => 'copy-'.$sourceFileId.'.pdf',
			]
		);

		$list = $this->composer->compose(view: 'woo-published', title: 'Woo-publicatielijst');

		$this->assertSame(2, $list['count']);
		$this->assertSame('woo-published', $list['view']);
		$this->assertSame('Woo-publicatielijst', $list['title']);
		$this->assertNotSame('', $list['composedAt']);
		$this->assertSame([1011, 1012], array_column($list['entries'], 'fileId'));

	}//end testAPublicationListComposesItself()

	/**
	 * The original is nowhere in the composed list.
	 *
	 * @return void
	 */
	public function testTheOriginalIsNotReachableFromTheList(): void {
		$this->views->method('records')->willReturn(
			[['title' => 'Besluit maart', 'fileId' => 7788]]
		);
		$this->links->method('forSource')->willReturn(
			[
				'sourceFileId' => 7788,
				'sourceFileName' => 'besluit-met-namen.pdf',
				'sourceFilePath' => '/Woo/originals/besluit-met-namen.pdf',
				'anonymizedFileId' => 9900,
				'anonymizedFileName' => 'besluit-geredigeerd.pdf',
				'anonymizedFilePath' => '/Woo/redacted/besluit-geredigeerd.pdf',
			]
		);

		$list = $this->composer->compose(view: 'woo-published');
		$serialised = json_encode($list);

		$this->assertStringNotContainsString('besluit-met-namen.pdf', $serialised);
		$this->assertStringNotContainsString('/Woo/originals/', $serialised);
		$this->assertStringNotContainsString('7788', $serialised);
		$this->assertStringContainsString('besluit-geredigeerd.pdf', $serialised);

	}//end testTheOriginalIsNotReachableFromTheList()

	/**
	 * A record with no redacted copy stops the whole list.
	 *
	 * @return void
	 */
	public function testARecordThatIsNotReadyStopsTheList(): void {
		$this->views->method('records')->willReturn(
			[
				['title' => 'Besluit maart', 'fileId' => 11],
				['title' => 'Nog niet geredigeerd', 'fileId' => 12],
			]
		);
		$this->links->method('forSource')->willReturnCallback(
			function (int $sourceFileId): ?array {
				if ($sourceFileId !== 11) {
					return null;
				}

				return ['sourceFileId' => 11, 'anonymizedFileId' => 1011];
			}
		);

		$list = $this->composer->compose(view: 'woo-published');

		$this->assertSame(PublicationListComposer::NOT_READY, $list['refused']);
		$this->assertArrayNotHasKey('entries', $list, 'a partial list is worse than none: it is invisible');
		$this->assertSame('Nog niet geredigeerd', $list['notReady'][0]['record']);

	}//end testARecordThatIsNotReadyStopsTheList()

	/**
	 * A link whose copy id is zero counts as not ready.
	 *
	 * @return void
	 */
	public function testALinkWithoutACopyCountsAsNotReady(): void {
		$this->views->method('records')->willReturn([['title' => 'Besluit', 'fileId' => 11]]);
		$this->links->method('forSource')->willReturn(['sourceFileId' => 11, 'anonymizedFileId' => 0]);

		$list = $this->composer->compose(view: 'woo-published');

		$this->assertSame(PublicationListComposer::NOT_READY, $list['refused']);

	}//end testALinkWithoutACopyCountsAsNotReady()

	/**
	 * A view that does not resolve composes nothing, and says which view.
	 *
	 * @return void
	 */
	public function testAViewThatDoesNotResolveComposesNothing(): void {
		$this->views->method('records')->willReturn(null);

		$list = $this->composer->compose(view: 'gone');

		$this->assertSame(PublicationListComposer::NO_SUCH_VIEW, $list['refused']);
		$this->assertStringContainsString('gone', $list['message']);

	}//end testAViewThatDoesNotResolveComposesNothing()

	/**
	 * An OpenRegister-shaped record is read the same way as a flat one.
	 *
	 * @return void
	 */
	public function testAnOpenRegisterShapedRecordIsRead(): void {
		$this->views->method('records')->willReturn(
			[['@self' => ['id' => 'abc'], 'object' => ['title' => 'Besluit', 'fileId' => 11]]]
		);
		$this->links->method('forSource')->willReturn(['sourceFileId' => 11, 'anonymizedFileId' => 1011]);

		$list = $this->composer->compose(view: 'woo-published');

		$this->assertSame(1, $list['count']);
		$this->assertSame('Besluit', $list['entries'][0]['record']);

	}//end testAnOpenRegisterShapedRecordIsRead()
}//end class

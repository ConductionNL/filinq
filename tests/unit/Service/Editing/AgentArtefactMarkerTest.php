<?php

/**
 * Unit tests for AgentArtefactMarker
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the ADR-088 artefact marker.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AgentArtefactMarkerTest extends TestCase {

	/**
	 * Build a tag double with a fixed id.
	 *
	 * @param string $id The tag id.
	 *
	 * @return ISystemTag The tag.
	 */
	private function tag(string $id = '7'): ISystemTag {
		$tag = $this->createMock(ISystemTag::class);
		$tag->method('getId')->willReturn($id);

		return $tag;

	}//end tag()

	/**
	 * The tag name is the fleet-wide ADR-088 string, untranslated. Hermiq marks
	 * calendar events and contacts with the same one; a per-locale name would
	 * make "show me everything an agent touched" answer differently per user.
	 *
	 * @return void
	 */
	public function testTheTagNameIsTheFleetWideUntranslatedString(): void {
		$this->assertSame('Agent authored', AgentArtefactMarker::TAG_NAME);

	}//end testTheTagNameIsTheFleetWideUntranslatedString()

	/**
	 * An untagged file gets the tag, against the `files` object type.
	 *
	 * @return void
	 */
	public function testAnUntaggedFileIsTagged(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturn($this->tag());

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('haveTag')->willReturn(false);
		$mapper->expects($this->once())->method('assignTags')->with('42', 'files', ['7']);

		$this->assertTrue((new AgentArtefactMarker($manager, $mapper))->mark(42));

	}//end testAnUntaggedFileIsTagged()

	/**
	 * Marking an already-marked file is a no-op reported as such, so a caller
	 * rolling back knows not to remove a tag it did not add.
	 *
	 * @return void
	 */
	public function testMarkingAnAlreadyMarkedFileAddsNothing(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturn($this->tag());

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('haveTag')->willReturn(true);
		$mapper->expects($this->never())->method('assignTags');

		$this->assertFalse((new AgentArtefactMarker($manager, $mapper))->mark(42));

	}//end testMarkingAnAlreadyMarkedFileAddsNothing()

	/**
	 * The tag is created on first use, user-visible and user-assignable — the
	 * point is that a person can see it in Files and filter on it.
	 *
	 * @return void
	 */
	public function testTheTagIsCreatedOnFirstUse(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willThrowException(new TagNotFoundException());
		$manager->expects($this->once())
			->method('createTag')
			->with('Agent authored', true, true)
			->willReturn($this->tag('9'));

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('haveTag')->willReturn(false);
		$mapper->expects($this->once())->method('assignTags')->with('42', 'files', ['9']);

		(new AgentArtefactMarker($manager, $mapper))->mark(42);

	}//end testTheTagIsCreatedOnFirstUse()

	/**
	 * Two concurrent first uses race to create the tag. Losing that race is not
	 * a failure — the tag now exists, which is all the caller wanted.
	 *
	 * @return void
	 */
	public function testLosingTheCreateRaceIsNotAFailure(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturnOnConsecutiveCalls(
			$this->throwException(new TagNotFoundException()),
			$this->tag('9')
		);
		$manager->method('createTag')->willThrowException(new RuntimeException('tag already exists'));

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('haveTag')->willReturn(false);
		$mapper->expects($this->once())->method('assignTags')->with('42', 'files', ['9']);

		$this->assertTrue((new AgentArtefactMarker($manager, $mapper))->mark(42));

	}//end testLosingTheCreateRaceIsNotAFailure()

	/**
	 * A marking failure THROWS rather than returning false. A boolean gets
	 * logged and stepped over, and an unmarked agent artefact reported as a
	 * success is the one outcome nothing downstream re-examines.
	 *
	 * @return void
	 */
	public function testAMarkingFailureThrowsRatherThanReturningFalse(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturn($this->tag());

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->method('haveTag')->willReturn(false);
		$mapper->method('assignTags')->willThrowException(new RuntimeException('database gone'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/could not mark file 42/i');

		(new AgentArtefactMarker($manager, $mapper))->mark(42);

	}//end testAMarkingFailureThrowsRatherThanReturningFalse()

	/**
	 * A tag that can be neither found nor created is also a failure, not a
	 * silent skip.
	 *
	 * @return void
	 */
	public function testAnUnresolvableTagThrows(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willThrowException(new TagNotFoundException());
		$manager->method('createTag')->willThrowException(new RuntimeException('read-only database'));

		$this->expectException(RuntimeException::class);

		(new AgentArtefactMarker($manager, $this->createMock(ISystemTagObjectMapper::class)))->mark(42);

	}//end testAnUnresolvableTagThrows()

	/**
	 * Unmarking never throws: it only ever runs while the caller is already
	 * reporting a failure, and masking that failure would be worse than a stuck
	 * tag.
	 *
	 * @return void
	 */
	public function testUnmarkingNeverThrows(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willThrowException(new RuntimeException('everything is down'));

		(new AgentArtefactMarker($manager, $this->createMock(ISystemTagObjectMapper::class)))->unmark(42);

		$this->addToAssertionCount(1);

	}//end testUnmarkingNeverThrows()

	/**
	 * Unmarking removes exactly the ADR-088 tag from exactly that file.
	 *
	 * @return void
	 */
	public function testUnmarkingRemovesOnlyTheAgentTag(): void {
		$manager = $this->createMock(ISystemTagManager::class);
		$manager->method('getTag')->willReturn($this->tag('7'));

		$mapper = $this->createMock(ISystemTagObjectMapper::class);
		$mapper->expects($this->once())->method('unassignTags')->with('42', 'files', ['7']);

		(new AgentArtefactMarker($manager, $mapper))->unmark(42);

	}//end testUnmarkingRemovesOnlyTheAgentTag()
}//end class

<?php

/**
 * Unit tests for EditSessionService
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
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\Editing\DocumentCodecs;
use OCA\Filinq\Service\Editing\GuardedWriter;
use OCA\Filinq\Service\Editing\PackageCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\PresentationCodec;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\Files\Lock\NoLockProviderException;
use OCP\Files\Lock\OwnerLockedException;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZipArchive;

/**
 * Unit tests for the agent document edit session.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EditSessionServiceTest extends TestCase {

	/**
	 * The acting user.
	 *
	 * @var string
	 */
	private const UID = 'alice';

	/**
	 * The file id under test.
	 *
	 * @var int
	 */
	private const FILE_ID = 4711;

	/**
	 * Bytes written by the file mock, so a test can assert what landed.
	 *
	 * @var string
	 */
	private string $written = '';

	/**
	 * Temporary files to clean up.
	 *
	 * @var array<int, string>
	 */
	private array $spilled = [];

	/**
	 * Collaborators.
	 *
	 * @var MockObject&IRootFolder
	 */
	private $rootFolder;

	/**
	 * The lock manager.
	 *
	 * @var MockObject&ILockManager
	 */
	private $lockManager;

	/**
	 * The artefact marker.
	 *
	 * @var MockObject&AgentArtefactMarker
	 */
	private $marker;

	/**
	 * The refusal gate.
	 *
	 * @var MockObject&DocumentGuard
	 */
	private $guard;

	/**
	 * App configuration.
	 *
	 * @var MockObject&IAppConfig
	 */
	private $appConfig;

	/**
	 * The source file.
	 *
	 * @var MockObject&File
	 */
	private $file;

	/**
	 * Set up a readable, updateable `.docx` in Alice's folder.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->lockManager = $this->createMock(ILockManager::class);
		$this->marker = $this->createMock(AgentArtefactMarker::class);
		$this->guard = $this->createMock(DocumentGuard::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => $default
		);

		$this->lockManager->method('runInScope')->willReturnCallback(
			static function (LockContext $lock, callable $callback): void {
				$callback();
			}
		);

		$this->file = $this->file(bytes: $this->docx(), etag: 'v1');
		$this->attach(node: $this->file);

	}//end setUp()

	/**
	 * Clean up temporary packages.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ($this->spilled as $path) {
			if (is_file($path) === true) {
				unlink($path);
			}
		}

		$this->spilled = [];
		parent::tearDown();

	}//end tearDown()

	/**
	 * A two-paragraph `.docx`.
	 *
	 * @return string The package bytes.
	 */
	private function docx(): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-session-');
		$this->spilled[] = $path;
		unlink($path);

		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		$zip->addFromString(
			'word/document.xml',
			'<w:document xmlns:w="urn:w"><w:body>'
			. '<w:p><w:r><w:t>Original heading</w:t></w:r></w:p>'
			. '<w:p><w:r><w:t>Original body</w:t></w:r></w:p>'
			. '</w:body></w:document>'
		);
		$zip->close();

		return (string)file_get_contents($path);

	}//end docx()

	/**
	 * Build a file mock.
	 *
	 * @param string $bytes The file contents.
	 * @param string $etag The file etag.
	 * @param string $name The file name.
	 * @param int $id The file id.
	 *
	 * @return MockObject&File The file mock.
	 */
	private function file(string $bytes, string $etag, string $name = 'letter.docx', int $id = self::FILE_ID) {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn('/' . self::UID . '/files/' . $name);
		$file->method('getExtension')->willReturn(pathinfo($name, PATHINFO_EXTENSION));
		$file->method('getSize')->willReturn(strlen($bytes));
		$file->method('getContent')->willReturn($bytes);
		$file->method('getEtag')->willReturn($etag);
		$file->method('isUpdateable')->willReturn(true);
		$file->method('putContent')->willReturnCallback(
			function (mixed $data): void {
				$this->written = (string)$data;
			}
		);

		return $file;

	}//end file()

	/**
	 * Put a node behind Alice's user folder.
	 *
	 * @param mixed $node The node the folder resolves the id to.
	 * @param Folder|null $parent The parent folder used for sibling output.
	 *
	 * @return void
	 */
	private function attach(mixed $node, ?Folder $parent = null): void {
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturn($node);
		$userFolder->method('getRelativePath')->willReturn('letter.docx');

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		if ($parent !== null && $node instanceof File) {
			$node->method('getParent')->willReturn($parent);
		}

	}//end attach()

	/**
	 * Build the service under test from the current mocks.
	 *
	 * @return EditSessionService The service.
	 */
	private function service(): EditSessionService {
		return new EditSessionService(
			$this->rootFolder,
			new DocumentCodecs(
				new PackageCodec(),
				new SpreadsheetCodec(new PackagePartIo()),
				new PresentationCodec(new PackagePartIo())
			),
			new GuardedWriter(
				$this->lockManager,
				$this->marker,
				$this->createMock(LoggerInterface::class),
				$this->rootFolder
			),
			$this->guard,
			$this->appConfig
		);

	}//end service()

	/**
	 * The anchor of the first paragraph in the current file.
	 *
	 * @return string The anchor.
	 */
	private function firstAnchor(): string {
		return (new PackageCodec())->readBlocks($this->docx(), 'docx')['blocks'][0]['anchor'];

	}//end firstAnchor()

	/**
	 * One replace edit against the first paragraph.
	 *
	 * @return array<int, array<string, string>> The edit set.
	 */
	private function edit(): array {
		return [['anchor' => $this->firstAnchor(), 'action' => 'replace', 'text' => 'Revised heading']];

	}//end edit()

	/**
	 * A read hands back the anchors AND the version, because an edit needs both
	 * and getting them from separate calls would let them disagree.
	 *
	 * @return void
	 */
	public function testReadReturnsAnchorsAndTheVersionAnEditWillNeed(): void {
		$outline = $this->service()->openForAgent(uid: self::UID, fileId: self::FILE_ID);

		$this->assertSame('v1', $outline['version']);
		$this->assertSame(2, $outline['blockCount']);
		$this->assertFalse($outline['truncated']);
		$this->assertTrue($outline['editable']);
		$this->assertSame('Original heading', $outline['blocks'][0]['text']);
		$this->assertArrayHasKey('anchor', $outline['blocks'][0]);

	}//end testReadReturnsAnchorsAndTheVersionAnEditWillNeed()

	/**
	 * A read returns text, never bytes. A tool that hands a package back to the
	 * model both poisons the prompt and moves document content somewhere with
	 * different access rules.
	 *
	 * @return void
	 */
	public function testReadNeverReturnsPackageBytes(): void {
		$outline = $this->service()->openForAgent(uid: self::UID, fileId: self::FILE_ID);

		$flat = json_encode($outline);

		$this->assertStringNotContainsString('PK', (string)$flat);
		$this->assertStringNotContainsString('w:document', (string)$flat);

	}//end testReadNeverReturnsPackageBytes()

	/**
	 * The happy path: locked, marked, written, unlocked, and the anchors that
	 * were applied are reported back.
	 *
	 * @return void
	 */
	public function testAnEditIsLockedMarkedWrittenAndUnlocked(): void {
		$this->lockManager->expects($this->once())->method('lock');
		$this->lockManager->expects($this->once())->method('unlock');
		$this->marker->expects($this->once())->method('mark')->with(self::FILE_ID)->willReturn(true);

		$result = $this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

		$this->assertSame(EditSessionService::MODE_IN_PLACE, $result['outputMode']);
		$this->assertSame(self::FILE_ID, $result['fileId']);
		$this->assertSame([$this->firstAnchor()], $result['appliedAnchors']);
		$this->assertSame('Agent authored', $result['agentAuthoredTag']);
		$this->assertSame(
			'Revised heading',
			(new PackageCodec())->readBlocks($this->written, 'docx')['blocks'][0]['text'],
			'The bytes that were written must actually carry the edit.'
		);

	}//end testAnEditIsLockedMarkedWrittenAndUnlocked()

	/**
	 * A version that moved between the read and the write REFUSES. This is the
	 * guard that survives when the lock cannot help — a change made outside any
	 * editing session — and merging is not something this codec can do.
	 *
	 * @return void
	 */
	public function testAVersionThatMovedRefusesTheWrite(): void {
		$this->file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/changed since you read it/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'a-stale-version'
		);

	}//end testAVersionThatMovedRefusesTheWrite()

	/**
	 * A refused write still releases the lock. A session that keeps a lock after
	 * failing locks the user out of their own document.
	 *
	 * @return void
	 */
	public function testTheLockIsReleasedEvenWhenTheWriteIsRefused(): void {
		$this->lockManager->expects($this->once())->method('unlock');

		try {
			$this->service()->editForAgent(
				uid: self::UID,
				fileId: self::FILE_ID,
				edits: $this->edit(),
				version: 'a-stale-version'
			);
		} catch (RuntimeException) {
			// Expected; the assertion is the unlock expectation above.
		}

		$this->addToAssertionCount(1);

	}//end testTheLockIsReleasedEvenWhenTheWriteIsRefused()

	/**
	 * An edit with no version is refused outright, which is what forces a read
	 * first and therefore forces the anchors to be current.
	 *
	 * @return void
	 */
	public function testAnEditWithoutAVersionIsRefused(): void {
		$this->lockManager->expects($this->never())->method('lock');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Read the document first/');

		$this->service()->editForAgent(uid: self::UID, fileId: self::FILE_ID, edits: $this->edit(), version: '');

	}//end testAnEditWithoutAVersionIsRefused()

	/**
	 * A document open in an editor holds the lock, and the answer is a refusal
	 * naming the condition — never a poll, a queue, a retry, or a stolen lock.
	 *
	 * @return void
	 */
	public function testAnotherOwnersLockIsARefusalNotAWait(): void {
		$lock = $this->createMock(ILock::class);
		$lock->method('getOwner')->willReturn('richdocuments');

		$this->lockManager->method('lock')->willThrowException(new OwnerLockedException($lock));
		$this->file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/richdocuments/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testAnotherOwnersLockIsARefusalNotAWait()

	/**
	 * An instance with no lock provider still edits, but SAYS the guard was
	 * absent. Degrading silently would leave an operator believing a protection
	 * was in force that never ran.
	 *
	 * @return void
	 */
	public function testAMissingLockProviderDegradesVisibly(): void {
		$this->lockManager->method('lock')->willThrowException(new NoLockProviderException());
		$this->lockManager->expects($this->never())->method('unlock');

		$result = $this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

		$this->assertNotEmpty($result['warnings']);
		$this->assertStringContainsString('lock', strtolower($result['warnings'][0]));

	}//end testAMissingLockProviderDegradesVisibly()

	/**
	 * The mark goes on BEFORE the write, so a file that cannot be marked is
	 * never written. Success on an unmarked agent artefact is the one outcome
	 * nothing downstream re-examines.
	 *
	 * @return void
	 */
	public function testAFileThatCannotBeMarkedIsNotWritten(): void {
		$this->marker->method('mark')->willThrowException(new RuntimeException('tag backend down'));
		$this->file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testAFileThatCannotBeMarkedIsNotWritten()

	/**
	 * And the converse: if the write fails after the mark went on, the mark is
	 * taken off again, so no unchanged file is left claiming an agent wrote it.
	 *
	 * @return void
	 */
	public function testAMarkIsRolledBackWhenTheWriteFails(): void {
		$file = $this->file(bytes: $this->docx(), etag: 'v1');
		$file->method('putContent')->willThrowException(new RuntimeException('disk full'));
		$this->attach(node: $file);

		$this->marker->method('mark')->willReturn(true);
		$this->marker->expects($this->once())->method('unmark')->with(self::FILE_ID);

		$this->expectException(RuntimeException::class);

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testAMarkIsRolledBackWhenTheWriteFails()

	/**
	 * Sibling output writes a new file and leaves the source alone.
	 *
	 * @return void
	 */
	public function testSiblingOutputLeavesTheSourceUntouched(): void {
		$sibling = $this->file(bytes: '', etag: 'v9', name: 'letter (agent edit).docx', id: 5150);

		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturn('letter (agent edit).docx');
		$parent->method('newFile')->willReturn($sibling);

		$file = $this->file(bytes: $this->docx(), etag: 'v1');
		$file->expects($this->never())->method('putContent');
		$this->attach(node: $file, parent: $parent);

		$result = $this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1',
			requestedMode: EditSessionService::MODE_SIBLING
		);

		$this->assertSame(EditSessionService::MODE_SIBLING, $result['outputMode']);
		$this->assertSame(5150, $result['fileId']);

	}//end testSiblingOutputLeavesTheSourceUntouched()

	/**
	 * Configuration sets the CEILING. An agent asking for in-place against a
	 * sibling ceiling gets sibling — an agent that can widen its own blast
	 * radius has no blast radius.
	 *
	 * @return void
	 */
	public function testAnAgentCannotWidenTheConfiguredOutputMode(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')->willReturn(EditSessionService::MODE_SIBLING);

		$sibling = $this->file(bytes: '', etag: 'v9', name: 'letter (agent edit).docx', id: 5150);
		$parent = $this->createMock(Folder::class);
		$parent->method('getNonExistingName')->willReturn('letter (agent edit).docx');
		$parent->method('newFile')->willReturn($sibling);

		$file = $this->file(bytes: $this->docx(), etag: 'v1');
		$file->expects($this->never())->method('putContent');
		$this->attach(node: $file, parent: $parent);

		$result = $this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1',
			requestedMode: EditSessionService::MODE_IN_PLACE
		);

		$this->assertSame(EditSessionService::MODE_SIBLING, $result['outputMode']);

	}//end testAnAgentCannotWidenTheConfiguredOutputMode()

	/**
	 * An unknown output mode is refused rather than silently defaulted.
	 *
	 * @return void
	 */
	public function testAnUnknownOutputModeIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Unknown output mode/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1',
			requestedMode: 'overwriteEverything'
		);

	}//end testAnUnknownOutputModeIsRefused()

	/**
	 * A document under a live signing request is refused before the lock is
	 * taken, so a refusal never disturbs the file it declines to touch.
	 *
	 * @return void
	 */
	public function testADocumentUnderSignatureIsRefusedBeforeAnythingIsTouched(): void {
		$this->guard->method('signatureRefusal')->willReturn('This document is part of a signing request.');
		$this->lockManager->expects($this->never())->method('lock');
		$this->file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/signing request/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testADocumentUnderSignatureIsRefusedBeforeAnythingIsTouched()

	/**
	 * Anonymisation output is refused: re-editing a redacted document risks
	 * re-identification, invisibly to whoever relies on the redaction.
	 *
	 * @return void
	 */
	public function testAnonymisationOutputIsRefused(): void {
		$this->guard->method('anonymisationRefusal')->willReturn('This document is anonymisation output.');
		$this->file->expects($this->never())->method('putContent');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/anonymisation output/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testAnonymisationOutputIsRefused()

	/**
	 * A PDF is not editable here, and the refusal names what is — otherwise the
	 * agent's only move is to try again.
	 *
	 * @return void
	 */
	public function testAnUneditableFormatRefusalNamesTheEditableOnes(): void {
		$this->attach(node: $this->file(bytes: '%PDF-1.7', etag: 'v1', name: 'decision.pdf'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/docx, odt/');

		$this->service()->openForAgent(uid: self::UID, fileId: self::FILE_ID);

	}//end testAnUneditableFormatRefusalNamesTheEditableOnes()

	/**
	 * A read-only file is refused for in-place output rather than failing deep
	 * inside the write.
	 *
	 * @return void
	 */
	public function testAReadOnlyFileIsRefusedForInPlaceOutput(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(self::FILE_ID);
		$file->method('getName')->willReturn('shared.docx');
		$file->method('getExtension')->willReturn('docx');
		$file->method('getSize')->willReturn(100);
		$file->method('getEtag')->willReturn('v1');
		$file->method('isUpdateable')->willReturn(false);
		$file->expects($this->never())->method('putContent');
		$this->attach(node: $file);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/permission/');

		$this->service()->editForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: $this->edit(),
			version: 'v1'
		);

	}//end testAReadOnlyFileIsRefusedForInPlaceOutput()

	/**
	 * A file id the acting user cannot reach does not resolve at all — the IDOR
	 * boundary is the user folder, not a permission check after the fact.
	 *
	 * @return void
	 */
	public function testAFileOutsideTheUsersFolderIsNotFound(): void {
		$this->attach(node: null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/was not found in your files/');

		$this->service()->openForAgent(uid: self::UID, fileId: self::FILE_ID);

	}//end testAFileOutsideTheUsersFolderIsNotFound()

	/**
	 * No session, no operation. There is no service user and no impersonation.
	 *
	 * @return void
	 */
	public function testAnEmptyActingUserIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/signed-in user/');

		$this->service()->openForAgent(uid: '  ', fileId: self::FILE_ID);

	}//end testAnEmptyActingUserIsRefused()

	/**
	 * An oversized package is refused before it is loaded into memory.
	 *
	 * @return void
	 */
	public function testAnOversizedDocumentIsRefused(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(self::FILE_ID);
		$file->method('getName')->willReturn('huge.docx');
		$file->method('getExtension')->willReturn('docx');
		$file->method('getSize')->willReturn(999999999);
		$file->expects($this->never())->method('getContent');
		$this->attach(node: $file);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/larger than/');

		$this->service()->openForAgent(uid: self::UID, fileId: self::FILE_ID);

	}//end testAnOversizedDocumentIsRefused()
}//end class

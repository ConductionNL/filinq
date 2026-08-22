<?php

/**
 * Unit tests for the spreadsheet, presentation, metadata and chart agent surfaces.
 *
 * 🔴 Why this file exists: seven public entry points of `EditSessionService`
 * had NO test reaching them at all — every spreadsheet, presentation, metadata
 * and chart tool an agent can call. The text surface was well covered, and the
 * suite's green tick said nothing whatever about the other four.
 *
 * The property they are tested for is the one they share and the one a
 * per-format write path is most likely to lose: **every agent write goes
 * through the same session** — same version precondition, same guard refusals,
 * same lock, same agent-authored marking. A format-specific shortcut around any
 * of those is a document changed without the accountability the text path has.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\DocumentCodecs;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\Editing\GuardedWriter;
use OCA\Filinq\Service\Editing\PackageCodec;
use OCA\Filinq\Service\Editing\PackagePartIo;
use OCA\Filinq\Service\Editing\PresentationCodec;
use OCA\Filinq\Service\Editing\SpreadsheetCodec;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use ZipArchive;

/**
 * The non-text agent surfaces of the edit session.
 *
 * @category Test
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
class EditSessionAgentSurfacesTest extends TestCase {

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
	private const FILE_ID = 8123;

	/**
	 * Temporary packages to clean up.
	 *
	 * @var array<int, string>
	 */
	private array $spilled = [];

	/**
	 * Bytes the file mock received, so a test can assert what landed.
	 *
	 * @var string
	 */
	private string $written = '';

	/**
	 * The user's folder root.
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
	 * Build the shared collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->lockManager = $this->createMock(ILockManager::class);
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

	}//end setUp()

	/**
	 * Remove temporary packages.
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
	 * Build a package from part name => contents.
	 *
	 * @param array<string, string> $parts The package parts.
	 *
	 * @return string The package bytes.
	 */
	private function package(array $parts): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-surface-');
		$this->spilled[] = $path;
		unlink($path);

		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::CREATE);
		foreach ($parts as $name => $contents) {
			$zip->addFromString($name, $contents);
		}

		$zip->close();

		return (string)file_get_contents($path);

	}//end package()

	/**
	 * A one-sheet `.ods` with a label and a rate.
	 *
	 * @return string The package bytes.
	 */
	private function ods(): string {
		return $this->package([
			'content.xml' => '<office:document-content>'
				. '<table:table table:name="Sheet1">'
				. '<table:table-row>'
				. '<table:table-cell office:value-type="string"><text:p>Rate</text:p></table:table-cell>'
				. '</table:table-row>'
				. '<table:table-row>'
				. '<table:table-cell office:value-type="float" office:value="95"><text:p>95</text:p></table:table-cell>'
				. '</table:table-row>'
				. '</table:table>'
				. '</office:document-content>',
		]);

	}//end ods()

	/**
	 * A one-slide `.odp` with a title frame.
	 *
	 * @return string The package bytes.
	 */
	private function odp(): string {
		return $this->package([
			'content.xml' => '<office:document-content><office:presentation>'
				. '<draw:page draw:name="Intro">'
				. '<draw:frame draw:name="Title"><draw:text-box><text:p>Welcome</text:p></draw:text-box></draw:frame>'
				. '</draw:page>'
				. '</office:presentation></office:document-content>',
		]);

	}//end odp()

	/**
	 * A `.docx` carrying a title in its core properties.
	 *
	 * @return string The package bytes.
	 */
	private function docx(): string {
		return $this->package([
			// A real OOXML package declares its part types, and the chart codec
			// needs that declaration to register the parts it adds. Omitting it
			// from a fixture makes a working codec look broken.
			'[Content_Types].xml' => '<?xml version="1.0"?>'
				. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
				. '<Default Extension="xml" ContentType="application/xml"/>'
				. '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-'
				. 'officedocument.wordprocessingml.document.main+xml"/>'
				. '</Types>',
			'word/document.xml' => '<w:document xmlns:w="urn:w"><w:body>'
				. '<w:p><w:r><w:t>Original heading</w:t></w:r></w:p>'
				. '</w:body></w:document>',
			'docProps/core.xml' => '<?xml version="1.0"?>'
				. '<cp:coreProperties'
				. ' xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
				. ' xmlns:dc="http://purl.org/dc/elements/1.1/">'
				. '<dc:title>Quarterly report</dc:title>'
				. '</cp:coreProperties>',
		]);

	}//end docx()

	/**
	 * Read one part back out of package bytes.
	 *
	 * @param string $package The package bytes.
	 * @param string $part    The part name.
	 *
	 * @return string The part's contents.
	 */
	private function partOf(string $package, string $part): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-read-');
		$this->spilled[] = $path;
		file_put_contents($path, $package);

		$zip = new ZipArchive();
		$this->assertTrue($zip->open($path) === true, 'the written bytes must be a readable package');
		$contents = $zip->getFromName($part);
		$zip->close();

		$this->assertNotFalse($contents, sprintf('the package must still carry "%s"', $part));

		return (string)$contents;

	}//end partOf()

	/**
	 * Put a file of the given name and bytes behind Alice's folder.
	 *
	 * @param string $name  The file name, whose extension selects the codec.
	 * @param string $bytes The package bytes.
	 *
	 * @return MockObject&File The file mock.
	 */
	private function attach(string $name, string $bytes) {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(self::FILE_ID);
		$file->method('getName')->willReturn($name);
		$file->method('getPath')->willReturn('/' . self::UID . '/files/' . $name);
		$file->method('getExtension')->willReturn(pathinfo($name, PATHINFO_EXTENSION));
		$file->method('getSize')->willReturn(strlen($bytes));
		$file->method('getContent')->willReturn($bytes);
		$file->method('getEtag')->willReturn('v1');
		$file->method('isUpdateable')->willReturn(true);
		$file->method('putContent')->willReturnCallback(
			function (mixed $data): void {
				$this->written = (string)$data;
			}
		);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')->willReturn($file);
		$userFolder->method('getRelativePath')->willReturn($name);

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);

		return $file;

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
				$this->createMock(AgentArtefactMarker::class),
				$this->createMock(LoggerInterface::class),
				$this->rootFolder
			),
			$this->guard,
			$this->appConfig
		);

	}//end service()

	/**
	 * A spreadsheet read hands back addressed cells and the version an edit needs.
	 *
	 * @return void
	 */
	public function testSpreadsheetReadReturnsAddressedCellsAndAVersion(): void {
		$this->attach('rates.ods', $this->ods());

		$outline = $this->service()->openSpreadsheetForAgent(uid: self::UID, fileId: self::FILE_ID);

		$this->assertSame('v1', $outline['version']);
		$this->assertSame(2, $outline['cellCount']);
		$this->assertTrue($outline['editable']);
		$this->assertContains('Sheet1!A1', array_column($outline['cells'], 'cell'));

	}//end testSpreadsheetReadReturnsAddressedCellsAndAVersion()

	/**
	 * 🔴 A spreadsheet read never returns package bytes.
	 *
	 * The same rule the text surface has, asserted separately because it is
	 * enforced per read: a tool that hands a package back to the model both
	 * poisons the prompt and moves document content somewhere with different
	 * access rules.
	 *
	 * @return void
	 */
	public function testSpreadsheetReadNeverReturnsPackageBytes(): void {
		$this->attach('rates.ods', $this->ods());

		$flat = (string)json_encode($this->service()->openSpreadsheetForAgent(uid: self::UID, fileId: self::FILE_ID));

		$this->assertStringNotContainsString('PK', $flat);
		$this->assertStringNotContainsString('office:document-content', $flat);

	}//end testSpreadsheetReadNeverReturnsPackageBytes()

	/**
	 * A cell write reports which dependent cells went stale.
	 *
	 * ⚠️ The list is on the RESULT, not merely logged. A dependent whose cached
	 * value no longer follows from its inputs is a number that looks current and
	 * is not, and only the caller can decide whether that matters.
	 *
	 * @return void
	 */
	public function testACellWriteReportsStaleDependentsOnTheResult(): void {
		$this->attach('rates.ods', $this->ods());

		$result = $this->service()->editSpreadsheetForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: [['cell' => 'Sheet1!A2', 'value' => '110']],
			version: 'v1'
		);

		$this->assertArrayHasKey('staleDependents', $result);
		$this->assertArrayHasKey('erroredDependents', $result);
		$this->assertNotSame('', $this->written, 'the write must actually reach the file');

	}//end testACellWriteReportsStaleDependentsOnTheResult()

	/**
	 * 🔴 A spreadsheet write without a version is refused.
	 *
	 * The precondition is what stops one writer silently overwriting another,
	 * and it must hold on every surface — not only the one it was written for.
	 *
	 * @return void
	 */
	public function testASpreadsheetWriteWithoutAVersionIsRefused(): void {
		$this->attach('rates.ods', $this->ods());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/version is required/i');

		$this->service()->editSpreadsheetForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: [['cell' => 'Sheet1!A2', 'value' => '110']],
			version: ''
		);

	}//end testASpreadsheetWriteWithoutAVersionIsRefused()

	/**
	 * 🔴 A guarded spreadsheet is refused, and nothing is written.
	 *
	 * The control that the shared session really is shared: the guard lives in
	 * `prepareWrite`, so a format-specific write path that skipped it would make
	 * a signed document editable through the newest tool only.
	 *
	 * @return void
	 */
	public function testAGuardedSpreadsheetIsRefusedWithoutWriting(): void {
		$this->attach('rates.ods', $this->ods());
		$this->guard->method('signatureRefusal')->willReturn('This document is out for signature.');

		try {
			$this->service()->editSpreadsheetForAgent(
				uid: self::UID,
				fileId: self::FILE_ID,
				edits: [['cell' => 'Sheet1!A2', 'value' => '110']],
				version: 'v1'
			);
			$this->fail('a document out for signature must not be editable');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('signature', $e->getMessage());
			$this->assertSame('', $this->written, 'a refusal must leave the file untouched');
		}

	}//end testAGuardedSpreadsheetIsRefusedWithoutWriting()

	/**
	 * A `.docx` is not a spreadsheet, and the refusal names the formats that are.
	 *
	 * @return void
	 */
	public function testTheSpreadsheetSurfaceRefusesADocumentAndNamesTheFormats(): void {
		$this->attach('letter.docx', $this->docx());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/ods/');

		$this->service()->openSpreadsheetForAgent(uid: self::UID, fileId: self::FILE_ID);

	}//end testTheSpreadsheetSurfaceRefusesADocumentAndNamesTheFormats()

	/**
	 * A presentation read hands back shapes addressed by slide and shape.
	 *
	 * @return void
	 */
	public function testPresentationReadReturnsAddressedShapes(): void {
		$this->attach('deck.odp', $this->odp());

		$outline = $this->service()->openPresentationForAgent(uid: self::UID, fileId: self::FILE_ID);

		$this->assertSame('v1', $outline['version']);
		$this->assertSame(1, $outline['shapeCount']);
		$this->assertSame('Intro', $outline['shapes'][0]['slide']);
		$this->assertSame('Welcome', $outline['shapes'][0]['text']);

	}//end testPresentationReadReturnsAddressedShapes()

	/**
	 * A shape edit reaches the file.
	 *
	 * @return void
	 */
	public function testAShapeEditReachesTheFile(): void {
		$this->attach('deck.odp', $this->odp());

		$this->service()->editPresentationForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: [['slide' => 'Intro', 'shape' => 'Title', 'text' => 'Welcome back']],
			version: 'v1'
		);

		$this->assertNotSame('', $this->written, 'the write must actually reach the file');

	}//end testAShapeEditReachesTheFile()

	/**
	 * 🔴 A presentation write without a version is refused too.
	 *
	 * @return void
	 */
	public function testAPresentationWriteWithoutAVersionIsRefused(): void {
		$this->attach('deck.odp', $this->odp());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/version is required/i');

		$this->service()->editPresentationForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			edits: [['slide' => 'Intro', 'shape' => 'Title', 'text' => 'Welcome back']],
			version: ''
		);

	}//end testAPresentationWriteWithoutAVersionIsRefused()

	/**
	 * Metadata is read from the package, not invented from the file name.
	 *
	 * @return void
	 */
	public function testMetadataIsReadFromThePackage(): void {
		$this->attach('letter.docx', $this->docx());

		$read = $this->service()->readMetadataForAgent(uid: self::UID, fileId: self::FILE_ID);

		$this->assertSame('letter.docx', $read['name']);
		$this->assertSame('v1', $read['version']);
		$this->assertTrue($read['editable']);
		$this->assertSame('Quarterly report', $read['metadata']['title']);

	}//end testMetadataIsReadFromThePackage()

	/**
	 * A metadata write goes through the same session, and lands.
	 *
	 * @return void
	 */
	public function testAMetadataWriteLandsInThePackage(): void {
		$this->attach('letter.docx', $this->docx());

		$this->service()->setMetadataForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			values: ['title' => 'Revised quarterly report'],
			version: 'v1'
		);

		// ⚠️ Read the PART back, not the package bytes. A package is compressed,
		// so a `assertStringContainsString` against the raw bytes fails even
		// when the write was perfect — and would pass for a package that merely
		// happened to store the part uncompressed. Only unpacking measures it.
		$this->assertStringContainsString(
			'Revised quarterly report',
			$this->partOf(package: $this->written, part: 'docProps/core.xml')
		);

	}//end testAMetadataWriteLandsInThePackage()

	/**
	 * 🔴 A metadata write is subject to the guard, exactly like a body edit.
	 *
	 * Metadata is a smaller change than a paragraph rewrite, not a less
	 * accountable one.
	 *
	 * @return void
	 */
	public function testAGuardedDocumentRefusesAMetadataWrite(): void {
		$this->attach('letter.docx', $this->docx());
		$this->guard->method('formatRefusal')->willReturn('Macro-bearing packages are not edited.');

		try {
			$this->service()->setMetadataForAgent(
				uid: self::UID,
				fileId: self::FILE_ID,
				values: ['title' => 'Revised quarterly report'],
				version: 'v1'
			);
			$this->fail('a guarded document must refuse a metadata write');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('Macro-bearing', $e->getMessage());
			$this->assertSame('', $this->written);
		}

	}//end testAGuardedDocumentRefusesAMetadataWrite()

	/**
	 * An unknown output mode is refused by name.
	 *
	 * @return void
	 */
	public function testAnUnknownOutputModeIsRefusedByName(): void {
		$this->attach('letter.docx', $this->docx());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/Unknown output mode "sideways"/');

		$this->service()->setMetadataForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			values: ['title' => 'Anything'],
			version: 'v1',
			requestedMode: 'sideways'
		);

	}//end testAnUnknownOutputModeIsRefusedByName()

	/**
	 * A chart embed runs through the same session and writes the package.
	 *
	 * A chart adds package PARTS rather than rewriting one, which makes the
	 * version precondition more important rather than less: a half-applied
	 * multi-part write is a document no suite will open.
	 *
	 * @return void
	 */
	public function testAChartEmbedWritesThroughTheSameSession(): void {
		$this->attach('letter.docx', $this->docx());

		$this->service()->embedChartForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			chart: [
				'type' => 'bar',
				'title' => 'Revenue',
				'categories' => ['Q1', 'Q2'],
				'series' => [['name' => 'Revenue', 'values' => [10, 20]]],
			],
			version: 'v1'
		);

		$this->assertNotSame('', $this->written, 'the chart write must reach the file');

	}//end testAChartEmbedWritesThroughTheSameSession()

	/**
	 * 🔴 A chart embed without a version is refused, like every other write.
	 *
	 * @return void
	 */
	public function testAChartEmbedWithoutAVersionIsRefused(): void {
		$this->attach('letter.docx', $this->docx());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/version is required/i');

		$this->service()->embedChartForAgent(
			uid: self::UID,
			fileId: self::FILE_ID,
			chart: ['type' => 'bar', 'categories' => ['Q1'], 'series' => [['name' => 'R', 'values' => [1]]]],
			version: ''
		);

	}//end testAChartEmbedWithoutAVersionIsRefused()

}//end class

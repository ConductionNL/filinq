<?php

/**
 * Unit tests for DocumentAgentService and Filinq's MCP tool surface
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

use OCA\Filinq\Mcp\FilinqScannableServices;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\DocumentAgentService;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfConversionService;
use OCA\OpenRegister\Mcp\Attribute\McpTool;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Unit tests for the agent-facing document tools.
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
 */
class DocumentAgentServiceTest extends TestCase {

	/**
	 * The edit session.
	 *
	 * @var MockObject&EditSessionService
	 */
	private $editSession;

	/**
	 * The conversion cascade.
	 *
	 * @var MockObject&PdfConversionService
	 */
	private $pdfConversion;

	/**
	 * The artefact marker.
	 *
	 * @var MockObject&AgentArtefactMarker
	 */
	private $marker;

	/**
	 * The generated-document logger.
	 *
	 * @var MockObject&GeneratedDocumentLogger
	 */
	private $documentLogger;

	/**
	 * The root folder.
	 *
	 * @var MockObject&IRootFolder
	 */
	private $rootFolder;

	/**
	 * The user session.
	 *
	 * @var MockObject&IUserSession
	 */
	private $userSession;

	/**
	 * Set up a signed-in Alice with an empty folder.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->editSession = $this->createMock(EditSessionService::class);
		$this->pdfConversion = $this->createMock(PdfConversionService::class);
		$this->marker = $this->createMock(AgentArtefactMarker::class);
		$this->documentLogger = $this->createMock(GeneratedDocumentLogger::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn($user);

	}//end setUp()

	/**
	 * Put a node behind Alice's user folder.
	 *
	 * @param mixed $node The node.
	 *
	 * @return void
	 */
	private function attach(mixed $node): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getFirstNodeById')->willReturn($node);
		$folder->method('getRelativePath')->willReturn('output.pdf');

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturn($folder);

	}//end attach()

	/**
	 * Build the service under test.
	 *
	 * @return DocumentAgentService The service.
	 */
	private function service(): DocumentAgentService {
		return new DocumentAgentService(
			$this->editSession,
			$this->pdfConversion,
			$this->marker,
			$this->documentLogger,
			$this->rootFolder,
			$this->userSession,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * Read the `#[McpTool]` attribute off a method.
	 *
	 * @param string $class The class name.
	 * @param string $method The method name.
	 *
	 * @return McpTool|null The attribute instance.
	 */
	private function toolAttribute(string $class, string $method): ?McpTool {
		$attributes = (new ReflectionMethod($class, $method))->getAttributes(McpTool::class);
		if ($attributes === []) {
			return null;
		}

		return $attributes[0]->newInstance();
	}//end toolAttribute()

	/**
	 * The three document tools exist, are named as the spec names them, and
	 * carry hints that MATCH what they do. Hints are advisory UX metadata, so
	 * the risk is not that a wrong hint unlocks something — it is that the grant
	 * editor and the oversight log describe a write as a read.
	 *
	 * @return void
	 */
	public function testTheDocumentToolsDeclareHintsThatMatchWhatTheyDo(): void {
		$read = $this->toolAttribute(DocumentAgentService::class, 'readDocument');
		$edit = $this->toolAttribute(DocumentAgentService::class, 'editDocument');
		$convert = $this->toolAttribute(DocumentAgentService::class, 'convertDocumentToPdf');

		$this->assertSame('readDocument', $read?->name);
		$this->assertTrue($read?->readOnlyHint);
		$this->assertSame('read', $read?->scope);

		$this->assertSame('editDocument', $edit?->name);
		$this->assertFalse($edit?->readOnlyHint);
		$this->assertTrue($edit?->destructiveHint, 'An in-place write to a user document is destructive.');
		$this->assertFalse($edit?->idempotentHint, 'Anchors are spent by the edit, so a repeat is not a no-op.');
		$this->assertSame('update', $edit?->scope);

		$this->assertSame('convertDocumentToPdf', $convert?->name);
		$this->assertFalse($convert?->destructiveHint, 'Conversion writes a new file; the source is untouched.');
		$this->assertSame('create', $convert?->scope);

	}//end testTheDocumentToolsDeclareHintsThatMatchWhatTheyDo()

	/**
	 * Every tool description tells the model the ORDER: read first, then edit.
	 * Without that, the model's first move is an edit with invented anchors.
	 *
	 * @return void
	 */
	public function testTheEditDescriptionTellsTheModelToReadFirst(): void {
		$edit = $this->toolAttribute(DocumentAgentService::class, 'editDocument');

		$this->assertStringContainsString('readDocument first', (string)$edit?->description);
		$this->assertStringContainsString('Refuses', (string)$edit?->description);

	}//end testTheEditDescriptionTellsTheModelToReadFirst()

	/**
	 * The correspondence generator is exposed; the BATCH variant is not. A
	 * mail-merge over N recipients an agent can trigger is a spam and
	 * exfiltration primitive.
	 *
	 * @return void
	 */
	public function testGenerationIsExposedButTheBatchVariantIsNot(): void {
		$generate = $this->toolAttribute(CorrespondenceService::class, 'generate');

		$this->assertSame('generateCorrespondence', $generate?->name);
		$this->assertSame('create', $generate?->scope);
		$this->assertNull(
			$this->toolAttribute(CorrespondenceService::class, 'generateBatch'),
			'generateBatch() must never carry an #[McpTool] attribute.'
		);

	}//end testGenerationIsExposedButTheBatchVariantIsNot()

	/**
	 * No signing service is scannable, and none carries a tool attribute.
	 * Applying an electronic signature has legal effect and stays a deliberate
	 * human action.
	 *
	 * @return void
	 */
	public function testNoSigningServiceIsReachableByAnAgent(): void {
		$scannable = (new FilinqScannableServices())->getScannableServiceClasses();

		foreach ($scannable as $class) {
			$this->assertStringNotContainsString('Signing', $class);
		}

		$signingSources = glob(__DIR__ . '/../../../../lib/Service/Signing*.php');
		$signingSources = array_merge(($signingSources === false ? [] : $signingSources), (glob(__DIR__ . '/../../../../lib/Service/Signing/*.php') ?: []));

		$this->assertNotEmpty($signingSources, 'The signing sources must be found, or this test proves nothing.');

		foreach ($signingSources as $path) {
			$this->assertStringNotContainsString(
				'#[McpTool',
				(string)file_get_contents($path),
				basename($path) . ' must not expose an MCP tool.'
			);
		}

	}//end testNoSigningServiceIsReachableByAnAgent()

	/**
	 * The scannable list names exactly the classes that carry attributes —
	 * a class with tools but no opt-in is invisible, and an opt-in without
	 * tools is dead weight.
	 *
	 * @return void
	 */
	public function testEveryScannableClassActuallyCarriesTools(): void {
		$scannable = (new FilinqScannableServices())->getScannableServiceClasses();

		$this->assertContains(CorrespondenceService::class, $scannable);
		$this->assertContains(DocumentAgentService::class, $scannable);

		foreach ($scannable as $class) {
			$tools = 0;
			foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$tools += count($method->getAttributes(McpTool::class));
			}

			$this->assertGreaterThan(0, $tools, $class . ' is opted in but declares no tools.');
		}

	}//end testEveryScannableClassActuallyCarriesTools()

	/**
	 * A read delegates to the session under the SESSION's user, not a caller-
	 * supplied one. There is no service user and no impersonation.
	 *
	 * @return void
	 */
	public function testAReadRunsAsTheSignedInUser(): void {
		$this->editSession->expects($this->once())
			->method('openForAgent')
			->with('alice', 99)
			->willReturn(['fileId' => 99, 'blocks' => []]);

		$this->assertSame(99, $this->service()->readDocument(99)['fileId']);

	}//end testAReadRunsAsTheSignedInUser()

	/**
	 * With no signed-in user there is no operation.
	 *
	 * @return void
	 */
	public function testWithoutASignedInUserThereIsNoOperation(): void {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn(null);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/signed-in user/');

		$this->service()->readDocument(99);

	}//end testWithoutASignedInUserThereIsNoOperation()

	/**
	 * An edit returns an `artefact` descriptor. Hermiq lifts exactly `type` and
	 * `id` out of it into the run's trace, so the oversight record can say WHAT
	 * a successful `editDocument` produced instead of only that it succeeded.
	 *
	 * @return void
	 */
	public function testAnEditReturnsTheArtefactDescriptorHermiqRecords(): void {
		$this->editSession->method('editForAgent')->willReturn(
			[
				'fileId' => 4711,
				'name' => 'letter.docx',
				'path' => 'letter.docx',
				'outputMode' => 'inPlace',
				'appliedAnchors' => ['babc12345-1'],
				'version' => 'v2',
				'warnings' => [],
			]
		);

		$result = $this->service()->editDocument(4711, [['anchor' => 'babc12345-1', 'text' => 'x']], 'v1');

		$this->assertSame(['type' => 'file', 'id' => '4711'], $result['artefact']);

	}//end testAnEditReturnsTheArtefactDescriptorHermiqRecords()

	/**
	 * No tool result carries document bytes. Responses are ids, metadata and
	 * status — the record stays followable without becoming a second copy of
	 * the document.
	 *
	 * @return void
	 */
	public function testNoToolResultCarriesDocumentContent(): void {
		$this->editSession->method('editForAgent')->willReturn(
			[
				'fileId' => 4711,
				'name' => 'letter.docx',
				'path' => 'letter.docx',
				'outputMode' => 'inPlace',
				'appliedAnchors' => [],
				'version' => 'v2',
				'warnings' => [],
			]
		);

		$result = $this->service()->editDocument(4711, [['anchor' => 'b1-1', 'text' => 'x']], 'v1');

		$this->assertArrayNotHasKey('content', $result);
		$this->assertArrayNotHasKey('bytes', $result);
		$this->assertStringNotContainsString('PK', (string)json_encode($result));

	}//end testNoToolResultCarriesDocumentContent()

	/**
	 * An empty `outputMode` argument means "use the configured mode" rather
	 * than an unknown-mode refusal.
	 *
	 * @return void
	 */
	public function testAnOmittedOutputModeMeansTheConfiguredOne(): void {
		$this->editSession->expects($this->once())
			->method('editForAgent')
			->with('alice', 4711, $this->anything(), 'v1', null)
			->willReturn(
				[
					'fileId' => 4711,
					'name' => 'letter.docx',
					'path' => 'letter.docx',
					'outputMode' => 'inPlace',
					'appliedAnchors' => [],
					'version' => 'v2',
					'warnings' => [],
				]
			);

		$this->service()->editDocument(4711, [['anchor' => 'b1-1', 'text' => 'x']], 'v1');

	}//end testAnOmittedOutputModeMeansTheConfiguredOne()

	/**
	 * Conversion reports WHICH backend produced the PDF. An office-app
	 * conversion and the built-in fallback differ visibly in fidelity and are
	 * otherwise indistinguishable to anyone reading the log afterwards.
	 *
	 * @return void
	 */
	public function testConversionReportsTheBackendThatClaimedIt(): void {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('letter.docx');
		$this->attach(node: $source);

		$pdf = $this->createMock(File::class);
		$pdf->method('getId')->willReturn(5150);
		$pdf->method('getName')->willReturn('letter.pdf');
		$pdf->method('getPath')->willReturn('/alice/files/letter.pdf');

		$this->pdfConversion->method('convertToPdfReporting')->willReturn(['file' => $pdf, 'backend' => 'OfficeApp']);
		$this->marker->expects($this->once())->method('mark')->with(5150);

		$result = $this->service()->convertDocumentToPdf(4711);

		$this->assertSame('OfficeApp', $result['backend']);
		$this->assertSame(5150, $result['fileId']);
		$this->assertSame(['type' => 'file', 'id' => '5150'], $result['artefact']);

	}//end testConversionReportsTheBackendThatClaimedIt()

	/**
	 * A cascade that no backend claims refuses and names the file, rather than
	 * returning a lower-fidelity result without saying so.
	 *
	 * @return void
	 */
	public function testAnUnconvertibleDocumentIsRefusedByName(): void {
		$source = $this->createMock(File::class);
		$source->method('getName')->willReturn('drawing.vsdx');
		$this->attach(node: $source);

		$this->pdfConversion->method('convertToPdfReporting')
			->willThrowException(new RuntimeException('no backend claimed it'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/drawing\.vsdx/');

		$this->service()->convertDocumentToPdf(4711);

	}//end testAnUnconvertibleDocumentIsRefusedByName()

	/**
	 * A failure to write the audit row does NOT fail the operation: the file is
	 * already written and already tagged, and throwing here would report a
	 * failure that did not happen.
	 *
	 * @return void
	 */
	public function testAFailedAuditRowDoesNotFailACompletedEdit(): void {
		$this->editSession->method('editForAgent')->willReturn(
			[
				'fileId' => 4711,
				'name' => 'letter.docx',
				'path' => 'letter.docx',
				'outputMode' => 'inPlace',
				'appliedAnchors' => [],
				'version' => 'v2',
				'warnings' => [],
			]
		);
		$this->documentLogger->method('log')->willThrowException(new RuntimeException('register down'));

		$result = $this->service()->editDocument(4711, [['anchor' => 'b1-1', 'text' => 'x']], 'v1');

		$this->assertSame(4711, $result['fileId']);

	}//end testAFailedAuditRowDoesNotFailACompletedEdit()
}//end class

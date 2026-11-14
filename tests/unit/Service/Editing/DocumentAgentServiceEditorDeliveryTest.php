<?php

/**
 * Unit tests for agent edits routed through an open editor
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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\DocumentAgentService;
use OCA\Filinq\Service\Editing\EditSessionService;
use OCA\Filinq\Service\Editing\EditorSessionProbe;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Support\AgentArtefacts;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An open document is edited through its editor; a closed one keeps the direct path.
 *
 * @covers \OCA\Filinq\Service\Editing\DocumentAgentService
 * @uses   \OCA\Filinq\Service\Editing\EditorEditPlan
 * @uses   \OCA\Filinq\Service\Editing\AgentFileRequest
 * @uses   \OCA\Filinq\Service\Support\AgentArtefacts
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 *
 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-an-agent-edit-must-reach-a-document-that-is-open-for-editing
 */
class DocumentAgentServiceEditorDeliveryTest extends TestCase {

	/**
	 * The edit session.
	 *
	 * @var EditSessionService&MockObject
	 */
	private EditSessionService&MockObject $editSession;

	/**
	 * The audit logger.
	 *
	 * @var GeneratedDocumentLogger&MockObject
	 */
	private GeneratedDocumentLogger&MockObject $documentLogger;

	/**
	 * The editor probe.
	 *
	 * @var EditorSessionProbe&MockObject
	 */
	private EditorSessionProbe&MockObject $editors;

	/**
	 * Fresh doubles, with the document read at version v1.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->editSession = $this->createMock(EditSessionService::class);
		$this->editSession->method('openForAgent')->with('alice', 12)->willReturn(
			[
				'fileId' => 12,
				'name' => 'subsidiebesluit.docx',
				'path' => 'Besluiten/subsidiebesluit.docx',
				'version' => 'sha256:v1',
				'blocks' => [
					['anchor' => 'b1-1', 'text' => 'Besluit'],
					['anchor' => 'b2-1', 'text' => 'De gemeente Zaanstad kent de subsidie toe.'],
				],
			]
		);
		$this->documentLogger = $this->createMock(GeneratedDocumentLogger::class);
		$this->editors = $this->createMock(EditorSessionProbe::class);
	}//end setUp()

	/**
	 * The service, signed in as alice.
	 *
	 * @return DocumentAgentService The service.
	 */
	private function service(): DocumentAgentService {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new DocumentAgentService(
			editSession: $this->editSession,
			artefacts: new AgentArtefacts(pdfConversion: $this->createMock(PdfConversionService::class), marker: $this->createMock(AgentArtefactMarker::class)),
			documentLogger: $this->documentLogger,
			rootFolder: $this->createMock(IRootFolder::class),
			userSession: $session,
			logger: $this->createMock(LoggerInterface::class),
			editors: $this->editors
		);
	}//end service()

	/**
	 * A document the agent may not edit directly is not handed to its editor
	 * either: the refusal comes back and nothing is read, delivered or recorded.
	 *
	 * @return void
	 */
	public function testAGuardedOpenDocumentIsRefusedBeforeDelivery(): void {
		$this->editors->method('attachedTo')->willReturn(['owner' => 'declared-editor', 'suite' => 'Declared Suite', 'adapter' => 'connector']);
		$this->editSession->expects($this->once())->method('assertAgentMayEdit')->with('alice', 12)
			->willThrowException(new RuntimeException('This document is part of a signing request.'));
		$this->editSession->expects($this->never())->method('openForAgent');
		$this->documentLogger->expects($this->never())->method($this->anything());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('This document is part of a signing request.');

		$this->service()->editDocument(
			fileId: 12,
			edits: [['anchor' => 'b2-1', 'action' => 'replace', 'text' => 'Gewijzigd.']],
			version: 'sha256:v1'
		);

	}//end testAGuardedOpenDocumentIsRefusedBeforeDelivery()

	/**
	 * An edit to an open document is handed to the editor as text replacements; nothing is written.
	 *
	 * @return void
	 */
	public function testAnOpenDocumentIsEditedThroughTheEditor(): void {
		$this->editors->expects($this->once())->method('attachedTo')->with(12)
			->willReturn(['owner' => 'declared-editor', 'suite' => 'Declared Suite', 'adapter' => 'connector']);
		$this->editSession->expects($this->never())->method('editForAgent');

		$result = $this->service()->editDocument(
			fileId: 12,
			edits: [['anchor' => 'b2-1', 'action' => 'replace', 'text' => 'De gemeente Zaandam kent de subsidie toe.']],
			version: 'sha256:v1'
		);

		self::assertSame('editor', $result['delivery']);
		self::assertFalse($result['written']);
		self::assertSame('Declared Suite', $result['suite']);
		self::assertSame('connector', $result['adapter']);
		self::assertSame(12, $result['fileId']);
		self::assertSame('subsidiebesluit.docx', $result['name']);
		self::assertSame(
			[['find' => 'De gemeente Zaanstad kent de subsidie toe.', 'replace' => 'De gemeente Zaandam kent de subsidie toe.']],
			$result['replacements']
		);
		self::assertArrayNotHasKey('artefact', $result);
	}//end testAnOpenDocumentIsEditedThroughTheEditor()

	/**
	 * The editor-delivered edit is audited as proposed by the agent, under the user, naming the suite.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-an-agent-proposed-edit-must-remain-attributable-after-the-editor-saves-it
	 */
	public function testAnEditorDeliveredEditIsAudited(): void {
		$this->editors->method('attachedTo')->willReturn(['owner' => 'declared-editor', 'suite' => 'Declared Suite', 'adapter' => 'connector']);
		$this->documentLogger->expects($this->once())->method('log')->with(
			$this->callback(
				static fn (DocumentGenerationRecord $record): bool => $record->userId === 'alice'
					&& str_contains((string)$record->template['name'], 'proposed by the document agent')
					&& str_contains((string)$record->template['name'], 'through Declared Suite')
			),
			$this->callback(
				static fn (array $outcome): bool => $outcome['fileId'] === 12
					&& $outcome['filePath'] === 'Besluiten/subsidiebesluit.docx'
					&& $outcome['status'] === 'generated'
			)
		);

		$this->service()->editDocument(fileId: 12, edits: [['anchor' => 'b1-1', 'action' => 'delete']], version: 'sha256:v1');
	}//end testAnEditorDeliveredEditIsAudited()

	/**
	 * An editor with no delivery adapter refuses, naming the suite and the direct path.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-suite-specific-code-must-be-confined-to-delivery
	 */
	public function testAnEditorWithoutAnAdapterRefusesByName(): void {
		$this->editors->method('attachedTo')->willReturn(['owner' => 'other-editor', 'suite' => 'other-editor', 'adapter' => null]);
		$this->editSession->expects($this->never())->method('editForAgent');
		$this->documentLogger->expects($this->never())->method('log');

		try {
			$this->service()->editDocument(fileId: 12, edits: [['anchor' => 'b1-1', 'action' => 'delete']], version: 'sha256:v1');
			self::fail('An editor without an adapter must refuse.');
		} catch (RuntimeException $e) {
			self::assertSame(
				'"subsidiebesluit.docx" is open in other-editor, which offers no way to apply an edit through the editor, '
				. 'so it was not changed. With the document closed the edit can be written directly.',
				$e->getMessage()
			);
		}
	}//end testAnEditorWithoutAnAdapterRefusesByName()

	/**
	 * Anchors from an older read are refused rather than matched against changed text.
	 *
	 * @return void
	 */
	public function testAStaleReadIsRefused(): void {
		$this->editors->method('attachedTo')->willReturn(['owner' => 'declared-editor', 'suite' => 'Declared Suite', 'adapter' => 'connector']);
		$this->documentLogger->expects($this->never())->method('log');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('This document changed since you read it');

		$this->service()->editDocument(fileId: 12, edits: [['anchor' => 'b1-1', 'action' => 'delete']], version: 'sha256:old');
	}//end testAStaleReadIsRefused()

	/**
	 * Without an editor the direct path is used, as before.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-an-agent-edit-must-reach-a-document-that-is-open-for-editing
	 */
	public function testWithoutAnEditorTheDirectPathIsUsed(): void {
		$this->editors->method('attachedTo')->willReturn(null);
		$this->editSession->expects($this->once())->method('editForAgent')->willReturn(
			['fileId' => 12, 'path' => 'p', 'outputMode' => 'inPlace']
		);

		$result = $this->service()->editDocument(fileId: 12, edits: [['anchor' => 'b1-1', 'action' => 'delete']], version: 'sha256:v1');

		self::assertArrayNotHasKey('delivery', $result);
		self::assertSame(['type' => 'file', 'id' => '12'], $result['artefact']);
	}//end testWithoutAnEditorTheDirectPathIsUsed()

	/**
	 * A read of an open document says it will be saved first; a closed one carries no editor block.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/edit-through-the-open-editor/specs/edit-through-the-open-editor/spec.md#requirement-the-agent-must-say-that-it-is-going-to-save-the-users-document
	 */
	public function testAReadOfAnOpenDocumentAnnouncesTheSave(): void {
		$this->editors->method('attachedTo')->willReturnOnConsecutiveCalls(
			['owner' => 'other-editor', 'suite' => 'Other Suite', 'adapter' => null],
			null
		);

		$open = $this->service()->readDocument(fileId: 12);
		$closed = $this->service()->readDocument(fileId: 12);

		self::assertSame(
			[
				'suite' => 'Other Suite',
				'deliverable' => false,
				'saveFirst' => true,
				'announce' => '"subsidiebesluit.docx" is open in Other Suite. Your document will be saved first, '
					. 'so the change is made to exactly what you see.',
			],
			$open['editor']
		);
		self::assertSame('sha256:v1', $open['version']);
		self::assertArrayNotHasKey('editor', $closed);
	}//end testAReadOfAnOpenDocumentAnnouncesTheSave()
}//end class

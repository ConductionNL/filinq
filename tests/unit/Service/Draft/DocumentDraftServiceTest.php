<?php

/**
 * Tests for DocumentDraftService.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Draft
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Draft;

use DateTimeImmutable;
use Exception;
use OCA\Filinq\Kernel\Contracts\CorrespondenceServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentServiceInterface;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\TemplateServiceInterface;
use OCA\Filinq\Service\Draft\DocumentDraftService;
use OCA\Filinq\Service\Draft\DraftHtmlSanitizer;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Creating renders and saves without filing or logging; finishing files and
 * logs through the direct path and closes the draft, for its owner only.
 *
 * @covers \OCA\Filinq\Service\Draft\DocumentDraftService
 * @uses   \OCA\Filinq\Service\Draft\DraftHtmlSanitizer
 */
class DocumentDraftServiceTest extends TestCase {

	private DocumentServiceInterface&MockObject $documents;

	private CorrespondenceServiceInterface&MockObject $correspondence;

	private TemplateServiceInterface&MockObject $templates;

	private ObjectService&MockObject $objects;

	private SettingsServiceInterface&MockObject $settings;

	/**
	 * The objects saved, with their arguments.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	protected function setUp(): void {
		$this->documents      = $this->createMock(DocumentServiceInterface::class);
		$this->correspondence = $this->createMock(CorrespondenceServiceInterface::class);
		$this->templates      = $this->createMock(TemplateServiceInterface::class);
		$this->objects        = $this->createMock(ObjectService::class);
		$this->settings       = $this->createMock(SettingsServiceInterface::class);
		$this->settings->method('getObjectService')->willReturn($this->objects);
		$this->objects->method('saveObject')->willReturnCallback(function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null): array {
			$this->saved[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid];
			return $object + ['@self' => ['id' => ($uuid ?? 'draft-new')]];
		});
	}//end setUp()

	private function service(): DocumentDraftService {
		$time = $this->createMock(ITimeFactory::class);
		$time->method('now')->willReturn(new DateTimeImmutable('2026-11-13T10:00:00+00:00'));

		return new DocumentDraftService(
			documents: $this->documents,
			correspondence: $this->correspondence,
			templates: $this->templates,
			settings: $this->settings,
			sanitizer: new DraftHtmlSanitizer(),
			time: $time
		);
	}//end service()

	/**
	 * A stored draft as OpenRegister answers it.
	 *
	 * @param array<string, mixed> $fields Fields over the defaults.
	 *
	 * @return array<string, mixed> The draft.
	 */
	private function storedDraft(array $fields = []): array {
		return $fields + [
			'@self' => ['id' => 'draft-9', 'owner' => 'alice'],
			'origin' => 'document',
			'stage' => 'editing',
			'templateId' => 'template-1',
			'templateName' => 'Algemene brief',
			'templateVersion' => 3,
			'dataRefs' => [['register' => 'r', 'schema' => 's', 'id' => 'o-1']],
			'format' => 'pdf',
			'outputOptions' => ['output' => ['mode' => 'files'], 'filename' => 'brief'],
			'html' => '<p onclick="steal()">Beste {{ naam }}</p><script>x()</script>',
			'expiresAt' => '2026-11-20T00:00:00+00:00',
		];
	}//end storedDraft()

	public function testCreateRendersThePreviewAndSavesAnEditingDraftWithoutFiling(): void {
		$this->templates->method('getTemplate')->willReturn(['name' => 'Algemene brief', 'version' => '3']);
		$this->documents->expects($this->once())->method('generatePreview')
			->with('template-1', [['register' => 'r', 'schema' => 's', 'id' => 'o-1']], $this->arrayHasKey('adHocData'))
			->willReturn(['html' => '<p>Beste Jan</p>', 'warnings' => ['w']]);
		$this->documents->expects($this->never())->method('generateDocument');
		$this->documents->expects($this->never())->method('generateFromHtml');
		$this->correspondence->expects($this->never())->method('logFinishedDraft');

		$draft = $this->service()->create(
			templateId: 'template-1',
			dataRefs: [['register' => 'r', 'schema' => 's', 'id' => 'o-1']],
			options: ['format' => 'docx', 'adHocData' => ['a' => 1], 'listRefs' => [], 'filename' => 'brief', 'recipientType' => 'person'],
			origin: 'correspondence'
		);

		$this->assertSame(['id' => 'draft-new', 'stage' => 'editing', 'html' => '<p>Beste Jan</p>', 'warnings' => ['w'], 'expiresAt' => '2026-12-13T10:00:00+00:00'], $draft);
		$this->assertCount(1, $this->saved);
		$saved = $this->saved[0];
		$this->assertSame(['filinq', 'documentDraft', null], [$saved['register'], $saved['schema'], $saved['uuid']]);
		$this->assertSame('correspondence', $saved['object']['origin']);
		$this->assertSame('Algemene brief', $saved['object']['templateName']);
		$this->assertSame(3, $saved['object']['templateVersion']);
		$this->assertSame('docx', $saved['object']['format']);
		$this->assertSame(['a' => 1], $saved['object']['adHocData']);
		$this->assertSame(['filename' => 'brief', 'recipientType' => 'person'], $saved['object']['outputOptions']);
		$this->assertArrayNotHasKey('wizardId', $saved['object']);
	}//end testCreateRendersThePreviewAndSavesAnEditingDraftWithoutFiling()

	public function testCreateKeepsTheWizardAnswersAndStep(): void {
		$this->templates->method('getTemplate')->willReturn(['name' => 'Beschikking']);
		$this->documents->method('generatePreview')->willReturn(['html' => '<p>x</p>', 'warnings' => []]);

		$this->service()->create(
			templateId: 'template-1',
			dataRefs: [],
			options: ['wizardContext' => ['wizardId' => 'wizard-1', 'answers' => ['q1' => 'ja'], 'step' => 3]],
			origin: 'wizard'
		);

		$object = $this->saved[0]['object'];
		$this->assertSame(['wizard-1', ['q1' => 'ja'], 3], [$object['wizardId'], $object['wizardAnswers'], $object['wizardStep']]);
		$this->assertNull($object['templateVersion']);
	}//end testCreateKeepsTheWizardAnswersAndStep()

	public function testATemplateWithAPlainLanguageVersionIsRefusedWith409AndNothingIsSaved(): void {
		$this->templates->method('getTemplate')->willReturn(['name' => 'Brief', 'plainLanguage' => ['templateId' => 'plain-1']]);
		$this->documents->expects($this->never())->method('generatePreview');

		try {
			$this->service()->create(templateId: 'template-1', dataRefs: [], options: [], origin: 'document');
			$this->fail('The draft was not refused.');
		} catch (Exception $e) {
			$this->assertSame(409, $e->getCode());
			$this->assertSame('This template has a plain-language version. Generate it directly.', $e->getMessage());
		}

		$this->assertSame([], $this->saved);
	}//end testATemplateWithAPlainLanguageVersionIsRefusedWith409AndNothingIsSaved()

	public function testAnUnknownOriginIsRefusedWith400(): void {
		$this->expectExceptionCode(400);
		$this->service()->create(templateId: 'template-1', dataRefs: [], options: [], origin: 'email');
	}//end testAnUnknownOriginIsRefusedWith400()

	public function testWithoutOpenRegisterCreateAnswers503(): void {
		$settings = $this->createMock(SettingsServiceInterface::class);
		$settings->method('getObjectService')->willReturn(null);
		$this->settings = $settings;
		$this->templates->method('getTemplate')->willReturn([]);
		$this->documents->method('generatePreview')->willReturn(['html' => '', 'warnings' => []]);

		$this->expectExceptionCode(503);
		$this->service()->create(templateId: 'template-1', dataRefs: [], options: [], origin: 'document');
	}//end testWithoutOpenRegisterCreateAnswers503()

	public function testFinishProducesTheSanitisedHtmlLogsItAndClosesTheDraft(): void {
		$this->objects->method('find')->willReturn($this->storedDraft());
		$this->documents->expects($this->once())->method('generateFromHtml')
			->with(
				'template-1',
				'<p>Beste {{ naam }}</p>',
				[['register' => 'r', 'schema' => 's', 'id' => 'o-1']],
				['output' => ['mode' => 'files'], 'filename' => 'brief', 'format' => 'pdf', 'userId' => 'alice', 'templateVersion' => 3],
				['draftId' => 'draft-9', 'editedBy' => 'alice'],
				true
			)
			->willReturn(['content' => '%PDF', 'format' => 'pdf', 'metadata' => ['@self' => ['id' => 'record-5']], 'warnings' => [], 'output' => ['mode' => 'files']]);
		$this->correspondence->expects($this->never())->method('logFinishedDraft');

		$result = $this->service()->finish(draftId: 'draft-9', userId: 'alice');

		$this->assertSame('draft-9', $result['draftId']);
		$this->assertSame('record-5', $result['recordId']);
		$this->assertCount(1, $this->saved);
		$closed = $this->saved[0];
		$this->assertSame('draft-9', $closed['uuid']);
		$this->assertSame('finished', $closed['object']['stage']);
		$this->assertSame('record-5', $closed['object']['finishedDocumentId']);
		$this->assertSame('2026-12-13T10:00:00+00:00', $closed['object']['expiresAt']);
		$this->assertArrayNotHasKey('@self', $closed['object']);
	}//end testFinishProducesTheSanitisedHtmlLogsItAndClosesTheDraft()

	public function testACorrespondenceDraftIsLoggedAsCorrespondenceNotAsAGeneratedDocument(): void {
		$this->objects->method('find')->willReturn($this->storedDraft(['origin' => 'correspondence']));
		$this->documents->expects($this->once())->method('generateFromHtml')
			->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), $this->anything(), false)
			->willReturn(['content' => '%PDF', 'format' => 'pdf', 'metadata' => [], 'warnings' => []]);
		$this->correspondence->expects($this->once())->method('logFinishedDraft')
			->with('template-1', 'Algemene brief', [['register' => 'r', 'schema' => 's', 'id' => 'o-1']], $this->arrayHasKey('userId'), 'draft-9', 'alice')
			->willReturn(['@self' => ['id' => 'letter-3']]);

		$result = $this->service()->finish(draftId: 'draft-9', userId: 'alice');

		$this->assertSame('letter-3', $result['recordId']);
		$this->assertSame('letter-3', $this->saved[0]['object']['finishedDocumentId']);
	}//end testACorrespondenceDraftIsLoggedAsCorrespondenceNotAsAGeneratedDocument()

	public function testAWizardDraftPassesItsAnswersAsTheWizardContext(): void {
		$this->objects->method('find')->willReturn($this->storedDraft(['wizardId' => 'wizard-1', 'wizardAnswers' => ['q1' => 'ja']]));
		$this->documents->expects($this->once())->method('generateFromHtml')
			->with($this->anything(), $this->anything(), $this->anything(), $this->callback(
				static fn (array $options): bool => $options['wizardContext'] === ['wizardId' => 'wizard-1', 'answers' => ['q1' => 'ja']]
			))
			->willReturn(['content' => '', 'format' => 'pdf', 'metadata' => [], 'warnings' => []]);

		$this->service()->finish(draftId: 'draft-9', userId: 'alice');
	}//end testAWizardDraftPassesItsAnswersAsTheWizardContext()

	public function testAWizardRefusalLeavesTheDraftOpen(): void {
		$this->objects->method('find')->willReturn($this->storedDraft(['wizardId' => 'wizard-1']));
		$this->documents->method('generateFromHtml')->willThrowException(new Exception('The wizard answers are not complete', 422));

		try {
			$this->service()->finish(draftId: 'draft-9', userId: 'alice');
			$this->fail('The finish was not refused.');
		} catch (Exception $e) {
			$this->assertSame(422, $e->getCode());
		}

		$this->assertSame([], $this->saved);
	}//end testAWizardRefusalLeavesTheDraftOpen()

	public function testAnotherUsersDraftIsNotFound(): void {
		$this->objects->method('find')->willReturn($this->storedDraft());
		$this->documents->expects($this->never())->method('generateFromHtml');

		$this->expectExceptionCode(404);
		$this->service()->finish(draftId: 'draft-9', userId: 'bob');
	}//end testAnotherUsersDraftIsNotFound()

	public function testADraftOpenRegisterRefusesIsNotFound(): void {
		$this->objects->method('find')->willThrowException(new Exception('Do not have permission', 403));

		$this->expectExceptionCode(404);
		$this->service()->finish(draftId: 'draft-9', userId: 'alice');
	}//end testADraftOpenRegisterRefusesIsNotFound()

	public function testAFinishedDraftIsRefusedWith409(): void {
		$this->objects->method('find')->willReturn($this->storedDraft(['stage' => 'finished']));
		$this->documents->expects($this->never())->method('generateFromHtml');

		$this->expectExceptionCode(409);
		$this->expectExceptionMessage('This draft is already finished.');
		$this->service()->finish(draftId: 'draft-9', userId: 'alice');
	}//end testAFinishedDraftIsRefusedWith409()

	public function testADraftStillAnsweringIsRefusedWith409(): void {
		$this->objects->method('find')->willReturn($this->storedDraft(['stage' => 'answering']));

		$this->expectExceptionCode(409);
		$this->expectExceptionMessage('Open the draft for editing before finishing it.');
		$this->service()->finish(draftId: 'draft-9', userId: 'alice');
	}//end testADraftStillAnsweringIsRefusedWith409()
}//end class

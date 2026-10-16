<?php

/**
 * Unit tests for DocumentGenerationRequestService
 *
 * The shared service behind the filinq.generate-document flow node and the
 * DocumentGenerationRequestedEvent command. Its collaborators are doubled at
 * the boundary (DocumentService, the template lookups, OpenRegister), and
 * the DocumentGeneratedEvent it dispatches is captured as the REAL class.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Event\DocumentGeneratedEvent;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

/**
 * Tests for DocumentGenerationRequestService.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentGenerationRequestServiceTest extends TestCase {

	/**
	 * The render, store and audit path.
	 *
	 * @var DocumentService&MockObject
	 */
	private DocumentService&MockObject $documents;

	/**
	 * Template lookup by id.
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService&MockObject $templates;

	/**
	 * Template lookup by slug.
	 *
	 * @var TemplateSlugResolver&MockObject
	 */
	private TemplateSlugResolver&MockObject $slugs;

	/**
	 * Filename renderer.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer&MockObject $renderer;

	/**
	 * OpenRegister's object service, for the field write.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The acting user session.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession&MockObject $session;

	/**
	 * Every event the service dispatched.
	 *
	 * @var array<int, object>
	 */
	private array $dispatched = [];

	/**
	 * The service under test.
	 *
	 * @var DocumentGenerationRequestService
	 */
	private DocumentGenerationRequestService $service;

	/**
	 * Build the service over fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->documents = $this->createMock(DocumentService::class);
		$this->templates = $this->createMock(TemplateService::class);
		$this->slugs = $this->createMock(TemplateSlugResolver::class);
		$this->renderer = $this->createMock(TemplateRenderer::class);
		$this->objects = $this->getMockBuilder(ObjectService::class)
			->onlyMethods(['patchObject'])
			->getMock();
		$this->session = $this->createMock(IUserSession::class);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);

		$this->dispatched = [];
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->method('dispatchTyped')->willReturnCallback(
			function (object $event): void {
				$this->dispatched[] = $event;
			}
		);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->session->method('getUser')->willReturn($user);

		$this->documents->method('buildOutputTargetPath')->willReturnCallback(
			static fn (string $templateId, ?string $explicitTargetPath, ?array $template = null): string
				=> 'DocuDesk/' . ($template['namespace'] ?? 'default')
		);

		$this->service = new DocumentGenerationRequestService(
			documents: $this->documents,
			templates: $this->templates,
			slugs: $this->slugs,
			renderer: $this->renderer,
			objectResolver: $resolver,
			userSession: $this->session,
			eventDispatcher: $dispatcher
		);
	}//end setUp()

	/**
	 * What DocumentService returns for a stored document.
	 *
	 * @return array<string, mixed>
	 */
	private function storedResult(): array {
		return [
			'content' => '%PDF-1.7',
			'html' => '<p>Dear Jan</p>',
			'format' => 'pdf',
			'metadata' => [],
			'warnings' => [],
			'output' => [
				'mode' => 'files',
				'fileId' => 314,
				'path' => '/alice/files/DocuDesk/flow/obj-1/Letter.pdf',
				'name' => 'Letter.pdf',
				'size' => 1200,
			],
		];
	}//end storedResult()

	/**
	 * An inline template is rendered over the item and filed per object.
	 *
	 * @return void
	 */
	public function testInlineTemplateIsFiledInAFolderPerObject(): void {
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->with(
				'inline',
				$this->callback(
					static fn (array $template): bool => $template['content'] === 'Dear {{ name }}'
						&& $template['namespace'] === 'flow'
				),
				[['register' => 'cases', 'schema' => 'case', 'id' => 'obj-1']],
				$this->callback(
					static fn (array $options): bool => $options['format'] === 'pdf'
						&& $options['userId'] === 'alice'
						&& $options['output'] === ['mode' => 'files', 'targetPath' => 'DocuDesk/flow/obj-1']
						&& $options['adHocData']['name'] === 'Jan'
						&& $options['adHocData']['item'] === ['name' => 'Jan']
						&& $options['filename'] === 'Letter'
				)
			)
			->willReturn($this->storedResult());

		$result = $this->service->generate(
			request: [
				'template' => 'Dear {{ name }}',
				'filename' => 'Letter',
				'data' => ['name' => 'Jan'],
				'object' => ['register' => 'cases', 'schema' => 'case', 'id' => 'obj-1'],
				'metadata' => ['informatieobjecttype' => 'brief'],
			],
			requestingApp: 'dossiq'
		);

		$this->assertSame(314, $result['fileId']);
		$this->assertSame('application/pdf', $result['mime']);
		$this->assertSame('Letter.pdf', $result['name']);
		$this->assertSame('inline', $result['template']['source']);
		$this->assertSame(['informatieobjecttype' => 'brief'], $result['metadata']);
		$this->assertArrayNotHasKey('text', $result);

		$this->assertCount(1, $this->dispatched);
		$event = $this->dispatched[0];
		$this->assertInstanceOf(DocumentGeneratedEvent::class, $event);
		$this->assertSame(314, $event->getFileId());
		$this->assertSame('/alice/files/DocuDesk/flow/obj-1/Letter.pdf', $event->getFilePath());
		$this->assertSame(['register' => 'cases', 'schema' => 'case', 'id' => 'obj-1'], $event->getObject());
		$this->assertSame(['informatieobjecttype' => 'brief'], $event->getMetadata());
		$this->assertSame('dossiq', $event->getRequestingApp());
		$this->assertSame('inline', $event->getTemplate()['id']);
	}//end testInlineTemplateIsFiledInAFolderPerObject()

	/**
	 * A template named by id is looked up and its id recorded.
	 *
	 * @return void
	 */
	public function testTemplateByIdIsLookedUp(): void {
		$template = ['id' => 'tpl-1', 'name' => 'Besluit', 'namespace' => 'dossiq', 'content' => 'x', 'version' => 3];
		$this->templates->expects($this->once())->method('getTemplate')->with('tpl-1')->willReturn($template);
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->with('tpl-1', $template)
			->willReturn($this->storedResult());

		$result = $this->service->generate(request: ['templateId' => 'tpl-1', 'format' => 'pdf'], requestingApp: 'flow');

		$this->assertSame(
			['id' => 'tpl-1', 'slug' => null, 'name' => 'Besluit', 'version' => 3, 'source' => 'id'],
			$result['template']
		);
		$this->assertNull($result['object']);
	}//end testTemplateByIdIsLookedUp()

	/**
	 * A template named by slug goes through the slug resolver, tenant included.
	 *
	 * @return void
	 */
	public function testTemplateBySlugIsResolvedInItsNamespace(): void {
		$template = ['id' => 'tpl-9', 'slug' => 'ontvangstbevestiging', 'name' => 'Ontvangst', 'content' => 'x'];
		$this->slugs->expects($this->once())
			->method('resolve')
			->with('dossiq', 'ontvangstbevestiging', 'tenant-a')
			->willReturn($template);
		$this->documents->method('generateFromTemplate')->willReturn($this->storedResult());

		$result = $this->service->generate(
			request: [
				'templateSlug' => 'ontvangstbevestiging',
				'templateNamespace' => 'dossiq',
				'templateTenantId' => 'tenant-a',
			],
			requestingApp: 'dossiq'
		);

		$this->assertSame('tpl-9', $result['template']['id']);
		$this->assertSame('slug', $result['template']['source']);
	}//end testTemplateBySlugIsResolvedInItsNamespace()

	/**
	 * A field-only request writes one field, stores no file and renders no PDF.
	 *
	 * @return void
	 */
	public function testFieldOnlyRequestPatchesOnlyThatField(): void {
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->with(
				'inline',
				$this->anything(),
				$this->anything(),
				$this->callback(
					static fn (array $options): bool => $options['format'] === 'html'
						&& $options['output'] === ['mode' => 'return']
						&& array_key_exists('userId', $options) === false
				)
			)
			->willReturn(
				[
					'content' => 'Besluit: toegekend',
					'html' => 'Besluit: toegekend',
					'format' => 'html',
					'metadata' => [],
					'warnings' => [],
					'output' => ['mode' => 'return', 'fileId' => null, 'path' => null, 'name' => null, 'size' => null],
				]
			);

		$this->objects->expects($this->once())
			->method('patchObject')
			->with('obj-1', ['besluitTekst' => 'Besluit: toegekend'], 'cases', 'case')
			->willReturn(null);

		$result = $this->service->generate(
			request: [
				'template' => 'Besluit: {{ uitkomst }}',
				'storeFile' => false,
				'targetField' => 'besluitTekst',
				'data' => ['uitkomst' => 'toegekend'],
				'object' => ['register' => 'cases', 'schema' => 'case', 'id' => 'obj-1'],
			],
			requestingApp: 'flow'
		);

		$this->assertNull($result['fileId']);
		$this->assertSame('besluitTekst', $result['targetField']);
		$this->assertSame('Besluit: toegekend', $result['text']);
		$this->assertSame('text/html', $result['mime']);
		$this->assertCount(1, $this->dispatched);
	}//end testFieldOnlyRequestPatchesOnlyThatField()

	/**
	 * A target field with no object to write it to is refused before rendering.
	 *
	 * @return void
	 */
	public function testTargetFieldWithoutObjectIsRefusedBeforeAnythingHappens(): void {
		$this->documents->expects($this->never())->method('generateFromTemplate');
		$this->objects->expects($this->never())->method('patchObject');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('names no object');

		$this->service->generate(
			request: ['template' => 'x', 'targetField' => 'body', 'object' => ['register' => 'r', 'schema' => '', 'id' => '1']],
			requestingApp: 'flow'
		);
	}//end testTargetFieldWithoutObjectIsRefusedBeforeAnythingHappens()

	/**
	 * Storing a file with nobody to own it is refused, not filed as nobody.
	 *
	 * @return void
	 */
	public function testStoringWithoutAnActingUserIsRefused(): void {
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn(null);
		$documents = $this->createMock(DocumentService::class);
		$documents->expects($this->never())->method('generateFromTemplate');

		$service = new DocumentGenerationRequestService(
			documents: $documents,
			templates: $this->templates,
			slugs: $this->slugs,
			renderer: $this->renderer,
			objectResolver: $this->createMock(DocumentObjectServiceResolver::class),
			userSession: $session,
			eventDispatcher: $this->createMock(IEventDispatcher::class)
		);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('no acting user');

		$service->generate(request: ['template' => 'x'], requestingApp: 'flow');
	}//end testStoringWithoutAnActingUserIsRefused()

	/**
	 * A failed generation propagates and announces nothing.
	 *
	 * @return void
	 */
	public function testAFailedGenerationThrowsAndAnnouncesNothing(): void {
		$this->documents->method('generateFromTemplate')
			->willThrowException(new \Exception('Failed to store generated document in Files', 507));

		try {
			$this->service->generate(request: ['template' => 'x'], requestingApp: 'flow');
			$this->fail('The storage failure must reach the caller.');
		} catch (\Exception $e) {
			$this->assertSame(507, $e->getCode());
		}

		$this->assertSame([], $this->dispatched);
	}//end testAFailedGenerationThrowsAndAnnouncesNothing()

	/**
	 * A templated file name is rendered and cannot become a path.
	 *
	 * @return void
	 */
	public function testTemplatedFilenameIsRenderedAndFlattened(): void {
		$this->renderer->expects($this->once())
			->method('renderTemplate')
			->with('Besluit {{ identifier }}', $this->anything())
			->willReturn('Besluit Z/2026/1 &amp; bijlage');
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->callback(static fn (array $options): bool => $options['filename'] === 'Besluit Z-2026-1 & bijlage')
			)
			->willReturn($this->storedResult());

		$this->service->generate(
			request: ['template' => 'x', 'filename' => 'Besluit {{ identifier }}', 'data' => ['identifier' => 'Z/2026/1']],
			requestingApp: 'flow'
		);
	}//end testTemplatedFilenameIsRenderedAndFlattened()

	/**
	 * Every unusable configuration is refused with a message naming the key.
	 *
	 * @param array<string, mixed> $request  The request.
	 * @param string               $mentions What the message must name.
	 *
	 * @return void
	 *
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequestsAreRefused(array $request, string $mentions): void {
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage($mentions);

		$this->service->validate(request: $request);
	}//end testInvalidRequestsAreRefused()

	/**
	 * Requests the service must refuse.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string}>
	 */
	public static function invalidRequestProvider(): array {
		return [
			'no template' => [[], 'exactly one template'],
			'two templates' => [['templateId' => 'a', 'template' => 'b'], 'exactly one template'],
			'slug without namespace' => [['templateSlug' => 'a'], 'templateNamespace'],
			'unknown format' => [['template' => 'a', 'format' => 'docx'], '"format"'],
			'nowhere to go' => [['template' => 'a', 'storeFile' => false], '"storeFile"'],
			'target field not text' => [['template' => 'a', 'targetField' => ['x']], '"targetField"'],
			'metadata not an object' => [['template' => 'a', 'metadata' => 'x'], '"metadata"'],
			'empty output key' => [['template' => 'a', 'output' => ' '], '"output"'],
		];
	}//end invalidRequestProvider()

	/**
	 * Each supported format validates, and maps to its mime type.
	 *
	 * @return void
	 */
	public function testSupportedFormatsValidate(): void {
		foreach (array_keys(DocumentGenerationRequestService::FORMATS) as $format) {
			$this->service->validate(request: ['template' => 'a', 'format' => $format]);
		}

		$this->assertSame(['pdf', 'odf', 'html'], array_keys(DocumentGenerationRequestService::FORMATS));
	}//end testSupportedFormatsValidate()
}//end class

<?php

/**
 * Unit tests for GenerateDocumentNode (filinq.generate-document)
 *
 * The node runs over the REAL DocumentGenerationRequestService; only the
 * service's own boundaries (DocumentService, OpenRegister, the session) are
 * doubled. That way validateConfig() and execute() are tested against the
 * rules they actually delegate to, not against a stand-in for them.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-generate-document-node/specs/flow-document-generation/spec.md#requirement-a-flow-step-generates-a-document-per-item
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\GenerateDocumentNode;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\OpenRegister\Service\Flow\IFlowNodeTaxonomy;
use OCA\OpenRegister\Service\ObjectService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * Tests for GenerateDocumentNode.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class GenerateDocumentNodeTest extends TestCase {

	/**
	 * The render, store and audit path.
	 *
	 * @var DocumentService&MockObject
	 */
	private DocumentService&MockObject $documents;

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The node under test.
	 *
	 * @var GenerateDocumentNode
	 */
	private GenerateDocumentNode $node;

	/**
	 * Build the node over the real service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->documents = $this->createMock(DocumentService::class);
		$this->documents->method('buildOutputTargetPath')->willReturn('DocuDesk/flow');
		$this->objects = $this->getMockBuilder(ObjectService::class)
			->onlyMethods(['patchObject'])
			->getMock();

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('flow-runner');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$service = new DocumentGenerationRequestService(
			documents: $this->documents,
			templates: $this->createMock(TemplateService::class),
			slugs: $this->createMock(TemplateSlugResolver::class),
			renderer: $this->createMock(TemplateRenderer::class),
			objectResolver: $resolver,
			userSession: $session,
			eventDispatcher: $this->createMock(IEventDispatcher::class)
		);

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$this->node = new GenerateDocumentNode(
			generator: $service,
			urls: $this->createMock(IURLGenerator::class),
			l10n: $l10n
		);
	}//end setUp()

	/**
	 * What DocumentService returns for one stored document.
	 *
	 * @param int $fileId The file id to report.
	 *
	 * @return array<string, mixed>
	 */
	private function stored(int $fileId): array {
		return [
			'content' => '%PDF',
			'html' => '<p>rendered ' . $fileId . '</p>',
			'format' => 'pdf',
			'metadata' => [],
			'warnings' => [],
			'output' => [
				'mode' => 'files',
				'fileId' => $fileId,
				'path' => '/flow-runner/files/DocuDesk/flow/x/doc.pdf',
				'name' => 'doc.pdf',
				'size' => 10,
			],
		];
	}//end stored()

	/**
	 * The node answers to its published id.
	 *
	 * @return void
	 */
	public function testIdIsThePublishedContract(): void {
		$this->assertSame('filinq.generate-document', $this->node->getId());
		$this->assertTrue($this->node->isAvailableForScope(0));
	}//end testIdIsThePublishedContract()

	/**
	 * One document per item, identified from the item's `@self`.
	 *
	 * @return void
	 */
	public function testExecuteGeneratesOneDocumentPerItemAgainstItsObject(): void {
		$seen = [];
		$this->documents->expects($this->exactly(2))
			->method('generateFromTemplate')
			->willReturnCallback(
				function (string $templateId, array $template, array $dataRefs, array $options) use (&$seen): array {
					$seen[] = ['refs' => $dataRefs, 'path' => $options['output']['targetPath']];
					return $this->stored(fileId: (count($seen) + 100));
				}
			);

		$items = [
			['json' => ['title' => 'A', '@self' => ['id' => 'obj-a', 'register' => '5', 'schema' => '7']]],
			['json' => ['title' => 'B', '@self' => ['id' => 'obj-b', 'register' => '5', 'schema' => '7']]],
		];

		$out = $this->node->execute(
			items: $items,
			config: ['template' => '{{ title }}', 'metadata' => ['classification' => 'openbaar']],
			context: []
		);

		$this->assertCount(2, $out);
		$this->assertSame([['register' => '5', 'schema' => '7', 'id' => 'obj-a']], $seen[0]['refs']);
		$this->assertSame('DocuDesk/flow/obj-b', $seen[1]['path']);
		$this->assertSame(101, $out[0]['json']['document']['fileId']);
		$this->assertSame(102, $out[1]['json']['document']['fileId']);
		$this->assertSame('application/pdf', $out[0]['json']['document']['mime']);
		$this->assertSame(['classification' => 'openbaar'], $out[0]['json']['document']['metadata']);
		$this->assertSame('flow', $out[0]['json']['document']['requestingApp']);
		$this->assertSame('A', $out[0]['json']['title'], 'the item keeps its own fields');
	}//end testExecuteGeneratesOneDocumentPerItemAgainstItsObject()

	/**
	 * Configured register/schema/objectId win over the item, and output is renamed.
	 *
	 * @return void
	 */
	public function testConfigNamesTheObjectAndTheOutputKey(): void {
		$this->documents->expects($this->once())
			->method('generateFromTemplate')
			->with(
				$this->anything(),
				$this->anything(),
				[['register' => 'cases', 'schema' => 'case', 'id' => 'cfg-1']],
				$this->anything()
			)
			->willReturn($this->stored(fileId: 7));

		$out = $this->node->execute(
			items: [['json' => ['id' => 'item-id']]],
			config: [
				'template' => 'x',
				'register' => 'cases',
				'schema' => 'case',
				'objectId' => 'cfg-1',
				'output' => 'besluitDocument',
				'requestingApp' => 'dossiq',
			],
			context: []
		);

		$this->assertSame(7, $out[0]['json']['besluitDocument']['fileId']);
		$this->assertSame('dossiq', $out[0]['json']['besluitDocument']['requestingApp']);
		$this->assertArrayNotHasKey('document', $out[0]['json']);
	}//end testConfigNamesTheObjectAndTheOutputKey()

	/**
	 * With targetField the next step sees the text this step stored.
	 *
	 * @return void
	 */
	public function testTargetFieldIsStampedOntoTheOutgoingItem(): void {
		$this->documents->method('generateFromTemplate')->willReturn(
			[
				'content' => 'Toegekend',
				'html' => 'Toegekend',
				'format' => 'html',
				'metadata' => [],
				'warnings' => [],
				'output' => ['mode' => 'return', 'fileId' => null, 'path' => null, 'name' => null, 'size' => null],
			]
		);
		$this->objects->expects($this->once())
			->method('patchObject')
			->with('obj-1', ['besluit' => 'Toegekend'], '5', '7');

		$out = $this->node->execute(
			items: [['json' => ['@self' => ['id' => 'obj-1', 'register' => '5', 'schema' => '7']]]],
			config: ['template' => 'x', 'storeFile' => false, 'targetField' => 'besluit'],
			context: []
		);

		$this->assertSame('Toegekend', $out[0]['json']['besluit']);
		$this->assertSame('besluit', $out[0]['json']['document']['targetField']);
		$this->assertArrayNotHasKey('text', $out[0]['json']['document'], 'the text lives on the field, not twice');
	}//end testTargetFieldIsStampedOntoTheOutgoingItem()

	/**
	 * A failure reaches the engine so its onError policy decides.
	 *
	 * @return void
	 */
	public function testAFailureThrowsToTheEngine(): void {
		$this->documents->method('generateFromTemplate')->willThrowException(new \Exception('Template not found', 404));

		$this->expectException(\Exception::class);
		$this->expectExceptionCode(404);

		$this->node->execute(items: [['json' => ['id' => 'x']]], config: ['templateId' => 'missing'], context: []);
	}//end testAFailureThrowsToTheEngine()

	/**
	 * validateConfig() refuses what the service refuses.
	 *
	 * @return void
	 */
	public function testValidateConfigRefusesAConfigurationWithoutATemplate(): void {
		$this->expectException(UnexpectedValueException::class);

		$this->node->validateConfig(config: ['format' => 'pdf']);
	}//end testValidateConfigRefusesAConfigurationWithoutATemplate()

	/**
	 * validateConfig() accepts a usable configuration.
	 *
	 * @return void
	 */
	public function testValidateConfigAcceptsAUsableConfiguration(): void {
		$this->node->validateConfig(config: ['templateSlug' => 'besluit', 'templateNamespace' => 'dossiq', 'format' => 'odf']);

		$this->addToAssertionCount(1);
	}//end testValidateConfigAcceptsAUsableConfiguration()

	/**
	 * Every form field edits a key the node actually reads.
	 *
	 * @return void
	 */
	public function testEveryFormFieldEditsAKeyTheNodeReads(): void {
		foreach ($this->node->configForm() as $field) {
			$this->assertContains($field['key'], $this->node->configKeys(), 'form field over an unread key: ' . $field['key']);
			$this->assertContains($field['type'], ['text', 'textarea', 'number', 'boolean', 'select']);
		}
	}//end testEveryFormFieldEditsAKeyTheNodeReads()

	/**
	 * Kind and category come from OpenRegister's closed lists.
	 *
	 * @return void
	 */
	public function testTaxonomyIsDrawnFromTheClosedLists(): void {
		$this->assertContains($this->node->getKind(), IFlowNodeTaxonomy::KINDS);
		$this->assertContains($this->node->getCategory(), IFlowNodeTaxonomy::CATEGORIES);
	}//end testTaxonomyIsDrawnFromTheClosedLists()
}//end class

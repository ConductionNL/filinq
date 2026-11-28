<?php

/**
 * Tests for FinalDocumentRepository::save() against OpenRegister's lifecycle.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * A final version is created the way the lifecycle allows, and its
 * translatable fields read as text.
 */
class FinalDocumentRepositorySaveTest extends TestCase {

	/**
	 * What was written: the status and the uuid of each save.
	 *
	 * @var array<int, array{0: string, 1: string|null}>
	 */
	private array $writes = [];

	/**
	 * A repository over an object service that records its saves and
	 * answers translatable fields as language maps, as OpenRegister does.
	 *
	 * @return FinalDocumentRepository
	 */
	private function repository(): FinalDocumentRepository {
		$objectService = $this->createMock(originalClassName: ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (array $object, ObjectSaveOptions $options): array {
				$this->writes[] = [(string) ($object['status'] ?? ''), $options->uuid];

				return [
					'@self'       => ['id' => ($options->uuid ?? 'version-new'), 'uuid' => ($options->uuid ?? 'version-new')],
					'status'      => $object['status'] ?? '',
					'fileId'      => $object['fileId'] ?? null,
					'finalReason' => ['nl' => (string) ($object['finalReason'] ?? ''), 'en' => 'other'],
				];
			}
		);
		$resolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new FinalDocumentRepository(objectResolver: $resolver, logger: new NullLogger());
	}//end repository()

	/**
	 * A new final version is created in draft and then made final under the
	 * same uuid; its reason reads as text.
	 *
	 * @return void
	 */
	public function testANewFinalVersionIsCreatedAsDraftThenMadeFinal(): void {
		$stored = $this->repository()->save(record: ['fileId' => 7, 'status' => 'final', 'finalReason' => 'Het besluit is genomen']);

		$this->assertSame(expected: [['draft', null], ['final', 'version-new']], actual: $this->writes);
		$this->assertSame(expected: 'final', actual: $stored['status']);
		$this->assertSame(expected: 'Het besluit is genomen', actual: $stored['finalReason']);
	}//end testANewFinalVersionIsCreatedAsDraftThenMadeFinal()

	/**
	 * An update and a new draft are written once, as given.
	 *
	 * @return void
	 */
	public function testAnUpdateOrANewDraftIsOneWrite(): void {
		$repository = $this->repository();
		$repository->save(record: ['fileId' => 7, 'status' => 'final'], uuid: 'version-1');
		$repository->save(record: ['fileId' => 8, 'status' => 'draft']);

		$this->assertSame(expected: [['final', 'version-1'], ['draft', null]], actual: $this->writes);
	}//end testAnUpdateOrANewDraftIsOneWrite()
}//end class

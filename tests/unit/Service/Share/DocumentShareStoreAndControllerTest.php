<?php

/**
 * The documentShare store over OpenRegister, and the share routes' answers
 * (share-document-links).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Share;

use InvalidArgumentException;
use OCA\Filinq\Controller\DocumentShareController;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Share\DocumentShareService;
use OCA\Filinq\Service\Share\DocumentShareStore;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectFindOptions;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Share\DocumentShareStore
 * @covers \OCA\Filinq\Controller\DocumentShareController
 */
class DocumentShareStoreAndControllerTest extends TestCase {

	private function store(ObjectService $objects): DocumentShareStore {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new DocumentShareStore($resolver);
	}

	public function testARecordIsSavedInFilinqsRegisterAsTheSystem(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->expects($this->once())->method('saveObject')
			->with(
				['kind' => 'document', 'status' => 'active'],
				$this->callback(
					static fn (ObjectSaveOptions $options): bool => $options->target->register === 'filinq'
						&& $options->target->schema === 'documentShare'
						&& $options->uuid === null
						&& $options->actor->unowned === true
				),
				false
			)
			->willReturnCallback(static fn (): array => ['kind' => 'document', 'status' => 'active', '@self' => ['id' => 'u-1']]);

		$saved = $this->store($objects)->save(['kind' => 'document', 'status' => 'active', 'uuid' => 'ignored', '@self' => []], '');

		$this->assertSame('u-1', $saved['uuid']);
	}

	public function testARefusedWriteSaysSo(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willThrowException(new RuntimeException('schema missing'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('OpenRegister refused the share record: schema missing');
		$this->store($objects)->save(['kind' => 'document']);
	}

	public function testTheActiveRecordsAreReadBySlugWithoutTheCallersRights(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'documentShare', ['status' => 'active'], false, false)
			->willReturn([['@self' => ['id' => 'a'], 'status' => 'active'], ['@self' => ['id' => 'b'], 'status' => 'revoked'], ['status' => 'active'], 'junk']);

		$this->assertSame([['@self' => ['id' => 'a'], 'status' => 'active', 'uuid' => 'a']], $this->store($objects)->active());

		$down = $this->createMock(ObjectService::class);
		$down->method('searchObjectsBySlug')->willThrowException(new RuntimeException('down'));
		$this->expectExceptionMessage('The share records could not be read: down');
		$this->store($down)->active();
	}

	public function testARecordIsFoundOrNot(): void {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(
			static function (string $id, ?ObjectFindOptions $options = null): mixed {
				if ($id === 'u-1' && $options?->register === 'filinq' && $options->schema === 'documentShare') {
					return ['uuid' => 'u-1', 'status' => 'active'];
				}

				throw new RuntimeException('not found');
			}
		);
		$store = $this->store($objects);

		$this->assertSame(['uuid' => 'u-1', 'status' => 'active'], $store->find('u-1'));
		$this->assertNull($store->find('u-2'));
		$this->assertNull($store->find(''));
	}

	private function controller(DocumentShareService $shares, array $params = [], ?LoggerInterface $logger = null): DocumentShareController {
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(static fn (string $key, mixed $default = null): mixed => ($params[$key] ?? $default));

		return new DocumentShareController('filinq', $request, $shares, $logger ?? $this->createMock(LoggerInterface::class));
	}

	public function testASharedDocumentAnswers201WithTheLink(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->expects($this->once())->method('share')
			->with(['42'], '', '2026-11-27', null, 'Advies', true)
			->willReturn(['share' => ['uuid' => 's-1'], 'url' => 'https://cloud.example/s/x', 'expiresAt' => '2026-11-27', 'expiryShortened' => false]);

		$response = $this->controller($shares, ['fileId' => '42', 'expiresOn' => '2026-11-27', 'password' => '', 'note' => 'Advies', 'labelConfirmed' => 'true'])->create();

		$this->assertSame(201, $response->getStatus());
		$this->assertSame('https://cloud.example/s/x', $response->getData()['url']);
	}

	public function testARefusalKeepsItsStatusAndReason(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->method('share')->willThrowException(new InvalidArgumentException('This document is labelled "Confidential". Confirm before sharing it by link.', 409));
		$shares->method('revoke')->willThrowException(new RuntimeException('Only the person who made the link, or an admin, can take it back', 403));
		$controller = $this->controller($shares, ['fileId' => 42, 'password' => 'p']);

		$created = $controller->create();
		$this->assertSame([409, ['error' => 'This document is labelled "Confidential". Confirm before sharing it by link.']], [$created->getStatus(), $created->getData()]);

		$revoked = $controller->revoke('s-1');
		$this->assertSame(403, $revoked->getStatus());
	}

	public function testAnUnexpectedFailureIsA500WithoutDetail(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->method('revoke')->willThrowException(new RuntimeException('SQLSTATE secret detail', 500));
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('error')->with('Sharing by link failed', ['exception' => 'SQLSTATE secret detail']);

		$response = $this->controller($shares, [], $logger)->revoke('s-1');

		$this->assertSame([500, ['error' => 'The link could not be made or taken back']], [$response->getStatus(), $response->getData()]);
	}

	public function testSeveralDocumentsGoToTheServiceAsOneListWithTheBundleName(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->expects($this->once())->method('share')
			->with([7, 8, 9], 'Bezwaar 2026-031', '', 'pw', '', false)
			->willReturn(['share' => ['uuid' => 's-2'], 'url' => 'https://cloud.example/s/y', 'expiresAt' => '2026-11-27', 'expiryShortened' => false]);

		$response = $this->controller($shares, ['fileIds' => ['a' => 7, 'b' => 8, 'c' => 9], 'name' => 'Bezwaar 2026-031', 'password' => 'pw'])->create();

		$this->assertSame(201, $response->getStatus());
	}

	public function testThePreflightAnswers200OrItsRefusal(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->expects($this->exactly(2))->method('preflight')->willReturnCallback(
			static function (array $fileIds): array {
				if ($fileIds === []) {
					throw new InvalidArgumentException('Choose at least one document to share', 400);
				}

				return ['maxDays' => 7, 'labels' => [], 'bundle' => null, 'fileIds' => $fileIds];
			}
		);

		$ok = $this->controller($shares, ['fileId' => 42])->preflight();
		$this->assertSame([200, [42]], [$ok->getStatus(), $ok->getData()['fileIds']]);

		$refused = $this->controller($shares, ['fileId' => ['nested']])->preflight();
		$this->assertSame([400, ['error' => 'Choose at least one document to share']], [$refused->getStatus(), $refused->getData()]);
	}

	public function testATakenBackLinkAnswers200(): void {
		$shares = $this->createMock(DocumentShareService::class);
		$shares->expects($this->once())->method('revoke')->with('s-1')->willReturn(['uuid' => 's-1', 'status' => 'revoked']);

		$this->assertSame(['uuid' => 's-1', 'status' => 'revoked'], $this->controller($shares)->revoke('s-1')->getData());
	}
}

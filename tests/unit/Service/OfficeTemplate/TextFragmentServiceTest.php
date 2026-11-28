<?php

/**
 * Text fragments are managed in the templates register through OpenRegister:
 * required fields and slug shape are checked, a slug is unique within its
 * namespace, and texts are looked up by exact slug.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Kernel\Contracts\OpenRegisterResolverInterface;
use OCA\Filinq\Service\OfficeTemplate\TemplatesRegisterObjects;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectDeletion;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\OfficeTemplate\TextFragmentService
 * @covers \OCA\Filinq\Service\OfficeTemplate\TemplatesRegisterObjects
 * @uses   \OCA\Filinq\Service\Support\OpenRegisterAccess
 */
class TextFragmentServiceTest extends TestCase {

	/**
	 * OpenRegister's object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The installed apps.
	 *
	 * @var array<int, string>
	 */
	private array $installed = ['openregister'];

	/**
	 * Build the service over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$this->objects->method('buildSearchQuery')->willReturnCallback(
			static fn (array $requestParams=[], string $register='', string $schema=''): array => ['params' => $requestParams, 'register' => $register, 'schema' => $schema]
		);
	}//end setUp()

	/**
	 * A fragment is created in the templates register under its own schema.
	 *
	 * @return void
	 */
	public function testAFragmentIsCreatedInTheTemplatesRegister(): void {
		$this->objects->method('searchObjectsPaginated')->willReturn(['results' => [], 'total' => 0]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				['name' => 'Ondertekening', 'slug' => 'ondertekening', 'content' => 'Hoogachtend', 'namespace' => 'filinq'],
				$this->callback(
					static fn (ObjectSaveOptions $options): bool => $options->target->register === 'templates'
						&& $options->target->schema === 'textFragment'
						&& $options->uuid === null
				)
			)
			->willReturn(['id' => 'f-1', 'slug' => 'ondertekening']);

		$saved = $this->service()->create(data: ['name' => 'Ondertekening', 'slug' => ' ondertekening ', 'content' => 'Hoogachtend', 'namespace' => 'filinq', 'owner' => 'x']);

		$this->assertSame(['id' => 'f-1', 'slug' => 'ondertekening'], $saved);
	}//end testAFragmentIsCreatedInTheTemplatesRegister()

	/**
	 * A slug another fragment in the namespace has is refused with 409.
	 *
	 * @return void
	 */
	public function testATakenSlugIsRefused(): void {
		$this->objects->method('searchObjectsPaginated')->willReturn(
			['results' => [['id' => 'f-2', 'slug' => 'ondertekening', 'namespace' => 'filinq']], 'total' => 1]
		);
		$this->objects->expects($this->never())->method('saveObject');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(409);

		$this->service()->create(data: ['name' => 'X', 'slug' => 'ondertekening', 'content' => 'Y', 'namespace' => 'filinq']);
	}//end testATakenSlugIsRefused()

	/**
	 * A missing field or a malformed slug or namespace is refused with 400.
	 *
	 * @return void
	 */
	public function testMissingAndMalformedFieldsAreRefused(): void {
		$this->objects->expects($this->never())->method('saveObject');
		$cases = [
			'Content is required' => ['name' => 'X', 'slug' => 'x', 'namespace' => 'filinq'],
			'Slug must be lowercase letters and digits, separated by single hyphens' => ['name' => 'X', 'slug' => 'Bad Slug', 'content' => 'Y', 'namespace' => 'filinq'],
			'Invalid namespace: must be lowercase alphanumeric only' => ['name' => 'X', 'slug' => 'x', 'content' => 'Y', 'namespace' => 'Fil-inq'],
		];
		foreach ($cases as $message => $data) {
			try {
				$this->service()->create(data: $data);
				$this->fail('Accepted: '.$message);
			} catch (Exception $e) {
				$this->assertSame([$message, 400], [$e->getMessage(), $e->getCode()]);
			}
		}
	}//end testMissingAndMalformedFieldsAreRefused()

	/**
	 * An update merges onto the stored fragment and may keep its own slug.
	 *
	 * @return void
	 */
	public function testAnUpdateMergesAndMayKeepItsSlug(): void {
		$stored = ['id' => 'f-1', 'name' => 'Oud', 'slug' => 'kop', 'content' => 'Oude tekst', 'namespace' => 'filinq', 'category' => 'koppen'];
		$this->objects->method('find')->willReturn($stored);
		$this->objects->method('searchObjectsPaginated')->willReturn(['results' => [$stored], 'total' => 1]);
		$this->objects->expects($this->once())->method('saveObject')
			->with(
				['name' => 'Oud', 'slug' => 'kop', 'content' => 'Nieuwe tekst', 'namespace' => 'filinq', 'category' => 'koppen'],
				$this->callback(
					static fn (ObjectSaveOptions $options): bool => $options->target->register === 'templates'
						&& $options->target->schema === 'textFragment'
						&& $options->uuid === 'f-1'
				)
			)
			->willReturn(['id' => 'f-1']);

		$this->assertSame(['id' => 'f-1'], $this->service()->update(id: 'f-1', data: ['content' => 'Nieuwe tekst']));
	}//end testAnUpdateMergesAndMayKeepItsSlug()

	/**
	 * Texts come back by exact slug and namespace; others are absent.
	 *
	 * @return void
	 */
	public function testTextsAreLookedUpByExactSlug(): void {
		$this->objects->method('searchObjectsPaginated')->willReturnCallback(
			static function (array $query): array {
				$slug = $query['params']['slug'];
				$rows = [
					'kop' => [['slug' => 'kop', 'namespace' => 'filinq', 'content' => 'De kop']],
					'voet' => [['slug' => 'voet-oud', 'namespace' => 'filinq', 'content' => 'Lijkt erop']],
				];
				return ['results' => ($rows[$slug] ?? []), 'total' => 1];
			}
		);

		$this->assertSame(['kop' => 'De kop'], $this->service()->texts(slugs: ['kop', 'voet', 'kop'], namespace: 'filinq'));
	}//end testTextsAreLookedUpByExactSlug()

	/**
	 * An unknown fragment is a 404, and is not deleted.
	 *
	 * @return void
	 */
	public function testAnUnknownFragmentIsNotFound(): void {
		$this->objects->method('find')->willReturn(null);
		$this->objects->expects($this->never())->method('deleteObject');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->service()->delete(id: 'nope');
	}//end testAnUnknownFragmentIsNotFound()

	/**
	 * A known fragment is deleted from the templates register.
	 *
	 * @return void
	 */
	public function testAKnownFragmentIsDeleted(): void {
		$this->objects->method('find')->willReturn(['id' => 'f-1']);
		$this->objects->expects($this->once())->method('deleteObject')->with(
			$this->callback(
				static fn (ObjectDeletion $deletion): bool => $deletion->uuid === 'f-1'
					&& $deletion->target->register === 'templates'
					&& $deletion->target->schema === 'textFragment'
			)
		);

		$this->service()->delete(id: 'f-1');
	}//end testAKnownFragmentIsDeleted()

	/**
	 * A list reads the page OpenRegister returns, with its total.
	 *
	 * @return void
	 */
	public function testAListReadsThePage(): void {
		$this->objects->method('searchObjectsPaginated')->willReturnCallback(
			static fn (array $query): array => ['results' => [['params' => $query['params'], 'schema' => $query['schema']]], 'total' => 7]
		);

		$this->assertSame(
			['results' => [['params' => ['category' => 'kop', '_limit' => 5, '_offset' => 10], 'schema' => 'textFragment']], 'total' => 7],
			$this->service()->list(filters: ['category' => 'kop'], limit: 5, offset: 10)
		);
	}//end testAListReadsThePage()

	/**
	 * Without OpenRegister nothing is read or written.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterNothingIsRead(): void {
		$this->installed = [];

		$this->expectException(RuntimeException::class);

		$this->service()->list();
	}//end testWithoutOpenRegisterNothingIsRead()

	/**
	 * The service over the mocked object service.
	 *
	 * @return TextFragmentService
	 */
	private function service(): TextFragmentService {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($this->installed);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$resolver = $this->createMock(OpenRegisterResolverInterface::class);
		$resolver->method('getRegisterAndSchema')->willReturn(['register' => 'templates', 'schema' => 'template']);

		return new TextFragmentService(
			objects: new TemplatesRegisterObjects(openRegister: new OpenRegisterAccess(apps: $apps, container: $container), resolver: $resolver)
		);
	}//end service()
}//end class

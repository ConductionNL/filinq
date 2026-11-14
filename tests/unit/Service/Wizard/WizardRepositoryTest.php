<?php

/**
 * Unit tests for WizardRepository
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Wizard
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

namespace OCA\Filinq\Tests\Unit\Service\Wizard;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Wizard\WizardRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Wizard definitions are read and written in filinq/wizardDefinition, come
 * back with their uuid and version, and store failures carry an HTTP-shaped
 * code.
 */
#[CoversClass(WizardRepository::class)]
class WizardRepositoryTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The subject.
	 *
	 * @var WizardRepository
	 */
	private WizardRepository $repository;

	/**
	 * Build the subject over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->repository = new WizardRepository(objectResolver: $resolver);
	}//end setUp()

	/**
	 * A blank uuid answers null without asking the store.
	 *
	 * @return void
	 */
	public function testFindWithABlankUuidIsNull(): void {
		$this->objects->expects($this->never())->method('find');

		$this->assertNull($this->repository->find(uuid: '  '));
	}//end testFindWithABlankUuidIsNull()

	/**
	 * Find reads the wizard schema and lifts uuid and version out of @self.
	 *
	 * @return void
	 */
	public function testFindNormalisesTheStoredWizard(): void {
		$entity = new class {
			public function jsonSerialize(): array {
				return ['@self' => ['id' => 'w-1', 'version' => '3'], 'name' => 'Intake', 'steps' => [1]];
			}
		};
		$this->objects->expects($this->once())
			->method('find')
			->with('w-1', 'filinq', 'wizardDefinition')
			->willReturn($entity);

		$this->assertSame(
			['name' => 'Intake', 'steps' => [1], 'uuid' => 'w-1', 'version' => '3'],
			$this->repository->find(uuid: 'w-1')
		);
	}//end testFindNormalisesTheStoredWizard()

	/**
	 * A missing wizard and a failing read are both null.
	 *
	 * @return void
	 */
	public function testFindOfAMissingOrUnreadableWizardIsNull(): void {
		$this->objects->method('find')->willReturnOnConsecutiveCalls(
			null,
			$this->throwException(new \RuntimeException('db down'))
		);

		$this->assertNull($this->repository->find(uuid: 'gone'));
		$this->assertNull($this->repository->find(uuid: 'broken'));
	}//end testFindOfAMissingOrUnreadableWizardIsNull()

	/**
	 * A row that is neither array nor serialisable normalises to nothing.
	 *
	 * @return void
	 */
	public function testANonArrayRowIsEmpty(): void {
		$this->objects->method('find')->willReturn('not a row');

		$this->assertSame([], $this->repository->find(uuid: 'w-2'));
	}//end testANonArrayRowIsEmpty()

	/**
	 * forTemplate searches by slug with the template filter and drops unreadable rows.
	 *
	 * @return void
	 */
	public function testForTemplateSearchesByTemplate(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'wizardDefinition', ['templateId' => 't-9'])
			->willReturn([
				['object' => ['name' => 'A'], 'uuid' => 'a', 'version' => '1'],
				'garbage',
				['id' => 'b', 'name' => 'B'],
			]);

		$this->assertSame(
			[
				['name' => 'A', 'uuid' => 'a', 'version' => '1'],
				['id' => 'b', 'name' => 'B', 'uuid' => 'b', 'version' => ''],
			],
			$this->repository->forTemplate(templateId: 't-9')
		);
	}//end testForTemplateSearchesByTemplate()

	/**
	 * A blank template id finds nothing without searching.
	 *
	 * @return void
	 */
	public function testForABlankTemplateIsEmpty(): void {
		$this->objects->expects($this->never())->method('searchObjectsBySlug');

		$this->assertSame([], $this->repository->forTemplate(templateId: ''));
	}//end testForABlankTemplateIsEmpty()

	/**
	 * active() filters on active=true; a non-array answer is an empty list.
	 *
	 * @return void
	 */
	public function testActiveFiltersOnActive(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'wizardDefinition', ['active' => true])
			->willReturn(0);

		$this->assertSame([], $this->repository->active());
	}//end testActiveFiltersOnActive()

	/**
	 * Save strips identity fields, passes the uuid, and falls back to it when the store returns none.
	 *
	 * @return void
	 */
	public function testSaveStripsIdentityAndKeepsTheUuid(): void {
		$this->objects->expects($this->once())
			->method('saveObject')
			->with(['name' => 'Intake'], 'filinq', 'wizardDefinition', 'w-5')
			->willReturn(['name' => 'Intake']);

		$saved = $this->repository->save(
			wizard: ['name' => 'Intake', 'uuid' => 'x', '@self' => ['id' => 'x'], 'id' => 'x', 'version' => '9'],
			uuid: 'w-5'
		);

		$this->assertSame(['name' => 'Intake', 'uuid' => 'w-5', 'version' => ''], $saved);
	}//end testSaveStripsIdentityAndKeepsTheUuid()

	/**
	 * Save without a uuid creates, and an empty uuid is not passed on.
	 *
	 * @return void
	 */
	public function testSaveWithoutAUuidCreates(): void {
		$this->objects->expects($this->exactly(2))
			->method('saveObject')
			->with(['name' => 'New'], 'filinq', 'wizardDefinition')
			->willReturn(['@self' => ['id' => 'w-new', 'version' => '1'], 'name' => 'New']);

		$this->assertSame(['name' => 'New', 'uuid' => 'w-new', 'version' => '1'], $this->repository->save(wizard: ['name' => 'New']));
		$this->assertSame(['name' => 'New', 'uuid' => 'w-new', 'version' => '1'], $this->repository->save(wizard: ['name' => 'New'], uuid: ''));
	}//end testSaveWithoutAUuidCreates()

	/**
	 * A refused save answers 403; any other failure 422.
	 *
	 * @return void
	 */
	public function testSaveFailuresCarryAnHttpCode(): void {
		$denied = new \RuntimeException('not yours', 401);
		$broken = new \RuntimeException('invalid', 500);
		$this->objects->method('saveObject')->willReturnOnConsecutiveCalls(
			$this->throwException($denied),
			$this->throwException($broken)
		);

		foreach ([[$denied, 403, 'not yours'], [$broken, 422, 'invalid']] as [$cause, $code, $message]) {
			try {
				$this->repository->save(wizard: ['name' => 'X']);
				$this->fail('Expected a RuntimeException');
			} catch (RuntimeException $e) {
				$this->assertSame('The wizard could not be saved: ' . $message, $e->getMessage());
				$this->assertSame($code, $e->getCode());
				$this->assertSame($cause, $e->getPrevious());
			}
		}
	}//end testSaveFailuresCarryAnHttpCode()

	/**
	 * Delete removes the object by uuid.
	 *
	 * @return void
	 */
	public function testDeleteRemovesTheObject(): void {
		$this->objects->expects($this->once())->method('deleteObject')->with('w-7')->willReturn(true);

		$this->repository->delete(uuid: 'w-7');
	}//end testDeleteRemovesTheObject()

	/**
	 * A failed delete answers 403 with the cause.
	 *
	 * @return void
	 */
	public function testAFailedDeleteIsRefused(): void {
		$cause = new \RuntimeException('locked');
		$this->objects->method('deleteObject')->willThrowException($cause);

		try {
			$this->repository->delete(uuid: 'w-8');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('The wizard could not be deleted: locked', $e->getMessage());
			$this->assertSame(403, $e->getCode());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAFailedDeleteIsRefused()
}//end class

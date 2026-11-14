<?php

/**
 * PolicyCrudService: the grondslagen (`bases`) on prohibitions and standing
 * consents, and the matcher's cache dropped on every rule write.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\ConsentService;
use OCA\Filinq\Service\PolicyCrudService;
use OCA\Filinq\Service\PolicyMatchService;
use OCA\Filinq\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\PolicyCrudService
 */
class PolicyCrudServiceBasesTest extends TestCase {

	/**
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objectService;

	/**
	 * @var PolicyMatchService&MockObject
	 */
	private PolicyMatchService&MockObject $matcher;

	/**
	 * What saveObject() was handed, per call.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->saved         = [];
		$this->objectService = $this->createMock(ObjectService::class);
		$this->objectService->method('saveObject')->willReturnCallback(
			function (array $object): ObjectEntity {
				$this->saved[] = $object;
				$entity = new ObjectEntity();
				$entity->setObject($object);
				return $entity;
			}
		);
		$this->matcher = $this->createMock(PolicyMatchService::class);

	}//end setUp()

	/**
	 * The service for an administrator.
	 *
	 * @return PolicyCrudService
	 */
	private function service(): PolicyCrudService {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getObjectService')->willReturn($this->objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->with('admin')->willReturn(true);

		return new PolicyCrudService(
			$settings,
			$this->createMock(ConsentService::class),
			$groups,
			$session,
			$this->createMock(LoggerInterface::class),
			$this->matcher
		);

	}//end service()

	public function testBasesAreStoredAsTrimmedUniqueSlugs(): void {
		$this->service()->createProhibition(['primaryName' => 'Getuige A', 'bases' => [' woo-art-5-1-2-e ', '', 'woo-art-5-1-2-e', 'woo-art-5-2']]);

		$this->assertSame(['woo-art-5-1-2-e', 'woo-art-5-2'], $this->saved[0]['bases']);

	}//end testBasesAreStoredAsTrimmedUniqueSlugs()

	public function testARuleWithoutBasesIsLeftWithout(): void {
		$this->service()->createProhibition(['primaryName' => 'Getuige A']);

		$this->assertArrayNotHasKey('bases', $this->saved[0]);

	}//end testARuleWithoutBasesIsLeftWithout()

	public function testNullBasesClearTheList(): void {
		$this->service()->updateProhibition('p1', ['bases' => null]);

		$this->assertSame([], $this->saved[0]['bases']);

	}//end testNullBasesClearTheList()

	/**
	 * Anything but a list of strings is refused before anything is written.
	 *
	 * @return void
	 */
	public function testMalformedBasesAreRefusedBeforeTheWrite(): void {
		$this->matcher->expects($this->never())->method('invalidateCache');
		$service = $this->service();

		foreach (['woo-art-5-1-2-e', ['a' => 'woo'], ['woo', 3]] as $bases) {
			try {
				$service->createStandingConsent(['entityText' => 'Gemeente', 'bases' => $bases]);
				$this->fail('accepted ' . json_encode($bases));
			} catch (InvalidArgumentException $e) {
				$this->assertSame('bases must be a list of grondslag slugs', $e->getMessage());
			}
		}

		$this->assertSame([], $this->saved);

	}//end testMalformedBasesAreRefusedBeforeTheWrite()

	public function testAStandingConsentKeepsItsBasesThroughCreate(): void {
		$result = $this->service()->createStandingConsent(['entityText' => 'Gemeente Voorbeeldstad', 'bases' => ['woo-art-5-1-2-e']]);

		$this->assertSame(['woo-art-5-1-2-e'], $result['bases']);
		$this->assertSame('entity', $this->saved[0]['scope']);

	}//end testAStandingConsentKeepsItsBasesThroughCreate()

	/**
	 * Every rule write and delete drops the matcher's cached rules.
	 *
	 * @return void
	 */
	public function testEveryRuleWriteDropsTheMatchersCache(): void {
		$this->objectService->method('find')->willReturn(
			new class implements \JsonSerializable {
				public function jsonSerialize(): array {
					return ['scope' => 'entity', 'entityText' => 'Gemeente'];
				}
			}
		);
		$this->matcher->expects($this->exactly(5))->method('invalidateCache');
		$service = $this->service();

		$service->createProhibition(['primaryName' => 'A']);
		$service->updateProhibition('p1', ['primaryName' => 'B']);
		$service->deleteProhibition('p1');
		$service->createStandingConsent(['entityText' => 'Gemeente']);
		$service->deleteStandingConsent('c1');

	}//end testEveryRuleWriteDropsTheMatchersCache()
}//end class

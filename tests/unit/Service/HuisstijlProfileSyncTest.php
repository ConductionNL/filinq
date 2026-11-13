<?php

/**
 * One managed house style per thematiq token set, filled from the profile.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayObject;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\HuisstijlProfileMapper;
use OCA\Filinq\Service\HuisstijlProfileSync;
use OCA\Filinq\Service\HuisstijlWriteGate;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\HuisstijlProfileSync
 * @uses   \OCA\Filinq\Service\HuisstijlProfileMapper
 * @uses   \OCA\Filinq\Service\HuisstijlWriteGate
 */
class HuisstijlProfileSyncTest extends TestCase {

	private ContainerInterface&MockObject $container;

	private IAppManager&MockObject $appManager;

	private ObjectService&MockObject $objectService;

	private IUserSession&MockObject $userSession;

	private LoggerInterface&MockObject $logger;

	/**
	 * The users the stub profile service was asked for.
	 *
	 * @var ArrayObject<int, string|null>
	 */
	private ArrayObject $asked;

	protected function setUp(): void {
		parent::setUp();

		$this->container = $this->createMock(ContainerInterface::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->objectService = $this->createMock(ObjectService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->asked = new ArrayObject();
	}//end setUp()

	/**
	 * The documented thematiq profile of one token set.
	 *
	 * @param string $logo The logo URL.
	 *
	 * @return array<string, mixed> The profile.
	 */
	private function profile(string $logo = '/apps/thematiq/document-style/logo?v=1'): array {
		return [
			'tokenSet' => ['id' => 'bergen', 'name' => 'Gemeente Bergen'],
			'organisation' => 'Gemeente Bergen',
			'logo' => ['url' => $logo, 'mime' => 'image/png'],
			'cover' => ['url' => '/apps/thematiq/document-style/cover?v=2', 'mime' => 'image/jpeg'],
			'colours' => ['primary' => '#154273', 'primaryText' => '#ffffff', 'text' => '#000000', 'background' => '#ffffff', 'accent' => null],
			'fonts' => [
				'heading' => ['family' => 'Fira Sans', 'url' => '/apps/thematiq/font/3'],
				'body' => ['family' => 'Noto Serif', 'url' => null],
			],
			'footer' => [
				'lines' => ['Gemeente Bergen', 'Postbus 175, 1860 AD Bergen'],
				'accessibilityUrl' => 'https://bergen.nl/toegankelijkheid',
				'privacyUrl' => '',
			],
			'warnings' => [],
		];
	}//end profile()

	/**
	 * A sync whose thematiq answers with the given profile.
	 *
	 * @param array<string, mixed>|null $profile The profile; null when thematiq is not enabled.
	 *
	 * @return HuisstijlProfileSync The sync.
	 */
	private function sync(?array $profile): HuisstijlProfileSync {
		$this->appManager->method('isEnabledForUser')->with('thematiq')->willReturn($profile !== null);

		$service = new class($profile, $this->asked) {
			/**
			 * @param array<string, mixed>|null $profile
			 * @param ArrayObject<int, string|null> $asked
			 */
			public function __construct(private ?array $profile, private ArrayObject $asked) {
			}

			/**
			 * @return array<string, mixed>
			 */
			public function forUser(?string $uid): array {
				$this->asked[] = $uid;

				return ($this->profile ?? []);
			}
		};
		$this->container->method('get')->willReturnCallback(
			static function (string $id) use ($service) {
				if ($id === HuisstijlProfileSync::PROFILE_SERVICE) {
					return $service;
				}

				throw new RuntimeException('unexpected ' . $id);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		return new HuisstijlProfileSync(
			$this->container,
			$this->appManager,
			$resolver,
			new HuisstijlProfileMapper(),
			new HuisstijlWriteGate(),
			$this->userSession,
			$this->logger
		);
	}//end sync()

	public function testWithoutThematiqNothingIsReadOrWritten(): void {
		$sync = $this->sync(null);

		$this->container->expects($this->never())->method('get');
		$this->objectService->expects($this->never())->method('searchObjectsBySlug');
		$this->objectService->expects($this->never())->method('saveObject');

		$this->assertNull($sync->syncFor('clerk'));
	}//end testWithoutThematiqNothingIsReadOrWritten()

	public function testAMissingProfileServiceMeansNoHouseStyle(): void {
		$this->appManager->method('isEnabledForUser')->willReturn(true);
		$this->container->method('get')->willThrowException(new RuntimeException('no such service'));
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->expects($this->never())->method('resolve');

		$sync = new HuisstijlProfileSync($this->container, $this->appManager, $resolver, new HuisstijlProfileMapper(), new HuisstijlWriteGate(), $this->userSession, $this->logger);

		$this->assertNull($sync->syncFor('clerk'));
	}//end testAMissingProfileServiceMeansNoHouseStyle()

	public function testTheFirstUseCreatesOneManagedHouseStyleThroughTheOpenGate(): void {
		$sync = $this->sync($this->profile());
		$this->objectService->method('searchObjectsBySlug')->willReturn([]);

		$gateWasOpen = null;
		$written = null;
		$this->objectService->expects($this->once())->method('saveObject')->willReturnCallback(
			function (...$args) use (&$gateWasOpen, &$written) {
				$gateWasOpen = (new HuisstijlWriteGate())->isOpen();
				$written = array_combine(['object', 'register', 'schema', 'uuid', '_rbac', '_multitenancy'], array_slice($args, 0, 6));

				return $args[0] + ['@self' => ['id' => 'hs-1']];
			}
		);

		$result = $sync->syncFor('clerk-bergen');

		$this->assertTrue($gateWasOpen);
		$this->assertFalse((new HuisstijlWriteGate())->isOpen());
		$this->assertSame('filinq', $written['register']);
		$this->assertSame('huisstijl', $written['schema']);
		$this->assertNull($written['uuid']);
		$this->assertFalse($written['_rbac']);
		$this->assertFalse($written['_multitenancy']);
		$this->assertSame('thematiq', $written['object']['managedBy']);
		$this->assertSame('bergen', $written['object']['tokenSetId']);
		$this->assertSame(['clerk-bergen'], $this->asked->getArrayCopy());
		$this->assertSame('hs-1', $result['@self']['id']);
		$this->assertSame('Gemeente Bergen', $result['name']);
	}//end testTheFirstUseCreatesOneManagedHouseStyleThroughTheOpenGate()

	public function testAnUnchangedProfileWritesNothing(): void {
		$sync = $this->sync($this->profile());
		$stored = (new HuisstijlProfileMapper())->mapProfile($this->profile()) + ['@self' => ['id' => 'hs-1']];

		$this->objectService->expects($this->once())->method('searchObjectsBySlug')
			->with('filinq', 'huisstijl', ['managedBy' => 'thematiq', 'tokenSetId' => 'bergen'], false, false)
			->willReturn([$stored]);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->assertSame($stored, $sync->syncFor('clerk'));
	}//end testAnUnchangedProfileWritesNothing()

	public function testAChangedProfileUpdatesTheStoredHouseStyle(): void {
		$sync = $this->sync($this->profile('/apps/thematiq/document-style/logo?v=9'));
		$old = (new HuisstijlProfileMapper())->mapProfile($this->profile()) + ['@self' => ['id' => 'hs-1']];

		$this->objectService->method('searchObjectsBySlug')->willReturn([$old]);
		$this->objectService->expects($this->once())->method('saveObject')->willReturnCallback(
			function (...$args) {
				$this->assertSame('hs-1', $args[3]);
				$this->assertSame('/apps/thematiq/document-style/logo?v=9', $args[0]['logo']);

				return null;
			}
		);

		$result = $sync->syncFor('clerk');

		// Nothing usable came back from the save: the mapped values stand in.
		$this->assertSame('/apps/thematiq/document-style/logo?v=9', $result['logo']);
	}//end testAChangedProfileUpdatesTheStoredHouseStyle()

	public function testAHandMadeRowWithTheSameTokenSetIsNotTakenOver(): void {
		$sync = $this->sync($this->profile());
		$this->objectService->method('searchObjectsBySlug')->willReturn([['name' => 'Eigen', 'tokenSetId' => 'bergen', 'id' => 'own-1']]);
		$this->objectService->expects($this->once())->method('saveObject')->willReturnCallback(
			function (...$args) {
				$this->assertNull($args[3]);

				return [];
			}
		);

		$this->assertSame('thematiq', $sync->syncFor('clerk')['managedBy']);
	}//end testAHandMadeRowWithTheSameTokenSetIsNotTakenOver()

	public function testWithoutANamedUserTheSignedInUserIsAsked(): void {
		$sync = $this->sync($this->profile());
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk-uitgeest');
		$this->userSession->method('getUser')->willReturn($user);
		$this->objectService->method('searchObjectsBySlug')->willReturn([]);
		$this->objectService->method('saveObject')->willReturn([]);

		$sync->syncFor(null);

		$this->assertSame(['clerk-uitgeest'], $this->asked->getArrayCopy());
	}//end testWithoutANamedUserTheSignedInUserIsAsked()

	public function testWithoutAnyUserTheInstanceDefaultIsAsked(): void {
		$sync = $this->sync($this->profile());
		$this->userSession->method('getUser')->willReturn(null);
		$this->objectService->method('searchObjectsBySlug')->willReturn([]);
		$this->objectService->method('saveObject')->willReturn([]);

		$sync->syncFor(null);

		$this->assertSame([null], $this->asked->getArrayCopy());
	}//end testWithoutAnyUserTheInstanceDefaultIsAsked()

	public function testAProfileWithoutATokenSetIsIgnored(): void {
		$profile = $this->profile();
		unset($profile['tokenSet']);
		$sync = $this->sync($profile);
		$this->objectService->expects($this->never())->method('saveObject');

		$this->assertNull($sync->syncFor('clerk'));
	}//end testAProfileWithoutATokenSetIsIgnored()

	public function testAFailingWriteIsLoggedAndLeavesNoHouseStyle(): void {
		$sync = $this->sync($this->profile());
		$this->objectService->method('searchObjectsBySlug')->willReturn([]);
		$this->objectService->method('saveObject')->willThrowException(new RuntimeException('register down'));
		$this->logger->expects($this->once())->method('warning')
			->with($this->stringContains('register down'), ['uid' => 'clerk']);

		$this->assertNull($sync->syncFor('clerk'));
		$this->assertFalse((new HuisstijlWriteGate())->isOpen());
	}//end testAFailingWriteIsLoggedAndLeavesNoHouseStyle()
}//end class

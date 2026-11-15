<?php

/**
 * Golden tests for how NativeSigningProvider finds a stored signing session.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;

/**
 * Pins the query, the row shapes accepted, the exact-match rule and the failure answers.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NativeSigningSessionLookupGoldenTest extends TestCase {

	/**
	 * The findAll calls the provider made, as [config, rbac].
	 *
	 * @var array<int, array{0: array<string, mixed>, 1: bool}>
	 */
	private array $calls = [];

	/**
	 * Errors the provider logged.
	 *
	 * @var array<int, string>
	 */
	private array $errors = [];

	/**
	 * Look a session up through a provider whose register answers `$results` (or throws it).
	 *
	 * @param mixed  $results     What findAll answers, or a Throwable to throw.
	 * @param string $externalId  The id to look up.
	 * @param bool   $withService Whether OpenRegister is available.
	 *
	 * @return array<string, mixed>|null
	 */
	private function lookup(mixed $results, string $externalId, bool $withService = true): ?array {
		$this->calls = [];
		$this->errors = [];

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => ($key === 'signingSession_register') ? 'reg-7' : $default
		);

		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['findAll', 'saveObject'])
			->getMock();
		$objectService->method('findAll')->willReturnCallback(
			function (array $config = [], bool $_rbac = true) use ($results): mixed {
				$this->calls[] = [$config, $_rbac];
				if ($results instanceof \Throwable) {
					throw $results;
				}

				return $results;
			}
		);

		$settingsService = $this->createMock(SettingsService::class);
		$settingsService->method('getObjectService')->willReturn(($withService === true) ? $objectService : null);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('error')->willReturnCallback(
			function (string $message): void {
				$this->errors[] = $message;
			}
		);

		$provider = new NativeSigningProvider(logger: $logger, settingsService: $settingsService, config: $config);
		$method = new ReflectionMethod($provider, 'loadRawSessionByExternalId');

		return $method->invoke($provider, $externalId);

	}//end lookup()

	/**
	 * The lookup asks the configured register and schema, outside RBAC, filtered on the external id.
	 *
	 * @return void
	 */
	public function testTheQueryFiltersOnTheExternalId(): void {
		$this->assertNull($this->lookup([], 'ext-1'));

		$this->assertSame(
			[[['filters' => ['register' => 'reg-7', 'schema' => 'signingSession', 'externalId' => 'ext-1']], false]],
			$this->calls
		);

	}//end testTheQueryFiltersOnTheExternalId()

	/**
	 * Only a row whose externalId matches exactly is returned; arrays, serialisable and object rows count.
	 *
	 * @return void
	 */
	public function testOnlyAnExactlyMatchingRowIsFound(): void {
		$serialised = new class {
			public function jsonSerialize(): array { return ['externalId' => 'ext-1', 'via' => 'json']; }
			public function getObject(): array { return ['externalId' => 'ext-1', 'via' => 'object']; }
		};
		$objectOnly = new class {
			public function jsonSerialize(): string { return 'x'; }
			public function getObject(): array { return ['externalId' => 'ext-1', 'via' => 'object']; }
		};
		$unreadable = new class {
			public function getObject(): string { return 'x'; }
		};

		$this->assertSame(['externalId' => 'ext-1', 'via' => 'json'], $this->lookup([$serialised], 'ext-1'));
		$this->assertSame(['externalId' => 'ext-1', 'via' => 'object'], $this->lookup([$objectOnly], 'ext-1'));
		$this->assertSame(
			['externalId' => 'ext-1', 'n' => 2],
			$this->lookup(['skip', $unreadable, new \stdClass(), ['externalId' => 'ext-10'], ['n' => 1], ['externalId' => 'ext-1', 'n' => 2], ['externalId' => 'ext-1', 'n' => 3]], 'ext-1')
		);
		$this->assertNull($this->lookup([['externalId' => 1]], '1'));
		$this->assertNull($this->lookup([['externalId' => 'EXT-1']], 'ext-1'));

	}//end testOnlyAnExactlyMatchingRowIsFound()

	/**
	 * No OpenRegister, no rows or a failing register give null; only the failure is logged.
	 *
	 * @return void
	 */
	public function testFailuresAnswerNull(): void {
		$this->assertNull($this->lookup([['externalId' => 'ext-1']], 'ext-1', false));
		$this->assertSame([], $this->calls);

		$this->assertNull($this->lookup([], 'ext-1'));
		$this->assertSame([], $this->errors);

		$this->assertNull($this->lookup(new RuntimeException('register down'), 'ext-2'));
		$this->assertSame(['Failed to load signing session ext-2: register down'], $this->errors);

	}//end testFailuresAnswerNull()
}//end class

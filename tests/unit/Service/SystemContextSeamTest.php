<?php

/**
 * Unit tests for SystemContextSeam
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

use InvalidArgumentException;
use OCA\Filinq\Service\SystemContextSeam;
use OCA\OpenRegister\Contract\ObjectServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The seam elevates through OpenRegister, logs reason and caller, and refuses a missing reason.
 *
 * @covers \OCA\Filinq\Service\SystemContextSeam
 *
 * @spec openspec/changes/multi-tenant-hardening/specs/multi-tenant-hardening/spec.md#requirement-openregister-enforcement-is-on-by-default-bypasses-are-removed-req-ddmth-002
 */
class SystemContextSeamTest extends TestCase {

	/**
	 * The object service.
	 *
	 * @var ObjectServiceInterface&MockObject
	 */
	private ObjectServiceInterface&MockObject $objectService;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface&MockObject $logger;

	/**
	 * Fresh doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objectService = $this->createMock(ObjectServiceInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	/**
	 * The operation runs inside runAsSystem and its result is returned; the use is logged with reason and caller.
	 *
	 * @return void
	 */
	public function testTheOperationRunsAsTheSystemAndIsLogged(): void {
		$this->objectService->expects($this->once())->method('runAsSystem')
			->willReturnCallback(static fn (callable $operation): array => ['system' => $operation()]);
		$this->logger->expects($this->once())->method('info')->with(
			'Filinq ran an operation as the system, across organisations',
			[
				'reason' => 'objection-deadline sweep over every organisation',
				'caller' => self::class . '::' . __FUNCTION__,
			]
		);

		$result = (new SystemContextSeam(logger: $this->logger))->run(
			objectService: $this->objectService,
			reason: '  objection-deadline sweep over every organisation ',
			operation: static fn (): string => 'swept'
		);

		self::assertSame(['system' => 'swept'], $result);
	}//end testTheOperationRunsAsTheSystemAndIsLogged()

	/**
	 * Reasons that are not one short line.
	 *
	 * @return array<string, array{0: string}> The reasons.
	 */
	public static function badReasonProvider(): array {
		return [
			'empty' => [''],
			'blank' => ['   '],
			'multi-line' => ["sweep\nand more"],
			'carriage return' => ["sweep\rmore"],
			'nul byte' => ["swe\0ep"],
			'too long' => [str_repeat('x', 201)],
		];
	}//end badReasonProvider()

	/**
	 * A missing or malformed reason is refused before anything runs or is logged.
	 *
	 * @param string $reason The reason.
	 *
	 * @dataProvider badReasonProvider
	 *
	 * @return void
	 */
	public function testABadReasonIsRefusedBeforeAnythingRuns(string $reason): void {
		$this->objectService->expects($this->never())->method('runAsSystem');
		$this->logger->expects($this->never())->method('info');
		$ran = false;

		try {
			(new SystemContextSeam(logger: $this->logger))->run(
				objectService: $this->objectService,
				reason: $reason,
				operation: static function () use (&$ran): void {
					$ran = true;
				}
			);
			self::fail('A bad reason must be refused.');
		} catch (InvalidArgumentException $e) {
			self::assertStringContainsString('needs a short, single-line reason', $e->getMessage());
		}

		self::assertFalse($ran);
	}//end testABadReasonIsRefusedBeforeAnythingRuns()

	/**
	 * A reason of exactly the maximum length is accepted.
	 *
	 * @return void
	 */
	public function testAReasonAtTheLimitIsAccepted(): void {
		$this->objectService->method('runAsSystem')->willReturnCallback(static fn (callable $operation): mixed => $operation());

		self::assertSame(
			1,
			(new SystemContextSeam(logger: $this->logger))->run(objectService: $this->objectService, reason: str_repeat('x', 200), operation: static fn (): int => 1)
		);
	}//end testAReasonAtTheLimitIsAccepted()

	/**
	 * Called from a closure, the caller is the class and the closure that called it.
	 *
	 * @return void
	 */
	public function testAClosureCallerIsNamed(): void {
		$this->objectService->method('runAsSystem')->willReturnCallback(static fn (callable $operation): mixed => $operation());
		$this->logger->expects($this->once())->method('info')
			->with($this->anything(), $this->callback(static fn (array $context): bool => str_starts_with($context['caller'], self::class . '::') === true
				&& str_contains($context['caller'], '{closure}') === true));

		$seam = new SystemContextSeam(logger: $this->logger);
		$results = array_map(fn (string $reason): mixed => $seam->run(objectService: $this->objectService, reason: $reason, operation: static fn (): int => 1), ['mapped']);

		self::assertSame([1], $results);
	}//end testAClosureCallerIsNamed()

	/**
	 * No controller reaches the seam, so no request parameter can steer it.
	 *
	 * @return void
	 */
	public function testNoControllerReferencesTheSeam(): void {
		$root = dirname(__DIR__, 3) . '/lib/Controller';
		$offenders = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
			if ($file->getExtension() === 'php' && str_contains((string)file_get_contents($file->getPathname()), 'SystemContextSeam') === true) {
				$offenders[] = $file->getPathname();
			}
		}

		self::assertSame([], $offenders);
	}//end testNoControllerReferencesTheSeam()
}//end class

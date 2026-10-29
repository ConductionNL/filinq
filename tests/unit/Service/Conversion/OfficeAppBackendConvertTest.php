<?php

/**
 * Office app backend: every outcome of convert(), with its attempts and log line.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\OfficeAppBackend;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Locks what convert() calls, returns and throws.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OfficeAppBackendConvertTest extends TestCase {

	/**
	 * Every call to the folder, the manager and the logger, in order.
	 *
	 * @var array<int, mixed>
	 */
	private array $calls = [];

	/**
	 * A backend over a source `/anna/files/Zaken/brief.v2.docx`.
	 *
	 * @param array<string, mixed> $world What the manager does, whether a PDF exists and what the lookup finds.
	 *
	 * @return array{0: OfficeAppBackend, 1: File, 2: mixed} The backend, the source and the node the lookup answers.
	 */
	private function backend(array $world): array {
		$converted = $this->createMock(originalClassName: File::class);
		$existing = $this->createMock(originalClassName: File::class);
		$existing->method('delete')->willReturnCallback(
			function (): void {
				$this->calls[] = 'delete existing';
			}
		);

		$folder = $this->createMock(originalClassName: Folder::class);
		$folder->method('getPath')->willReturn('/anna/files/Zaken');
		$folder->method('nodeExists')->willReturnCallback(
			function (string $name) use ($world): bool {
				$this->calls[] = 'nodeExists ' . $name;
				return $world['pdfExists'];
			}
		);
		$lookups = 0;
		$folder->method('get')->willReturnCallback(
			function (string $name) use ($world, $existing, $converted, &$lookups): mixed {
				$this->calls[] = 'get ' . $name;
				$lookups++;
				if ($world['pdfExists'] === true && $lookups === 1) {
					return $existing;
				}

				if ($world['lookup'] === 'missing') {
					throw new NotFoundException('gone');
				}

				if ($world['lookup'] === 'folder') {
					return $this->createMock(originalClassName: Folder::class);
				}

				return $converted;
			}
		);

		$source = $this->createMock(originalClassName: File::class);
		$source->method('getName')->willReturn('brief.v2.docx');
		$source->method('getParent')->willReturn($folder);

		$manager = $this->createMock(originalClassName: IConversionManager::class);
		$manager->method('convert')->willReturnCallback(
			function (File $file, string $mime, ?string $destination) use ($world, $source): string {
				$this->calls[] = 'convert ' . ($file === $source ? 'source' : 'other') . ' ' . $mime . ' ' . $destination;
				if ($world['managerFails'] === true) {
					throw new RuntimeException(message: 'provider down');
				}

				return 'Zaken/brief.v2.pdf';
			}
		);

		$session = $this->createMock(originalClassName: IUserSession::class);
		$session->method('getUser')->willReturn($world['user'] === true ? $this->createMock(originalClassName: IUser::class) : null);

		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			function (string $message, array $context = []): void {
				$this->calls[] = ['warning' => $message, 'context' => $context];
			}
		);

		$backend = new OfficeAppBackend(
			conversionManager: $world['manager'] === true ? $manager : null,
			rootFolder: $this->createMock(originalClassName: IRootFolder::class),
			userSession: $session,
			appConfig: $this->createMock(originalClassName: IAppConfig::class),
			logger: $logger,
		);

		return [$backend, $source, $converted];
	}//end backend()

	/**
	 * A world where everything works and no PDF exists yet.
	 *
	 * @param array<string, mixed> $changes What differs.
	 *
	 * @return array<string, mixed> The world.
	 */
	private function world(array $changes): array {
		return array_merge(
			['manager' => true, 'user' => true, 'pdfExists' => false, 'managerFails' => false, 'lookup' => 'file'],
			$changes
		);
	}//end world()

	/**
	 * Convert and catch the failure.
	 *
	 * @param array<string, mixed> $changes What differs from the working world.
	 *
	 * @return ConversionFailedException The failure.
	 */
	private function failure(array $changes): ConversionFailedException {
		[$backend, $source] = $this->backend(world: $this->world(changes: $changes));
		try {
			$backend->convert(source: $source);
		} catch (ConversionFailedException $e) {
			return $e;
		}

		$this->fail(message: 'Expected a conversion failure');
	}//end failure()

	/**
	 * The attempts and the previous message of a failure.
	 *
	 * @param ConversionFailedException $failure The failure.
	 *
	 * @return array<string, mixed> Message, attempts and the previous exception's message.
	 */
	private function describe(ConversionFailedException $failure): array {
		$previous = $failure->getPrevious();
		return [
			'message'  => $failure->getMessage(),
			'attempts' => $failure->getAttempts(),
			'previous' => $previous instanceof Throwable ? $previous->getMessage() : null,
		];
	}//end describe()

	/**
	 * A stale PDF is deleted, the manager converts next to the source, and the new node is returned.
	 *
	 * @return void
	 */
	public function testConvertReplacesAStalePdf(): void {
		[$backend, $source, $converted] = $this->backend(world: $this->world(changes: ['pdfExists' => true]));

		$this->assertSame(expected: $converted, actual: $backend->convert(source: $source));
		$this->assertSame(
			expected: [
				'nodeExists brief.v2.pdf',
				'get brief.v2.pdf',
				'delete existing',
				'convert source application/pdf /anna/files/Zaken/brief.v2.pdf',
				'get brief.v2.pdf',
			],
			actual: $this->calls
		);
	}//end testConvertReplacesAStalePdf()

	/**
	 * Without a manager nothing is touched.
	 *
	 * @return void
	 */
	public function testConvertWithoutManager(): void {
		$this->assertSame(
			expected: [
				'message'  => 'OfficeAppBackend reached convert() without IConversionManager bound.',
				'attempts' => [['name' => 'office_app', 'available' => false, 'supports' => false, 'reason' => 'IConversionManager not present (NC < 31?)']],
				'previous' => null,
			],
			actual: $this->describe(failure: $this->failure(changes: ['manager' => false]))
		);
		$this->assertSame(expected: [], actual: $this->calls);
	}//end testConvertWithoutManager()

	/**
	 * Without a user nothing is touched.
	 *
	 * @return void
	 */
	public function testConvertWithoutUser(): void {
		$this->assertSame(
			expected: [
				'message'  => 'OfficeAppBackend requires a user session.',
				'attempts' => [['name' => 'office_app', 'available' => true, 'supports' => true, 'reason' => 'no active user session']],
				'previous' => null,
			],
			actual: $this->describe(failure: $this->failure(changes: ['user' => false]))
		);
		$this->assertSame(expected: [], actual: $this->calls);
	}//end testConvertWithoutUser()

	/**
	 * A manager failure is wrapped with its cause.
	 *
	 * @return void
	 */
	public function testConvertWhenTheManagerFails(): void {
		$this->assertSame(
			expected: [
				'message'  => 'IConversionManager->convert threw: provider down',
				'attempts' => [['name' => 'office_app', 'available' => true, 'supports' => true, 'reason' => 'manager convert exception: provider down']],
				'previous' => 'provider down',
			],
			actual: $this->describe(failure: $this->failure(changes: ['managerFails' => true]))
		);
		$this->assertSame(
			expected: ['nodeExists brief.v2.pdf', 'convert source application/pdf /anna/files/Zaken/brief.v2.pdf'],
			actual: $this->calls
		);
	}//end testConvertWhenTheManagerFails()

	/**
	 * A result that cannot be found is logged and wrapped.
	 *
	 * @return void
	 */
	public function testConvertWhenTheResultIsMissing(): void {
		$this->assertSame(
			expected: [
				'message'  => 'Office-app conversion reported success but the resulting PDF could not be located.',
				'attempts' => [['name' => 'office_app', 'available' => true, 'supports' => true, 'reason' => 'post-convert file lookup failed: gone']],
				'previous' => 'gone',
			],
			actual: $this->describe(failure: $this->failure(changes: ['lookup' => 'missing']))
		);
		$this->assertSame(
			expected: [
				'warning' => '[OfficeAppBackend] Conversion reported success but result lookup failed',
				'context' => [
					'expected'    => '/anna/files/Zaken/brief.v2.pdf',
					'manager_ret' => 'Zaken/brief.v2.pdf',
					'exception'   => NotFoundException::class,
				],
			],
			actual: end($this->calls)
		);
	}//end testConvertWhenTheResultIsMissing()

	/**
	 * A result that is not a file is refused.
	 *
	 * @return void
	 */
	public function testConvertWhenTheResultIsNotAFile(): void {
		$this->assertSame(
			expected: [
				'message'  => 'Office-app conversion result is not a regular file node.',
				'attempts' => [['name' => 'office_app', 'available' => true, 'supports' => true, 'reason' => 'result node is not File']],
				'previous' => null,
			],
			actual: $this->describe(failure: $this->failure(changes: ['lookup' => 'folder']))
		);
	}//end testConvertWhenTheResultIsNotAFile()
}//end class

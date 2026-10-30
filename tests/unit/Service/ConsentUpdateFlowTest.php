<?php

/**
 * Consent update: the reads, the write and the log lines per outcome.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Service\ConsentStatusChange;
use OCA\Filinq\Service\ConsentUpdateHandler;
use OCA\Filinq\Service\RegisterObjectRef;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Locks what updateConsentStatus reads, writes, logs and throws.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsentUpdateFlowTest extends TestCase {

	/**
	 * Every call to the object service and the logger, in order.
	 *
	 * @var array<int, mixed>
	 */
	private array $calls = [];

	/**
	 * A handler over a stored consent record.
	 *
	 * @param array<string, mixed>|null $stored The stored record, or null when it is not found.
	 * @param bool                      $saveFails Whether the save throws.
	 *
	 * @return ConsentUpdateHandler The handler.
	 */
	private function handler(?array $stored, bool $saveFails=false): ConsentUpdateHandler {
		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->method('find')->willReturnCallback(
			function (string $id, string $register, string $schema, bool $_rbac) use ($stored): ?ObjectEntity {
				$this->calls[] = ['find' => [$id, $register, $schema, $_rbac]];
				if ($stored === null) {
					return null;
				}

				$entity = new ObjectEntity();
				$entity->setObject($stored);
				return $entity;
			}
		);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object, string $register, string $schema, ?string $uuid = null, bool $_rbac = true) use ($saveFails): ObjectEntity {
				unset($object['statusEvents']);
				$this->calls[] = ['save' => [$object, $register, $schema, $uuid, $_rbac]];
				if ($saveFails === true) {
					throw new RuntimeException(message: 'db down');
				}

				$entity = new ObjectEntity();
				$entity->setObject(array_merge($object, ['saved' => true]));
				return $entity;
			}
		);

		$appManager = $this->createMock(originalClassName: IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($objects);

		$logger = $this->createMock(originalClassName: LoggerInterface::class);
		foreach (['info', 'error'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string $message, array $context = []) use ($level): void {
					if (isset($context['exception']) === true) {
						$context['exception'] = $context['exception']->getMessage();
					}

					$this->calls[] = ['log ' . $level => $message, 'context' => $context];
				}
			);
		}

		return new ConsentUpdateHandler(logger: $logger, container: $container, appManager: $appManager);
	}//end handler()

	/**
	 * Only whitelisted fields are merged, the rest of the record is kept.
	 *
	 * @return void
	 */
	public function testWhitelistedFieldsAreSaved(): void {
		$stored = ['consentStatus' => 'pending', 'notificationStatus' => 'pending', 'documentId' => 'd-1', 'policyMatch' => null];

		$result = $this->handler(stored: $stored)->updateConsentStatus(consent: new RegisterObjectRef(id: 'c-1', register: 'filinq', schema: 'publicationConsent'), change: new ConsentStatusChange(data: ['notificationStatus' => 'sent', 'objectionReason' => 'privacy', 'documentId' => 'd-9', 'extra' => 'x', 'evidence' => 'mail'], actor: 'ingrid'));

		$this->assertSame(
			expected: [
				['find' => ['c-1', 'filinq', 'publicationConsent', false]],
				[
					'save' => [
						['consentStatus' => 'pending', 'notificationStatus' => 'sent', 'documentId' => 'd-1', 'policyMatch' => null, 'objectionReason' => 'privacy'],
						'filinq',
						'publicationConsent',
						null,
						false,
					],
				],
				[
					'log info' => 'Consent status updated',
					'context'  => ['consentId' => 'c-1', 'updatedKeys' => ['notificationStatus', 'objectionReason', 'statusEvents']],
				],
			],
			actual: $this->calls
		);
		$this->assertTrue(condition: $result['saved']);
		$this->assertSame(expected: 'd-1', actual: $result['documentId']);
	}//end testWhitelistedFieldsAreSaved()

	/**
	 * A record that is not found is wrapped, logged and nothing is saved.
	 *
	 * @return void
	 */
	public function testMissingRecord(): void {
		$caught = $this->failure(handler: $this->handler(stored: null));

		$this->assertSame(expected: Exception::class, actual: get_class($caught));
		$this->assertSame(expected: 'Failed to update consent status: Consent record not found: c-1', actual: $caught->getMessage());
		$this->assertSame(expected: 'Consent record not found: c-1', actual: $caught->getPrevious()?->getMessage());
		$this->assertSame(
			expected: [
				['find' => ['c-1', 'filinq', 'publicationConsent', false]],
				[
					'log error' => 'Failed to update consent status: Consent record not found: c-1',
					'context'   => ['consentId' => 'c-1', 'exception' => 'Consent record not found: c-1'],
				],
			],
			actual: $this->calls
		);
	}//end testMissingRecord()

	/**
	 * A failed save is wrapped and logged.
	 *
	 * @return void
	 */
	public function testFailedSave(): void {
		$caught = $this->failure(handler: $this->handler(stored: ['notificationStatus' => 'pending'], saveFails: true));

		$this->assertSame(expected: 'Failed to update consent status: db down', actual: $caught->getMessage());
		$this->assertInstanceOf(expected: RuntimeException::class, actual: $caught->getPrevious());
		$this->assertSame(
			expected: ['log error' => 'Failed to update consent status: db down', 'context' => ['consentId' => 'c-1', 'exception' => 'db down']],
			actual: end($this->calls)
		);
	}//end testFailedSave()

	/**
	 * A guard refusal passes through unwrapped and unlogged.
	 *
	 * @return void
	 */
	public function testGuardRefusalPassesThrough(): void {
		$caught = $this->failure(handler: $this->handler(stored: ['policyMatch' => null]), data: ['policyMatch' => 'p-1']);

		$this->assertSame(expected: InvalidArgumentException::class, actual: get_class($caught));
		$this->assertSame(expected: [['find' => ['c-1', 'filinq', 'publicationConsent', false]]], actual: $this->calls);
	}//end testGuardRefusalPassesThrough()

	/**
	 * Run an update that is expected to throw.
	 *
	 * @param ConsentUpdateHandler $handler The handler.
	 * @param array<string, mixed> $data    The update.
	 *
	 * @return Throwable What it threw.
	 */
	private function failure(ConsentUpdateHandler $handler, array $data=['notificationStatus' => 'sent']): Throwable {
		try {
			$handler->updateConsentStatus(consent: new RegisterObjectRef(id: 'c-1', register: 'filinq', schema: 'publicationConsent'), change: new ConsentStatusChange(data: $data));
		} catch (Throwable $e) {
			return $e;
		}

		$this->fail(message: 'Expected the update to throw');
	}//end failure()
}//end class

<?php

/**
 * Unit tests for ConsentRecordWriter::recordNotification()
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\ConsentNotesHelper;
use OCA\Filinq\Service\ConsentPolicyReferentValidator;
use OCA\Filinq\Service\ConsentRecordWriter;
use OCA\Filinq\Service\ConsentScopeValidator;
use OCA\Filinq\Service\ObjectionDeadlineChecker;
use OCA\Filinq\Service\Support\ConsentRecordRules;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * How a consent request went is stored on its record through OpenRegister's save options.
 *
 * Seen live (NC 34, OpenRegister main): the save passed `register:` and `schema:`
 * as named arguments OpenRegister no longer has, so every consent request that
 * was sent answered 500 after the mail had gone out.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
class ConsentRecordNotificationTest extends TestCase {

	/**
	 * The outcome is merged onto the record and saved into the record's register and schema.
	 *
	 * @return void
	 */
	public function testTheOutcomeIsSavedIntoTheRecordsRegisterAndSchema(): void {
		$saved = $this->createMock(originalClassName: ObjectEntity::class);
		$saved->method('getObject')->willReturn(['id' => 'c-1', 'notificationStatus' => 'sent']);
		$objects = $this->createMock(originalClassName: ObjectService::class);
		$objects->expects($this->once())->method('saveObject')
			->with(
				['id' => 'c-1', 'notificationStatus' => 'sent'],
				$this->callback(
					callback: static fn (ObjectSaveOptions $o): bool => [$o->target->register, $o->target->schema] === ['26', '41']
				)
			)
			->willReturn($saved);
		$apps = $this->createMock(originalClassName: IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(originalClassName: ContainerInterface::class);
		$container->method('get')->willReturn($objects);

		$writer = new ConsentRecordWriter(
			logger: $this->createMock(originalClassName: LoggerInterface::class),
			openRegister: new OpenRegisterAccess(apps: $apps, container: $container),
			rules: new ConsentRecordRules(
				deadlineChecker: $this->createMock(originalClassName: ObjectionDeadlineChecker::class),
				notesHelper: new ConsentNotesHelper(),
				scopeValidator: $this->createMock(originalClassName: ConsentScopeValidator::class),
				referentValidator: $this->createMock(originalClassName: ConsentPolicyReferentValidator::class)
			)
		);

		$stored = $writer->recordNotification(
			record: ['id' => 'c-1', 'notificationStatus' => 'pending', 'wasUpdated' => false],
			fields: ['notificationStatus' => 'sent'],
			register: '26',
			schema: '41'
		);

		$this->assertSame(expected: ['id' => 'c-1', 'notificationStatus' => 'sent'], actual: $stored);

	}//end testTheOutcomeIsSavedIntoTheRecordsRegisterAndSchema()
}//end class

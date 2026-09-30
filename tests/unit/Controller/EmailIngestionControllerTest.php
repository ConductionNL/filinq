<?php

/**
 * EmailIngestionController tests
 *
 * The real controller, service and settings over the email-ingestion world.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/email-ingestion/tasks.md#2-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Controller;

require_once __DIR__ . '/../Service/EmailIngestion/EmailIngestionDoubles.php';

use OCA\Filinq\Controller\EmailIngestionController;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\EmailIngestion\EmailDocumentRepository;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Tests\Unit\Service\EmailIngestion\EmailInboxWorld;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * The status list, re-scan, retry and the inbox mapping.
 */
class EmailIngestionControllerTest extends TestCase {
	use EmailInboxWorld;

	/**
	 * Build the world.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->setUpWorld();

	}//end setUp()

	/**
	 * A re-scan by hand files the inbox and the list shows filed and failed rows, filterable.
	 *
	 * @return void
	 */
	public function testRescanThenListFiledAndFailed(): void {
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: self::eml(messageId: 'list@example.org'));
		$this->drop(folderId: self::INBOX, name: 'outlook.msg', content: 'cfbf');
		$controller = $this->controller();

		$scan = $controller->scan();
		$all = $controller->index()->getData()['results'];
		$failed = $controller->index(status: 'failed')->getData()['results'];

		$this->assertSame(200, $scan->getStatus());
		$this->assertSame(2, $scan->getData()['processed']);
		$this->assertCount(2, $all);
		$this->assertSame(['manual', 'manual'], array_column($all, 'ingestSource'));
		$this->assertCount(1, $failed);
		$this->assertSame('unsupported-format', $failed[0]['failureReason']);
		$this->assertNotSame('', $failed[0]['uuid']);

	}//end testRescanThenListFiledAndFailed()

	/**
	 * Retry answers 404 for an unknown record and 409 for a failed one.
	 *
	 * @return void
	 */
	public function testRetryRefusesUnknownAndFailedRecords(): void {
		$this->drop(folderId: self::INBOX, name: 'outlook.msg', content: 'cfbf');
		$controller = $this->controller();
		$controller->scan();
		$failedUuid = (string) array_key_first($this->store->rows);

		$this->assertSame(404, $controller->retry(uuid: 'nope')->getStatus());
		$this->assertSame(409, $controller->retry(uuid: $failedUuid)->getStatus());

	}//end testRetryRefusesUnknownAndFailedRecords()

	/**
	 * The mapping is read and saved; a broken one answers 400.
	 *
	 * @return void
	 */
	public function testSettingsReadAndSave(): void {
		$controller = $this->controller();

		$this->assertSame([['folderId' => 100, 'dossierRef' => 'dossier-017']], $controller->settings()->getData()['inboxes']);
		$this->assertSame(400, $controller->updateSettings(inboxes: [['folderId' => 0]], filesPerTick: 25)->getStatus());
		$saved = $controller->updateSettings(inboxes: [['folderId' => 7, 'dossierRef' => 'dossier-9']], filesPerTick: 10);
		$this->assertSame(['inboxes' => [['folderId' => 7, 'dossierRef' => 'dossier-9']], 'filesPerTick' => 10], $saved->getData());

	}//end testSettingsReadAndSave()

	/**
	 * Every action is for admins and delegated admins of the filinq settings.
	 *
	 * @return void
	 */
	public function testEveryActionIsAdminOnly(): void {
		$class = new ReflectionClass(EmailIngestionController::class);
		foreach (['index', 'scan', 'retry', 'settings', 'updateSettings'] as $method) {
			$this->assertCount(1, $class->getMethod($method)->getAttributes(AuthorizedAdminSetting::class), $method);
		}

	}//end testEveryActionIsAdminOnly()

	/**
	 * The controller.
	 *
	 * @return EmailIngestionController
	 */
	private function controller(): EmailIngestionController {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default = ''): string => ($this->config[$key] ?? $default));
		$config->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->config[$key] = $value;

				return true;
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);

		return new EmailIngestionController(
			appName: 'filinq',
			request: $this->createMock(IRequest::class),
			ingestion: $this->service(),
			repository: new EmailDocumentRepository(objectResolver: $resolver),
			settings: new EmailIngestionSettings(appConfig: $config),
			logger: $this->createMock(LoggerInterface::class),
		);

	}//end controller()
}//end class

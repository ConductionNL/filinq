<?php

/**
 * Unit tests for bulk send: validate first, then create isolated ordinary requests
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\BulkSigning
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service\BulkSigning;

require_once __DIR__ . '/BulkSigningDoubles.php';

use OCA\Filinq\BackgroundJob\BulkSigningJob;
use OCA\Filinq\Service\BulkSigning\BulkSigningRecipientParser;
use OCA\Filinq\Service\BulkSigning\BulkSigningRowValidator;
use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCA\Filinq\Service\BulkSigning\BulkSigningService;
use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\Signing\LibreSignClient;
use OCA\Filinq\Service\Signing\LibreSignProvider;
use OCA\Filinq\Service\Signing\NativeSigningProvider;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCA\Filinq\Service\Signing\ValidSignProvider;
use OCA\Filinq\Service\SigningRequestValidator;
use OCA\Filinq\Service\SigningService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Bulk send never sends before confirmation, never bypasses the single-request
 * gates, and never lets one failing row stop the others.
 */
class BulkSigningServiceTest extends TestCase {
	use BulkSigningDoubles;

	/**
	 * The ordinary single-request path.
	 *
	 * @var SigningService&MockObject
	 */
	private SigningService $signing;

	/**
	 * The job list confirmation queues on.
	 *
	 * @var IJobList&MockObject
	 */
	private IJobList $jobs;

	/**
	 * The session the job runs the rows under.
	 *
	 * @var IUserSession&MockObject
	 */
	private IUserSession $session;

	/**
	 * Users that exist on the instance.
	 *
	 * @var list<string>
	 */
	private array $knownUsers = ['alice', 'an'];

	/**
	 * The document the batch sends.
	 *
	 * @var array<string, string>
	 */
	private const SETTINGS = [
		'title' => 'Verklaring 2026',
		'documentFileId' => '4711',
		'documentName' => 'verklaring.pdf',
		'signatureLevel' => 'SES',
		'signingMode' => 'parallel',
		'provider' => 'native',
	];

	/**
	 * Build the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->signing = $this->createMock(SigningService::class);
		$this->jobs = $this->createMock(IJobList::class);
		$this->session = $this->createMock(IUserSession::class);

	}//end setUp()

	/**
	 * The service over the real parser, row validator and request validator.
	 *
	 * @return BulkSigningService
	 */
	private function service(): BulkSigningService {
		$settings = $this->createMock(SettingsService::class);
		$settings->method('getFeatureToggles')->willReturn(['signing_default_level' => 'SES', 'signing_provider' => 'native']);

		return new BulkSigningService(
			parser: new BulkSigningRecipientParser(),
			rowValidator: new BulkSigningRowValidator(userManager: $this->users()),
			requestValidator: new SigningRequestValidator(providerFactory: $this->providerFactory()),
			settings: $settings,
			repository: $this->batchRepository(),
			jobList: $this->jobs,
			signing: $this->signing
		);

	}//end service()

	/**
	 * The runner the background job calls.
	 *
	 * @return BulkSigningRunner
	 */
	private function runner(): BulkSigningRunner {
		return new BulkSigningRunner(
			repository: $this->batchRepository(),
			signing: $this->signing,
			userSession: $this->session,
			userManager: $this->users(),
			logger: $this->createMock(LoggerInterface::class)
		);

	}//end runner()

	/**
	 * A user manager that knows $knownUsers.
	 *
	 * @return IUserManager
	 */
	private function users(): IUserManager {
		$users = $this->createMock(IUserManager::class);
		$users->method('userExists')->willReturnCallback(fn (string $uid): bool => in_array($uid, $this->knownUsers, true));
		$users->method('get')->willReturnCallback(
			function (string $uid): ?IUser {
				if (in_array($uid, $this->knownUsers, true) === false) {
					return null;
				}

				$user = $this->createMock(IUser::class);
				$user->method('getUID')->willReturn($uid);
				return $user;
			}
		);

		return $users;

	}//end users()

	/**
	 * The real provider factory with the real native and ValidSign providers.
	 *
	 * @return SigningProviderFactory
	 */
	private function providerFactory(): SigningProviderFactory {
		$config = $this->createMock(IAppConfig::class);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturn(false);

		return new SigningProviderFactory(
			config: $config,
			nativeProvider: new NativeSigningProvider(
				logger: $this->createMock(LoggerInterface::class),
				settingsService: $this->createMock(SettingsService::class),
				config: $config
			),
			validSignProvider: new ValidSignProvider(config: $config),
			libreSignProvider: new LibreSignProvider(config: $config, client: $this->createMock(LibreSignClient::class)),
			appManager: $apps
		);

	}//end providerFactory()

	/**
	 * The 50-row fixture with three bad e-mail addresses.
	 *
	 * @return string
	 */
	private function fixture(): string {
		return (string) file_get_contents(__DIR__ . '/../../../fixtures/bulk-signing/recipients-50.csv');

	}//end fixture()

	/**
	 * Phase 1 names exactly the three bad rows and sends nothing.
	 *
	 * @return void
	 */
	public function testAMixedListYieldsAReportAndSendsNothing(): void {
		$this->signing->expects($this->never())->method('createRequest');
		$this->jobs->expects($this->never())->method('add');

		$batch = $this->service()->createBatch(settings: self::SETTINGS, content: $this->fixture(), filename: 'recipients-50.csv', userId: 'alice');

		$this->assertSame('ready', $batch['status']);
		$this->assertSame(50, $batch['totalRows']);
		$this->assertSame(47, $batch['acceptedRows']);
		$this->assertSame([4, 18, 43], array_column($batch['rejectedRows'], 'row'));
		$this->assertSame(['invalid-email'], array_values(array_unique(array_column($batch['rejectedRows'], 'reason'))));
		$this->assertSame([], $batch['requestRefs']);
		$this->assertSame('csv', $batch['recipientSource']);
		$this->assertSame('alice', $batch['createdBy']);

	}//end testAMixedListYieldsAReportAndSendsNothing()

	/**
	 * Confirmation queues one job; the job creates 47 ordinary requests and ends with errors.
	 *
	 * @return void
	 */
	public function testConfirmationCreatesOneOrdinaryRequestPerAcceptedRow(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: $this->fixture(), filename: 'recipients-50.csv', userId: 'alice');

		$this->jobs->expects($this->once())->method('add')->with(BulkSigningJob::class, ['batchId' => $batch['uuid'], 'userId' => 'alice']);
		$confirmed = $service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
		$this->assertSame('creating', $confirmed['status']);

		$sent = [];
		$this->signing->method('createRequest')->willReturnCallback(
			function (array $data) use (&$sent): array {
				$sent[] = $data;
				return ['id' => 'req-' . count($sent)];
			}
		);
		$users = [];
		$this->session->method('setVolatileActiveUser')->willReturnCallback(
			function (?IUser $user) use (&$users): void {
				$users[] = $user?->getUID();
			}
		);

		$done = $this->runner()->run(batchId: $batch['uuid'], userId: 'alice');

		$this->assertCount(47, $sent);
		$this->assertSame(
			['documentFileId' => '4711', 'documentName' => 'verklaring.pdf', 'signatureLevel' => 'SES', 'signingMode' => 'parallel', 'provider' => 'native',
				'signers' => [['userId' => '', 'email' => 'burger1@example.invalid', 'displayName' => 'Burger 1']]],
			$sent[0]
		);
		$this->assertSame('completed_with_errors', $done['status']);
		$this->assertCount(47, $done['requestRefs']);
		$this->assertSame(47, $done['processedRows']);
		$this->assertSame([4, 18, 43], array_column($done['rejectedRows'], 'row'));
		$this->assertSame(['alice', null], $users, 'The rows run as the initiator, and the session is cleared after.');

	}//end testConfirmationCreatesOneOrdinaryRequestPerAcceptedRow()

	/**
	 * One row failing at creation is recorded; every other row is still created.
	 *
	 * @return void
	 */
	public function testAFailingRowDoesNotAbortTheBatch(): void {
		$service = $this->service();
		$csv = "email\nan@example.invalid\ngone@example.invalid\nbo@example.invalid\n";
		$batch = $service->createBatch(settings: self::SETTINGS, content: $csv, filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);

		$this->signing->method('createRequest')->willReturnCallback(
			static function (array $data): array {
				if ($data['signers'][0]['email'] === 'gone@example.invalid') {
					throw new RuntimeException('Every signer needs a user or an e-mail address', 400);
				}

				return ['id' => 'req-' . $data['signers'][0]['email']];
			}
		);

		$done = $this->runner()->run(batchId: $batch['uuid'], userId: 'alice');

		$this->assertSame(['req-an@example.invalid', 'req-bo@example.invalid'], $done['requestRefs']);
		$this->assertSame([['row' => 3, 'reason' => 'creation-failed', 'detail' => 'Every signer needs a user or an e-mail address']], $done['rejectedRows']);
		$this->assertSame('completed_with_errors', $done['status']);

	}//end testAFailingRowDoesNotAbortTheBatch()

	/**
	 * A clean list ends completed.
	 *
	 * @return void
	 */
	public function testACleanListEndsCompleted(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: "userId\nan\n", filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
		$this->signing->method('createRequest')->willReturn(['id' => 'req-1']);

		$done = $this->runner()->run(batchId: $batch['uuid'], userId: 'alice');

		$this->assertSame('completed', $done['status']);
		$this->assertSame(['req-1'], $done['requestRefs']);

	}//end testACleanListEndsCompleted()

	/**
	 * QES with the native provider is refused as a single request is, and nothing is stored.
	 *
	 * @return void
	 */
	public function testABatchPassesTheSameHonestyGateAsASingleRequest(): void {
		try {
			$this->service()->createBatch(
				settings: array_merge(self::SETTINGS, ['signatureLevel' => 'QES']),
				content: $this->fixture(),
				filename: 'recipients-50.csv',
				userId: 'alice'
			);
			$this->fail('A QES batch on the native provider was accepted.');
		} catch (RuntimeException $e) {
			$this->assertSame(400, $e->getCode());
		}

		$this->assertSame([], $this->batchWrites);

	}//end testABatchPassesTheSameHonestyGateAsASingleRequest()

	/**
	 * A batch without a document is refused as a single request is.
	 *
	 * @return void
	 */
	public function testABatchWithoutADocumentIsRefused(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionCode(400);

		$this->service()->createBatch(settings: array_merge(self::SETTINGS, ['documentFileId' => '']), content: "email\na@example.invalid\n", filename: 'x.csv', userId: 'alice');

	}//end testABatchWithoutADocumentIsRefused()

	/**
	 * Unknown users, rows naming nobody and duplicates are rejected with their reason.
	 *
	 * @return void
	 */
	public function testUnknownUsersEmptyRowsAndDuplicatesAreRejected(): void {
		$csv = "email;userId;name\n;ghost;\nan@example.invalid;;\nAN@example.invalid;;\n;an;\n;an;\n;;Iemand\n";

		$batch = $this->service()->createBatch(settings: self::SETTINGS, content: $csv, filename: 'x.csv', userId: 'alice');

		$this->assertSame(
			[
				['row' => 2, 'reason' => 'unknown-user', 'detail' => 'ghost'],
				['row' => 4, 'reason' => 'duplicate', 'detail' => 'row 3'],
				['row' => 6, 'reason' => 'duplicate', 'detail' => 'row 5'],
				['row' => 7, 'reason' => 'no-recipient', 'detail' => ''],
			],
			$batch['rejectedRows']
		);
		$this->assertSame(2, $batch['acceptedRows']);

	}//end testUnknownUsersEmptyRowsAndDuplicatesAreRejected()

	/**
	 * Only the initiator or an admin reads, confirms or cancels a batch.
	 *
	 * @return void
	 */
	public function testOnlyTheInitiatorOrAnAdminReachesTheBatch(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: "userId\nan\n", filename: 'x.csv', userId: 'alice');

		$this->assertNull($service->get(id: $batch['uuid'], userId: 'bob', isAdmin: false));
		$this->assertNull($service->confirm(id: $batch['uuid'], userId: 'bob', isAdmin: false));
		$this->assertNull($service->cancel(id: $batch['uuid'], userId: 'bob', isAdmin: false));
		$this->assertSame([], $service->listFor(userId: 'bob', isAdmin: false));
		$this->assertSame($batch['uuid'], $service->get(id: $batch['uuid'], userId: 'root', isAdmin: true)['uuid']);
		$this->assertCount(1, $service->listFor(userId: 'alice', isAdmin: false));

	}//end testOnlyTheInitiatorOrAnAdminReachesTheBatch()

	/**
	 * A batch is confirmed once; a list with nobody to send to cannot be confirmed.
	 *
	 * @return void
	 */
	public function testABatchIsConfirmedOnceAndNeverEmpty(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: "userId\nan\n", filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);

		try {
			$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
			$this->fail('A batch was confirmed twice.');
		} catch (RuntimeException $e) {
			$this->assertSame(409, $e->getCode());
		}

		$empty = $service->createBatch(settings: self::SETTINGS, content: "userId\nghost\n", filename: 'x.csv', userId: 'alice');
		$this->expectExceptionCode(409);
		$service->confirm(id: $empty['uuid'], userId: 'alice', isAdmin: false);

	}//end testABatchIsConfirmedOnceAndNeverEmpty()

	/**
	 * Cancel stops the batch and cancels only the member requests that can still be cancelled.
	 *
	 * @return void
	 */
	public function testCancelCancelsOnlyWhatCanStillBeCancelled(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: "userId\nan\nalice\n", filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
		$this->signing->method('createRequest')->willReturnOnConsecutiveCalls(['id' => 'r1'], ['id' => 'r2']);
		$this->runner()->run(batchId: $batch['uuid'], userId: 'alice');

		$cancelled = [];
		$this->signing->method('cancelRequest')->willReturnCallback(
			static function (string $id) use (&$cancelled): array {
				if ($id === 'r2') {
					throw new RuntimeException('Cannot cancel request in status: COMPLETED');
				}

				$cancelled[] = $id;
				return ['id' => $id, 'status' => 'CANCELLED'];
			}
		);

		$result = $service->cancel(id: $batch['uuid'], userId: 'alice', isAdmin: false);

		$this->assertSame(['r1'], $cancelled);
		$this->assertSame('cancelled', $result['status']);
		$this->assertSame(1, $result['cancelledRequests']);

	}//end testCancelCancelsOnlyWhatCanStillBeCancelled()

	/**
	 * A batch cancelled before its job runs creates nothing.
	 *
	 * @return void
	 */
	public function testACancelledBatchCreatesNothing(): void {
		$service = $this->service();
		$batch = $service->createBatch(settings: self::SETTINGS, content: "userId\nan\n", filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
		$service->cancel(id: $batch['uuid'], userId: 'alice', isAdmin: false);

		$this->signing->expects($this->never())->method('createRequest');

		$done = $this->runner()->run(batchId: $batch['uuid'], userId: 'alice');

		$this->assertSame('cancelled', $done['status']);

	}//end testACancelledBatchCreatesNothing()
	/**
	 * Every payload the service and the runner write, and the demo row, validate against the real schema fragment.
	 *
	 * @return void
	 */
	public function testEveryPayloadWrittenValidatesAgainstTheRegisterSchema(): void {
		$service = $this->service();
		$csv = "email;userId;name\nan@example.invalid;;An\nkapot;;\n;ghost;\ngone@example.invalid;;\n";
		$batch = $service->createBatch(settings: self::SETTINGS, content: $csv, filename: 'x.csv', userId: 'alice');
		$service->confirm(id: $batch['uuid'], userId: 'alice', isAdmin: false);
		$this->signing->method('createRequest')->willReturnCallback(
			static fn (array $data): array => $data['signers'][0]['email'] === 'gone@example.invalid' ? throw new RuntimeException(str_repeat('x', 900)) : ['id' => 'r1']
		);
		$this->runner()->run(batchId: $batch['uuid'], userId: 'alice');
		$service->cancel(id: $batch['uuid'], userId: 'alice', isAdmin: false);

		$descriptor = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$mock = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_mock_register.json'), true);
		$schema = $descriptor['components']['schemas']['bulkSigningBatch'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => $schema['required'], 'properties' => $properties, 'additionalProperties' => false]);
		$demo = array_values(array_filter($mock['components']['objects'], static fn (array $o): bool => ($o['@self']['schema'] ?? '') === 'bulkSigningBatch'));
		$this->assertCount(3, $demo);
		$demo = array_map(static fn (array $o): array => array_diff_key($o, ['@self' => true]), $demo);

		$this->assertGreaterThanOrEqual(6, count($this->batchWrites));
		foreach (array_merge($this->batchWrites, $demo) as $payload) {
			$result = (new \Opis\JsonSchema\Validator())->validate(json_decode((string) json_encode($payload)), $json);
			$message = '';
			if ($result->isValid() === false) {
				$message = (string) json_encode((new \Opis\JsonSchema\Errors\ErrorFormatter())->format($result->error()));
			}

			$this->assertTrue($result->isValid(), $message);
		}

		$this->assertContains('bulkSigningBatch', $descriptor['components']['registers']['filinq']['schemas']);
		$this->assertSame(['read' => [], 'create' => ['authenticated'], 'update' => [], 'delete' => []], $schema['authorization']);

	}//end testEveryPayloadWrittenValidatesAgainstTheRegisterSchema()
}//end class

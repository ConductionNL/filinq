<?php

/**
 * Unit tests for SchemaFieldFillService
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/extraction-model-filled-fields/specs/schema-field-suggestions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\FieldFill;

use OCA\Filinq\Exception\FieldFillRefused;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FieldFill\FieldFillProfileService;
use OCA\Filinq\Service\FieldFill\FieldValueCheck;
use OCA\Filinq\Service\FieldFill\SchemaFieldFillService;
use OCA\Filinq\Service\FieldFill\TextPassage;
use OCA\Filinq\Service\OcrService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\TaskProcessing\Task;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Suggesting declared fields from a file and writing only what a person decides.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\FieldFill
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SchemaFieldFillServiceTest extends TestCase {

	private const TEXT = "Subsidieaanvraag\nAanvrager: Stichting Groen Dorp\nBedrag: 12500\nGewenste start: 2027-03-01";

	/** @var array<int, string> Prompts the model received. */
	private array $prompts = [];

	/** @var string What the model answers. */
	private string $answer = '{}';

	/** @var bool Whether a TextToText provider exists. */
	private bool $provider = true;

	/** @var bool The switch. */
	private bool $enabled = true;

	/** @var array<int, array<string, mixed>> Every saveObject call. */
	private array $saved = [];

	/** @var array<string, mixed> Records find() answers, by id. */
	private array $records = [];

	/** @var array<int, array<string, mixed>> Profiles stored. */
	private array $profiles = [];

	/** @var bool Whether the session user can read the file. */
	private bool $fileReadable = true;

	/** @var bool Whether saving the record is refused. */
	private bool $refuseRecordWrite = false;

	/** @var array<int, array<string, mixed>> Every find() call. */
	private array $finds = [];

	/**
	 * Set up a declared subsidy schema and record.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->records = [
			'rec-1' => ['@self' => ['id' => 'rec-1'], 'applicant' => null, 'amount' => null, 'startDate' => '2026-01-01', 'status' => 'open'],
		];
		$this->profiles = [
			['declaringApp' => 'dossiq', 'register' => 'subsidies', 'schema' => 'subsidieaanvraag', 'fields' => ['applicant', 'amount', 'startDate', 'bsn'], 'active' => true],
		];

	}//end setUp()

	/**
	 * The service over the doubles.
	 *
	 * @param bool $admin Whether the user is an administrator.
	 *
	 * @return SchemaFieldFillService
	 */
	private function service(bool $admin = false): SchemaFieldFillService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturnCallback(function (string $id = '', string $register = '', string $schema = '') {
			$this->finds[] = ['id' => $id, 'register' => $register, 'schema' => $schema];
			return ($this->records[$id] ?? null);
		});
		$objects->method('saveObject')->willReturnCallback(function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null) {
			$this->saved[] = ['object' => $object, 'register' => $register, 'schema' => $schema, 'uuid' => $uuid];
			if ($this->refuseRecordWrite === true && $schema === 'subsidieaanvraag') {
				throw new RuntimeException('Permission denied', 403);
			}

			return array_merge($object, ['@self' => ['id' => ($uuid ?? 'sug-1')]]);
		});
		$objects->method('searchObjectsBySlug')->willReturnCallback(fn (): array => $this->profiles);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$schema = new class {
			/**
			 * The properties.
			 *
			 * @return array<string, array<string, mixed>>
			 */
			public function getProperties(): array {
				return [
					'applicant' => ['type' => 'string', 'title' => 'Aanvrager', 'visible' => true],
					'amount' => ['type' => 'number', 'minimum' => 0],
					'startDate' => ['type' => 'string', 'format' => 'date'],
					'bsn' => ['type' => 'string', 'format' => 'bsn'],
					'status' => ['type' => 'string', 'enum' => ['open', 'closed']],
				];
			}
		};
		$schemaMapper = new class($schema) {
			/**
			 * Constructor.
			 *
			 * @param object $schema The schema.
			 */
			public function __construct(private object $schema) {
			}

			/**
			 * Find.
			 *
			 * @param mixed $id The id.
			 *
			 * @return object
			 */
			public function find(mixed $id): object {
				return $this->schema;
			}
		};
		$manager = new class($this) {
			/**
			 * Constructor.
			 *
			 * @param SchemaFieldFillServiceTest $test The test.
			 */
			public function __construct(private SchemaFieldFillServiceTest $test) {
			}

			/**
			 * Run a task.
			 *
			 * @param Task $task The task.
			 *
			 * @return Task
			 */
			public function runTask(Task $task): Task {
				return $this->test->modelRuns(task: $task);
			}

			/**
			 * The provider.
			 *
			 * @param string $type The task type.
			 *
			 * @return object
			 */
			public function getPreferredProvider(string $type): object {
				return $this->test->providerFor(type: $type);
			}
		};
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static function (string $id) use ($manager, $schemaMapper): object {
			return match ($id) {
				'OCP\TaskProcessing\IManager' => $manager,
				'OCA\OpenRegister\Db\SchemaMapper' => $schemaMapper,
				default => throw new RuntimeException('no ' . $id),
			};
		});

		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn('image/png');
		$file->method('getName')->willReturn('aanvraag.png');
		$file->method('getContent')->willReturn('png');
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (): array => ($this->fileReadable === true ? [$file] : []));
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->willReturn($folder);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		$ocr = $this->createMock(OcrService::class);
		$ocr->method('needsOcr')->willReturn(true);
		$ocr->method('isTesseractAvailable')->willReturn(true);
		$ocr->method('getOcrLanguages')->willReturn('nld');
		$ocr->method('getOcrDpi')->willReturn(300);
		$ocr->method('extractTextFromPdf')->willReturn(['text' => '', 'confidence' => 0]);
		$ocr->method('extractTextFromImage')->willReturn(['text' => self::TEXT, 'confidence' => 90]);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueBool')->willReturnCallback(fn (): bool => $this->enabled);
		$groups = $this->createMock(IGroupManager::class);
		$groups->method('isAdmin')->willReturn($admin);
		$logger = $this->createMock(LoggerInterface::class);

		return new SchemaFieldFillService(
			objectResolver: $resolver,
			profiles: new FieldFillProfileService(objectResolver: $resolver, container: $container, logger: $logger),
			valueCheck: new FieldValueCheck(),
			passage: new TextPassage(),
			container: $container,
			rootFolder: $root,
			userSession: $session,
			ocrService: $ocr,
			appConfig: $config,
			groupManager: $groups,
			logger: $logger
		);

	}//end service()

	/**
	 * The model double: records the prompt and answers.
	 *
	 * @param Task $task The task.
	 *
	 * @return Task
	 */
	public function modelRuns(Task $task): Task {
		$this->prompts[] = (string)$task->getInput()['input'];
		$task->setOutput(['output' => $this->answer]);

		return $task;

	}//end modelRuns()

	/**
	 * The provider double.
	 *
	 * @param string $type The task type.
	 *
	 * @return object
	 */
	public function providerFor(string $type): object {
		if ($this->provider === false) {
			throw new RuntimeException('no provider for ' . $type);
		}

		return new class {
			/**
			 * Name.
			 *
			 * @return string
			 */
			public function getName(): string {
				return 'Local LLM';
			}
		};

	}//end providerFor()

	/**
	 * Ask the double for suggestions on rec-1.
	 *
	 * @return array<string, mixed>
	 */
	private function suggest(): array {
		return $this->service()->suggest(fileId: 7, register: 'subsidies', schema: 'subsidieaanvraag', objectId: 'rec-1', userId: 'clerk');

	}//end suggest()

	/**
	 * Assert a call is refused with a status and message.
	 *
	 * @param callable $call    The call.
	 * @param int      $status  The status.
	 * @param string   $message The message.
	 *
	 * @return void
	 */
	private function assertRefused(callable $call, int $status, string $message): void {
		try {
			$call();
			$this->fail('Expected a refusal');
		} catch (FieldFillRefused $refused) {
			$this->assertSame($status, $refused->getCode());
			$this->assertSame($message, $refused->getMessage());
		}

	}//end assertRefused()

	/**
	 * A valid answer is kept per field with its passage; a value not in the text is flagged; a bsn field is never asked.
	 *
	 * @return void
	 */
	public function testAValidAnswerIsSuggestedWithItsPassage(): void {
		$this->answer = "```json\n" . '{"applicant": "Stichting Groen Dorp", "amount": 9999, "startDate": "2027-03-01", "bsn": "123456782", "status": "closed"}' . "\n```";

		$result = $this->suggest();

		$this->assertSame(['applicant', 'amount', 'startDate'], array_keys($result['suggestions']));
		$this->assertTrue($result['suggestions']['applicant']['foundInText']);
		$this->assertStringContainsString('Stichting Groen Dorp', (string)$result['suggestions']['applicant']['passage']);
		$this->assertSame(0.75, $result['suggestions']['applicant']['confidence']);
		$this->assertFalse($result['suggestions']['amount']['foundInText']);
		$this->assertNull($result['suggestions']['amount']['passage']);
		$this->assertSame(0.55, $result['suggestions']['amount']['confidence']);
		$this->assertSame(0, $result['droppedFields']);
		$this->assertSame('suggested', $result['status']);
		$this->assertSame('clerk', $result['requestedBy']);
		$this->assertSame('Local LLM', $result['provider']);
		$this->assertSame('sug-1', $result['uuid']);
		$this->assertSame(['applicant' => null, 'amount' => null, 'startDate' => '2026-01-01'], $result['currentValues']);

		$this->assertCount(1, $this->prompts);
		$this->assertStringContainsString('- applicant; type: string; title: Aanvrager', $this->prompts[0]);
		$this->assertStringContainsString('- startDate; type: string; format: date', $this->prompts[0]);
		$this->assertStringNotContainsString('bsn', $this->prompts[0]);
		$this->assertStringNotContainsString('status', $this->prompts[0]);

		$this->assertSame('filinq', $this->saved[0]['register']);
		$this->assertSame('fieldSuggestion', $this->saved[0]['schema']);
		$this->assertSame(7, $this->saved[0]['object']['fileId']);

	}//end testAValidAnswerIsSuggestedWithItsPassage()

	/**
	 * A malformed date is dropped and counted.
	 *
	 * @return void
	 */
	public function testAMalformedDateIsDropped(): void {
		$this->answer = '{"applicant": null, "amount": -3, "startDate": "next spring"}';

		$result = $this->suggest();

		$this->assertSame([], $result['suggestions']);
		$this->assertSame(2, $result['droppedFields']);

	}//end testAMalformedDateIsDropped()

	/**
	 * An answer that is not JSON suggests nothing.
	 *
	 * @return void
	 */
	public function testAnAnswerThatIsNotJsonSuggestsNothing(): void {
		$this->answer = 'I could not read it.';

		$result = $this->suggest();

		$this->assertSame([], $result['suggestions']);
		$this->assertSame(0, $result['droppedFields']);

	}//end testAnAnswerThatIsNotJsonSuggestsNothing()

	/**
	 * A schema without an active profile is refused before any model task runs.
	 *
	 * @return void
	 */
	public function testNoProfileIsAConflictAndNoTaskRuns(): void {
		$this->profiles[0]['active'] = false;

		$this->assertRefused(fn () => $this->suggest(), 409, 'No fields are declared for model filling on this schema.');
		$this->assertSame([], $this->prompts);
		$this->assertSame([], $this->saved);

	}//end testNoProfileIsAConflictAndNoTaskRuns()

	/**
	 * With the switch off, or without a provider, the answer is 503 and nothing is read.
	 *
	 * @return void
	 */
	public function testNoModelIsUnavailable(): void {
		$this->enabled = false;
		$this->assertRefused(fn () => $this->suggest(), 503, 'No text model is available on this server.');

		$this->enabled  = true;
		$this->provider = false;
		$this->assertRefused(fn () => $this->suggest(), 503, 'No text model is available on this server.');
		$this->assertSame([], $this->finds);
		$this->assertSame([], $this->prompts);

	}//end testNoModelIsUnavailable()

	/**
	 * A record or file the user cannot read is a 404.
	 *
	 * @return void
	 */
	public function testAnUnreadableRecordOrFileIsNotFound(): void {
		$this->assertRefused(
			fn () => $this->service()->suggest(fileId: 7, register: 'subsidies', schema: 'subsidieaanvraag', objectId: 'nope', userId: 'clerk'),
			404,
			'The record was not found or you may not read it.'
		);

		$this->fileReadable = false;
		$this->assertRefused(fn () => $this->suggest(), 404, 'The file was not found or you may not read it.');
		$this->assertSame([], $this->prompts);

	}//end testAnUnreadableRecordOrFileIsNotFound()

	/**
	 * A suggestion to decide, stored as the suggest step leaves it.
	 *
	 * @param string $requestedBy Who asked.
	 * @param string $status      Its status.
	 *
	 * @return void
	 */
	private function storeSuggestion(string $requestedBy = 'clerk', string $status = 'suggested'): void {
		$this->records['sug-1'] = [
			'@self' => ['id' => 'sug-1'],
			'register' => 'subsidies',
			'schema' => 'subsidieaanvraag',
			'objectId' => 'rec-1',
			'requestedBy' => $requestedBy,
			'status' => $status,
			'suggestions' => [
				'applicant' => ['value' => 'Stichting Groen Dorp'],
				'amount' => ['value' => 12500],
				'startDate' => ['value' => '2027-03-01'],
			],
		];

	}//end storeSuggestion()

	/**
	 * Accepted and changed values are written to the record as the clerk; a rejected one keeps the record's value.
	 *
	 * @return void
	 */
	public function testOnlyAcceptedAndChangedValuesAreWritten(): void {
		$this->storeSuggestion();

		$result = $this->service()->decide(
			id: 'sug-1',
			decisions: [
				'applicant' => 'accept',
				'amount' => ['decision' => 'change', 'value' => 12000],
				'startDate' => ['decision' => 'reject'],
			],
			userId: 'clerk'
		);

		$this->assertCount(2, $this->saved);
		$record = $this->saved[0];
		$this->assertSame(['subsidies', 'subsidieaanvraag', 'rec-1'], [$record['register'], $record['schema'], $record['uuid']]);
		$this->assertSame(
			['applicant' => 'Stichting Groen Dorp', 'amount' => 12000, 'startDate' => '2026-01-01', 'status' => 'open'],
			$record['object']
		);

		$this->assertSame('decided', $result['status']);
		$this->assertSame('clerk', $result['decidedBy']);
		$this->assertSame(['accept', 'change', 'reject'], array_column($result['decisions'], 'decision'));
		$this->assertSame(['Stichting Groen Dorp', 12000, null], array_column($result['decisions'], 'written'));
		$this->assertSame([12500], array_values(array_filter(array_column($result['decisions'], 'suggested'), 'is_int')));
		$this->assertSame(['filinq', 'fieldSuggestion', 'sug-1'], [$this->saved[1]['register'], $this->saved[1]['schema'], $this->saved[1]['uuid']]);
		$this->assertSame('decided', $this->saved[1]['object']['status']);

	}//end testOnlyAcceptedAndChangedValuesAreWritten()

	/**
	 * Rejecting everything writes nothing to the record, and still records the decisions.
	 *
	 * @return void
	 */
	public function testRejectingEverythingLeavesTheRecordAlone(): void {
		$this->storeSuggestion();

		$this->service()->decide(id: 'sug-1', decisions: ['applicant' => 'reject', 'amount' => 'reject', 'startDate' => 'reject'], userId: 'clerk');

		$this->assertCount(1, $this->saved);
		$this->assertSame('fieldSuggestion', $this->saved[0]['schema']);

	}//end testRejectingEverythingLeavesTheRecordAlone()

	/**
	 * A decision is refused for someone else's suggestion, a decided one, a missing decision and a changed value that does not fit.
	 *
	 * @return void
	 */
	public function testDecisionRefusals(): void {
		$this->assertRefused(fn () => $this->service()->decide(id: 'none', decisions: [], userId: 'clerk'), 404, 'The suggestion was not found.');

		$this->storeSuggestion(requestedBy: 'other');
		$this->assertRefused(
			fn () => $this->service()->decide(id: 'sug-1', decisions: [], userId: 'clerk'),
			403,
			'Only the person who asked for the suggestion decides it.'
		);

		$this->storeSuggestion(status: 'decided');
		$this->assertRefused(fn () => $this->service()->decide(id: 'sug-1', decisions: [], userId: 'clerk'), 409, 'This suggestion has already been decided.');

		$this->storeSuggestion();
		$this->assertRefused(
			fn () => $this->service()->decide(id: 'sug-1', decisions: ['applicant' => 'accept', 'amount' => 'maybe'], userId: 'clerk'),
			400,
			'The field "amount" needs a decision: accept, change or reject.'
		);
		$this->assertRefused(
			fn () => $this->service()->decide(
				id: 'sug-1',
				decisions: ['applicant' => 'accept', 'amount' => 'accept', 'startDate' => ['decision' => 'change', 'value' => 'soon']],
				userId: 'clerk'
			),
			400,
			'The changed value for "startDate" does not fit the field.'
		);
		$this->assertSame([], $this->saved);

	}//end testDecisionRefusals()

	/**
	 * An administrator may decide another person's suggestion; a refused record write is a 403 and the suggestion stays open.
	 *
	 * @return void
	 */
	public function testAnAdminDecidesButARefusedWriteIsForbidden(): void {
		$this->storeSuggestion(requestedBy: 'other');
		$this->refuseRecordWrite = true;

		$this->assertRefused(
			fn () => $this->service(admin: true)->decide(id: 'sug-1', decisions: ['applicant' => 'accept', 'amount' => 'reject', 'startDate' => 'reject'], userId: 'admin'),
			403,
			'You may not change this record.'
		);
		$this->assertCount(1, $this->saved);
		$this->assertSame('subsidieaanvraag', $this->saved[0]['schema']);

	}//end testAnAdminDecidesButARefusedWriteIsForbidden()

	/**
	 * The status names the provider; switching on without one is refused.
	 *
	 * @return void
	 */
	public function testStatusAndSwitch(): void {
		$this->enabled = false;
		$this->assertSame(['enabled' => false, 'provider' => 'Local LLM', 'available' => false], $this->service()->status());

		$this->provider = false;
		$this->assertRefused(fn () => $this->service()->setEnabled(enabled: true), 503, 'No text model is available on this server.');

	}//end testStatusAndSwitch()
}//end class

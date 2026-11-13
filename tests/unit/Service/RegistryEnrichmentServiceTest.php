<?php

/**
 * Registry enrichment: a KvK number becomes a suggestion from the
 * Handelsregister, a BSN reaches the BRP only past the switch, the rule's
 * purpose and the elfproef, a lookup that cannot run leaves a note, and no
 * BSN ever reaches a note or the log.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DateTime;
use DateTimeZone;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\RegistryEnrichmentService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Db\GdprEntity;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\RegistryEnrichmentService
 * @uses   \OCA\Filinq\Service\Intake\RegistryAnswer
 */
class RegistryEnrichmentServiceTest extends TestCase {

	/**
	 * A BSN that passes the elfproef.
	 *
	 * @var string
	 */
	private const VALID_BSN = '111222333';

	/**
	 * A nine-digit number that fails the elfproef.
	 *
	 * @var string
	 */
	private const INVALID_BSN = '123456789';

	/**
	 * The BSNs the BRP double was asked for.
	 *
	 * @var array<int, string>
	 */
	private array $brpAsked = [];

	/**
	 * The KvK numbers the KvK double was asked for.
	 *
	 * @var array<int, string>
	 */
	private array $kvkAsked = [];

	/**
	 * Every message and context the logger received, serialised.
	 *
	 * @var array<int, string>
	 */
	private array $logged = [];

	/**
	 * A detected entity.
	 *
	 * @param string $type  The entity type.
	 * @param string $value Its text.
	 *
	 * @return GdprEntity The double.
	 */
	private function entity(string $type, string $value): GdprEntity {
		$entity = $this->createMock(GdprEntity::class);
		$entity->method('getType')->willReturn($type);
		$entity->method('getValue')->willReturn($value);

		return $entity;

	}//end entity()

	/**
	 * A KvK provider double that answers the given result, or throws.
	 *
	 * @param array<string, mixed>|null $answer The answer, or null to throw.
	 *
	 * @return object The double.
	 */
	private function kvkProvider(?array $answer): object {
		$test = $this;

		return new class ($answer, $test) {
			/**
			 * @param array<string, mixed>|null       $answer The answer.
			 * @param RegistryEnrichmentServiceTest $test   The test that records the calls.
			 */
			public function __construct(private ?array $answer, private RegistryEnrichmentServiceTest $test) {
			}

			/**
			 * @param string $kvkNumber The number.
			 *
			 * @return array<string, mixed>
			 */
			public function lookupByKvkNumber(string $kvkNumber): array {
				$this->test->recordKvk(kvkNumber: $kvkNumber);
				if ($this->answer === null) {
					throw new RuntimeException('connection reset');
				}

				return $this->answer;
			}
		};

	}//end kvkProvider()

	/**
	 * A BRP provider double that answers the given result, or throws with the BSN in its message.
	 *
	 * @param array<string, mixed>|null $answer The answer, or null to throw.
	 *
	 * @return object The double.
	 */
	private function brpProvider(?array $answer): object {
		$test = $this;

		return new class ($answer, $test) {
			/**
			 * @param array<string, mixed>|null       $answer The answer.
			 * @param RegistryEnrichmentServiceTest $test   The test that records the calls.
			 */
			public function __construct(private ?array $answer, private RegistryEnrichmentServiceTest $test) {
			}

			/**
			 * @param string $bsn The BSN.
			 *
			 * @return array<string, mixed>
			 */
			public function lookupByBsn(string $bsn): array {
				$this->test->recordBsn(bsn: $bsn);
				if ($this->answer === null) {
					throw new RuntimeException('request for ' . $bsn . ' failed');
				}

				return $this->answer;
			}
		};

	}//end brpProvider()

	/**
	 * A BSN format double carrying the real elfproef.
	 *
	 * @return object The double.
	 */
	private function bsnFormat(): object {
		return new class () {
			/**
			 * @param mixed $data The value.
			 *
			 * @return bool Whether it passes the elfproef.
			 */
			public function validate(mixed $data): bool {
				$digits = (string)$data;
				if (preg_match('/^\d{9}$/', $digits) !== 1) {
					return false;
				}

				$sum = 0;
				for ($i = 0; $i < 8; $i++) {
					$sum += ((int)$digits[$i] * (9 - $i));
				}

				return (($sum - (int)$digits[8]) % 11) === 0;
			}
		};

	}//end bsnFormat()

	/**
	 * Record a KvK lookup.
	 *
	 * @param string $kvkNumber The number.
	 *
	 * @return void
	 */
	public function recordKvk(string $kvkNumber): void {
		$this->kvkAsked[] = $kvkNumber;

	}//end recordKvk()

	/**
	 * Record a BRP lookup.
	 *
	 * @param string $bsn The BSN.
	 *
	 * @return void
	 */
	public function recordBsn(string $bsn): void {
		$this->brpAsked[] = $bsn;

	}//end recordBsn()

	/**
	 * The service over the given entities, container classes, switch and rule.
	 *
	 * @param array<int, GdprEntity>   $entities  What entity detection found.
	 * @param array<string, object>    $classes   The OpenRegister classes the container has.
	 * @param bool                     $brpOn     Whether the admin switched BRP lookups on.
	 * @param array<string, mixed>|null $rule     The stamped rule, or null when it is gone.
	 * @param bool                     $installed Whether OpenRegister is installed.
	 *
	 * @return RegistryEnrichmentService The service under test.
	 */
	private function service(
		array $entities,
		array $classes = [],
		bool $brpOn = false,
		?array $rule = null,
		bool $installed = true,
	): RegistryEnrichmentService {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturn($entities);
		$stats = $this->createMock(FileEntityStatsService::class);
		$stats->method('tryGetEntityRelationMapper')->willReturn($mapper);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static function (string $id) use ($classes): object {
				if (isset($classes[$id]) === false) {
					throw new RuntimeException('no ' . $id);
				}

				return $classes[$id];
			}
		);

		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn($installed === true ? ['files', 'openregister'] : ['files']);

		$rules = $this->createMock(IntakeDefaultRuleService::class);
		$rules->method('find')->willReturnCallback(
			static fn (string $uuid): ?array => ($uuid === 'rule-1' ? $rule : null)
		);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($key === RegistryEnrichmentService::BRP_SETTING ? ($brpOn === true ? '1' : '0') : $default)
		);

		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getDateTime')->willReturnCallback(
			static fn (): DateTime => new DateTime('2026-09-19T10:06:00', new DateTimeZone('UTC'))
		);

		$logger = $this->createMock(LoggerInterface::class);
		foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical'] as $level) {
			$logger->method($level)->willReturnCallback(
				function (string|\Stringable $message, array $context = []): void {
					$this->logged[] = (string)$message . ' ' . json_encode($context);
				}
			);
		}

		return new RegistryEnrichmentService($container, $apps, $stats, $rules, $config, $clock, $logger);

	}//end service()

	/**
	 * A KvK row as the Handelsregister search answers it.
	 *
	 * @return array<string, mixed> The answer.
	 */
	private static function kvkAnswer(): array {
		return [
			'results' => [
				[
					'kvkNummer' => '12345678',
					'naam' => 'Voorbeeld B.V.',
					'adres' => [
						'binnenlandsAdres' => [
							'type' => 'bezoekadres',
							'straatnaam' => 'Voorbeeldstraat',
							'huisnummer' => 1,
							'postcode' => '1234AB',
							'plaats' => 'Voorbeeldstad',
						],
					],
				],
			],
			'total' => 1,
		];

	}//end kvkAnswer()

	/**
	 * A BRP person as HaalCentraal answers it, with more than the suggestion may keep.
	 *
	 * @return array<string, mixed> The answer.
	 */
	private static function brpAnswer(): array {
		return [
			'results' => [
				[
					'burgerservicenummer' => self::VALID_BSN,
					'geboorte' => ['datum' => ['datum' => '1980-01-01']],
					'naam' => ['volledigeNaam' => 'Jan de Vries'],
					'verblijfplaats' => [
						'verblijfadres' => [
							'officieleStraatnaam' => 'Dorpsstraat',
							'huisnummer' => 12,
							'huisletter' => 'a',
							'postcode' => '9999ZZ',
							'woonplaats' => 'Ergens',
						],
					],
				],
			],
			'total' => 1,
			'meta' => ['correlationId' => 'c-1'],
		];

	}//end brpAnswer()

	/**
	 * Assert no BSN appears in a note or in anything logged.
	 *
	 * @param array<string, mixed> $outcome The enrichment outcome.
	 *
	 * @return void
	 */
	private function assertNoBsnLeaked(array $outcome): void {
		foreach ([self::VALID_BSN, self::INVALID_BSN] as $bsn) {
			$this->assertStringNotContainsString($bsn, $outcome['registryLookupNote']);
			foreach ($this->logged as $line) {
				$this->assertStringNotContainsString($bsn, $line);
			}
		}

	}//end assertNoBsnLeaked()

	/**
	 * A KvK number becomes one suggestion with name, visiting address and number, not written.
	 *
	 * @return void
	 */
	public function testAKvkNumberBecomesASuggestion(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'KVK', value: '1234 5678'), $this->entity(type: 'KVK', value: '12.34.56.78')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: self::kvkAnswer())]
		);

		$outcome = $service->enrich(document: ['file' => 7]);

		$this->assertSame(['12345678'], $this->kvkAsked, 'the same number is asked once');
		$this->assertSame(
			[
				[
					'source' => 'kvk',
					'identifier' => '12345678',
					'fields' => [
						'name' => 'Voorbeeld B.V.',
						'address' => 'Voorbeeldstraat 1, 1234AB Voorbeeldstad',
						'kvkNumber' => '12345678',
					],
					'retrievedAt' => '2026-09-19T10:06:00+00:00',
					'written' => false,
				],
			],
			$outcome['registrySuggestion']
		);
		$this->assertSame('', $outcome['registryLookupNote']);

	}//end testAKvkNumberBecomesASuggestion()

	/**
	 * Without OpenRegister the lookup says it is not configured and suggests nothing.
	 *
	 * @return void
	 */
	public function testWithoutOpenRegisterTheKvkLookupIsNotConfigured(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'KVK', value: '12345678')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: self::kvkAnswer())],
			installed: false
		);

		$outcome = $service->enrich(document: ['file' => 7]);

		$this->assertSame([], $this->kvkAsked);
		$this->assertSame([], $outcome['registrySuggestion']);
		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_NOT_CONFIGURED, $outcome['registryLookupNote']);

	}//end testWithoutOpenRegisterTheKvkLookupIsNotConfigured()

	/**
	 * A degraded answer names the cause: a missing source is configuration, the rest is downtime.
	 *
	 * @return void
	 */
	public function testADegradedAnswerSaysWhy(): void {
		$missing = $this->service(
			entities: [$this->entity(type: 'KVK', value: '12345678')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: ['unavailable' => true, 'cause' => 'openconnector-source-missing', 'results' => [], 'total' => 0])]
		);
		$down = $this->service(
			entities: [$this->entity(type: 'KVK', value: '12345678')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: ['unavailable' => true, 'cause' => 'upstream-service-down', 'results' => [], 'total' => 0])]
		);
		$throws = $this->service(
			entities: [$this->entity(type: 'KVK', value: '12345678')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: null)]
		);
		$unknown = $this->service(
			entities: [$this->entity(type: 'KVK', value: '12345678')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: ['results' => [], 'total' => 0])]
		);

		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_NOT_CONFIGURED, $missing->enrich(document: ['file' => 7])['registryLookupNote']);
		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_UNAVAILABLE, $down->enrich(document: ['file' => 7])['registryLookupNote']);
		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_UNAVAILABLE, $throws->enrich(document: ['file' => 7])['registryLookupNote']);
		$this->assertSame(RegistryEnrichmentService::NOTE_KVK_UNKNOWN, $unknown->enrich(document: ['file' => 7])['registryLookupNote']);

	}//end testADegradedAnswerSaysWhy()

	/**
	 * A document without identifiers, or without a file, gets neither a suggestion nor a note.
	 *
	 * @return void
	 */
	public function testNoIdentifiersNoLookup(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'PERSON', value: 'Jan'), $this->entity(type: 'KVK', value: '1234567')],
			classes: [RegistryEnrichmentService::KVK_PROVIDER => $this->kvkProvider(answer: self::kvkAnswer())]
		);

		$this->assertSame(['registrySuggestion' => [], 'registryLookupNote' => ''], $service->enrich(document: ['file' => 7]));
		$this->assertSame(['registrySuggestion' => [], 'registryLookupNote' => ''], $service->enrich(document: []));
		$this->assertSame([], $this->kvkAsked);

	}//end testNoIdentifiersNoLookup()

	/**
	 * With the switch off, a BSN is never sent to the BRP and nothing is noted.
	 *
	 * @return void
	 */
	public function testTheSwitchOffMeansNoBrpQuery(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::VALID_BSN)],
			classes: [
				RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: self::brpAnswer()),
				RegistryEnrichmentService::BSN_FORMAT => $this->bsnFormat(),
			],
			brpOn: false,
			rule: ['uuid' => 'rule-1', 'brpPurpose' => 'Behandeling bezwaar']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([], $this->brpAsked);
		$this->assertSame(['registrySuggestion' => [], 'registryLookupNote' => ''], $outcome);
		$this->assertFalse($service->brpLookupEnabled());

	}//end testTheSwitchOffMeansNoBrpQuery()

	/**
	 * With the switch on but no purpose on the rule, no query is made and the note says what is missing.
	 *
	 * @return void
	 */
	public function testNoPurposeNoQuery(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::VALID_BSN)],
			classes: [
				RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: self::brpAnswer()),
				RegistryEnrichmentService::BSN_FORMAT => $this->bsnFormat(),
			],
			brpOn: true,
			rule: ['uuid' => 'rule-1', 'channel' => 'mail', 'brpPurpose' => '   ']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([], $this->brpAsked);
		$this->assertSame([], $outcome['registrySuggestion']);
		$this->assertSame(RegistryEnrichmentService::NOTE_BRP_NO_PURPOSE, $outcome['registryLookupNote']);
		$this->assertNoBsnLeaked(outcome: $outcome);

	}//end testNoPurposeNoQuery()

	/**
	 * A number failing the elfproef is not sent, and the note does not repeat it.
	 *
	 * @return void
	 */
	public function testAnInvalidBsnIsNotSent(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::INVALID_BSN)],
			classes: [
				RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: self::brpAnswer()),
				RegistryEnrichmentService::BSN_FORMAT => $this->bsnFormat(),
			],
			brpOn: true,
			rule: ['uuid' => 'rule-1', 'brpPurpose' => 'Behandeling bezwaar']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([], $this->brpAsked);
		$this->assertSame(RegistryEnrichmentService::NOTE_BSN_INVALID, $outcome['registryLookupNote']);
		$this->assertNoBsnLeaked(outcome: $outcome);

	}//end testAnInvalidBsnIsNotSent()

	/**
	 * Without the elfproef class the BRP is not asked: the check cannot be skipped.
	 *
	 * @return void
	 */
	public function testWithoutTheElfproefTheBrpIsNotAsked(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::VALID_BSN)],
			classes: [RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: self::brpAnswer())],
			brpOn: true,
			rule: ['uuid' => 'rule-1', 'brpPurpose' => 'Behandeling bezwaar']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([], $this->brpAsked);
		$this->assertSame(RegistryEnrichmentService::NOTE_BRP_NOT_CONFIGURED, $outcome['registryLookupNote']);

	}//end testWithoutTheElfproefTheBrpIsNotAsked()

	/**
	 * Under a purpose, a valid BSN gives a masked suggestion with name and address only.
	 *
	 * @return void
	 */
	public function testAValidBsnUnderAPurposeGivesNameAndAddressOnly(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::VALID_BSN)],
			classes: [
				RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: self::brpAnswer()),
				RegistryEnrichmentService::BSN_FORMAT => $this->bsnFormat(),
			],
			brpOn: true,
			rule: ['uuid' => 'rule-1', 'brpPurpose' => ' Behandeling bezwaar ']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([self::VALID_BSN], $this->brpAsked);
		$this->assertSame(
			[
				[
					'source' => 'brp',
					'identifier' => '******333',
					'fields' => ['name' => 'Jan de Vries', 'address' => 'Dorpsstraat 12a, 9999ZZ Ergens'],
					'retrievedAt' => '2026-09-19T10:06:00+00:00',
					'written' => false,
					'purpose' => 'Behandeling bezwaar',
				],
			],
			$outcome['registrySuggestion']
		);
		$this->assertStringNotContainsString(self::VALID_BSN, (string)json_encode($outcome));
		$this->assertNoBsnLeaked(outcome: $outcome);

	}//end testAValidBsnUnderAPurposeGivesNameAndAddressOnly()

	/**
	 * A BRP transport failure whose message carries the BSN leaves the BSN out of the log and the note.
	 *
	 * @return void
	 */
	public function testAFailingBrpLookupNeverLogsTheBsn(): void {
		$service = $this->service(
			entities: [$this->entity(type: 'BSN', value: self::VALID_BSN)],
			classes: [
				RegistryEnrichmentService::BRP_PROVIDER => $this->brpProvider(answer: null),
				RegistryEnrichmentService::BSN_FORMAT => $this->bsnFormat(),
			],
			brpOn: true,
			rule: ['uuid' => 'rule-1', 'brpPurpose' => 'Behandeling bezwaar']
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-1']);

		$this->assertSame([self::VALID_BSN], $this->brpAsked);
		$this->assertSame(RegistryEnrichmentService::NOTE_BRP_UNAVAILABLE, $outcome['registryLookupNote']);
		$this->assertNotSame([], $this->logged, 'the failure is logged');
		$this->assertNoBsnLeaked(outcome: $outcome);

	}//end testAFailingBrpLookupNeverLogsTheBsn()

	/**
	 * Notes of several lookups are joined once each.
	 *
	 * @return void
	 */
	public function testNotesAreJoinedOnce(): void {
		$service = $this->service(
			entities: [
				$this->entity(type: 'KVK', value: '12345678'),
				$this->entity(type: 'KVK', value: '87654321'),
				$this->entity(type: 'BSN', value: self::VALID_BSN),
			],
			brpOn: true,
			rule: null
		);

		$outcome = $service->enrich(document: ['file' => 7, 'defaultRule' => 'rule-gone']);

		$this->assertSame(
			RegistryEnrichmentService::NOTE_KVK_NOT_CONFIGURED . '. ' . RegistryEnrichmentService::NOTE_BRP_NO_PURPOSE,
			$outcome['registryLookupNote']
		);

	}//end testNotesAreJoinedOnce()

	/**
	 * A BSN is masked to its last three digits.
	 *
	 * @return void
	 */
	public function testMaskBsn(): void {
		$this->assertSame('******333', RegistryEnrichmentService::maskBsn(bsn: self::VALID_BSN));

	}//end testMaskBsn()
}//end class

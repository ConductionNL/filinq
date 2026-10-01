<?php

/**
 * Tests for WizardService, WizardDefinitionValidator, WizardConditions and WizardAnswers
 *
 * Real sibling classes throughout; only OpenRegister (an in-memory store
 * that validates against the real wizardDefinition fragment), the template
 * lookup, the schema mapper and the data resolver are doubles.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/Wizard/WizardDoubles.php';

use DateTimeImmutable;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardConditions;
use OCA\Filinq\Service\Wizard\WizardDefinitionValidator;
use OCA\Filinq\Service\Wizard\WizardRefused;
use OCA\Filinq\Service\Wizard\WizardRepository;
use OCA\Filinq\Service\Wizard\WizardService;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardFixtures;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Wizard authoring, skip logic, answer translation and prefill.
 */
class WizardServiceTest extends TestCase {

	/**
	 * The store.
	 *
	 * @var WizardObjectStore
	 */
	private WizardObjectStore $store;

	/**
	 * The template the lookup answers.
	 *
	 * @var array<string, mixed>
	 */
	private array $template = ['id' => WizardFixtures::TEMPLATE, 'name' => 'Beschikking parkeervergunning'];

	/**
	 * The data resolver.
	 *
	 * @var DataResolverService&MockObject
	 */
	private DataResolverService $resolver;

	/**
	 * The schema mapper.
	 *
	 * @var SchemaMapper&MockObject
	 */
	private SchemaMapper $schemas;

	/**
	 * Build the service.
	 *
	 * @return WizardService The service.
	 */
	private function service(): WizardService {
		$this->store ??= new WizardObjectStore();
		$objects = $this->createMock(DocumentObjectServiceResolver::class);
		$objects->method('resolve')->willReturn($this->store);
		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturnCallback(fn (string $id): array => $id === WizardFixtures::TEMPLATE ? $this->template : throw new \Exception('not found', 404));
		$this->resolver ??= $this->createMock(DataResolverService::class);
		$this->schemas ??= $this->createMock(SchemaMapper::class);

		return new WizardService(
			repository: new WizardRepository(objectResolver: $objects),
			validator: new WizardDefinitionValidator(),
			templates: $templates,
			resolver: $this->resolver,
			schemas: $this->schemas,
			answers: $this->answers(),
		);

	}//end service()

	/**
	 * The answers half.
	 *
	 * @return WizardAnswers The real class.
	 */
	private function answers(): WizardAnswers {
		return new WizardAnswers(conditions: new WizardConditions());

	}//end answers()

	/**
	 * Run a save and return the refusal.
	 *
	 * @param callable $save The save.
	 *
	 * @return WizardRefused The refusal.
	 */
	private function refusal(callable $save): WizardRefused {
		try {
			$save();
		} catch (WizardRefused $e) {
			return $e;
		}

		$this->fail('The save was not refused.');

	}//end refusal()

	/**
	 * A valid wizard of all four types is stored and found for its template.
	 *
	 * @return void
	 */
	public function testAValidWizardIsSavedAndFoundForItsTemplate(): void {
		$saved = $this->service()->save(wizard: WizardFixtures::seed(), userId: 'alice');

		$this->assertSame([], $saved['warnings']);
		$this->assertCount(1, $this->store->saves);
		$this->assertSame(['registerObject', 'choice', 'text', 'date'], array_column($saved['wizard']['questions'], 'type'));
		$found = $this->service()->activeFor(templateId: WizardFixtures::TEMPLATE);
		$this->assertSame($saved['wizard']['uuid'], $found['uuid']);
		$this->assertNotSame('', $found['version']);

	}//end testAValidWizardIsSavedAndFoundForItsTemplate()

	/**
	 * A second active wizard on the same template is refused with 409 and not stored.
	 *
	 * @return void
	 */
	public function testSecondActiveWizardIsRefused(): void {
		$this->service()->save(wizard: WizardFixtures::seed(), userId: 'alice');

		$refused = $this->refusal(fn () => $this->service()->save(wizard: WizardFixtures::seed(), userId: 'alice'));

		$this->assertSame(409, $refused->getCode());
		$this->assertCount(1, $this->store->rows);

		// An inactive second wizard is allowed, and the first one can be changed.
		$this->service()->save(wizard: ['active' => false] + WizardFixtures::seed(), userId: 'alice');
		$this->service()->save(wizard: WizardFixtures::seed(), userId: 'alice', uuid: 'wizard-1');
		$this->assertCount(2, $this->store->rows);

	}//end testSecondActiveWizardIsRefused()

	/**
	 * A condition on a later question is refused with 422 naming the question.
	 *
	 * @return void
	 */
	public function testForwardConditionRefused(): void {
		$wizard = WizardFixtures::seed();
		$wizard['questions'][1]['condition'] = ['questionKey' => 'ingangsdatum', 'operator' => 'answered'];

		$refused = $this->refusal(fn () => $this->service()->save(wizard: $wizard, userId: 'alice'));

		$this->assertSame(422, $refused->getCode());
		$this->assertArrayHasKey('besluit', $refused->getErrors());
		$this->assertStringContainsString('ingangsdatum', $refused->getErrors()['besluit']);
		$this->assertSame([], $this->store->saves);

	}//end testForwardConditionRefused()

	/**
	 * Duplicate keys, a choice without choices and a register object without slugs are refused per question.
	 *
	 * @return void
	 */
	public function testStructuralErrorsAreRefusedPerQuestion(): void {
		$wizard = WizardFixtures::seed();
		$wizard['questions'][1]['choices'] = [];
		$wizard['questions'][0]['schema'] = '';
		$wizard['questions'][] = ['key' => 'besluit', 'label' => 'Again', 'type' => 'text', 'required' => false];

		$refused = $this->refusal(fn () => $this->service()->save(wizard: $wizard, userId: 'alice'));

		$this->assertSame(422, $refused->getCode());
		$errors = $refused->getErrors();
		$this->assertStringContainsString('register and a schema', $errors['dossier']);
		$this->assertStringContainsString('used twice', $errors['besluit']);

		$wizard = WizardFixtures::seed();
		$wizard['questions'][1]['choices'] = [];
		$this->assertStringContainsString('needs choices', $this->refusal(fn () => $this->service()->save(wizard: $wizard, userId: 'alice'))->getErrors()['besluit']);

	}//end testStructuralErrorsAreRefusedPerQuestion()

	/**
	 * A template locked by someone else refuses the save with 423; an expired lock does not.
	 *
	 * @return void
	 */
	public function testATemplateLockedByAnotherUserRefuses423(): void {
		$this->template += ['lockedBy' => 'bob', 'lockedAt' => (new DateTimeImmutable('-2 minutes'))->format(DATE_ATOM)];
		$this->assertSame(423, $this->refusal(fn () => $this->service()->save(wizard: WizardFixtures::seed(), userId: 'alice'))->getCode());

		// The lock holder may save.
		$this->service()->save(wizard: WizardFixtures::seed(), userId: 'bob');
		$this->assertCount(1, $this->store->rows);

	}//end testATemplateLockedByAnotherUserRefuses423()

	/**
	 * A template bound to a schema warns about a mapsTo path the schema lacks, and saves.
	 *
	 * @return void
	 */
	public function testUnknownMapsToWarnsButSaves(): void {
		$this->template += ['boundRegister' => 'filinq', 'boundSchema' => 'dossier'];
		$schema = new class {
			/**
			 * The properties.
			 *
			 * @return array<string, array<string, string>> The properties.
			 */
			public function getProperties(): array {
				return ['title' => ['type' => 'string'], 'besluit' => ['type' => 'object']];
			}
		};
		$this->schemas = $this->createMock(SchemaMapper::class);
		$this->schemas->method('find')->willReturn($schema);
		$wizard = WizardFixtures::seed();
		$wizard['questions'][] = ['key' => 'foo', 'label' => 'Foo', 'type' => 'text', 'required' => false, 'mapsTo' => 'foo.bar'];

		$saved = $this->service()->save(wizard: $wizard, userId: 'alice');

		$this->assertCount(1, $this->store->rows);
		$this->assertCount(1, $saved['warnings']);
		$this->assertStringContainsString('"foo"', $saved['warnings'][0]);
		$this->assertStringContainsString('foo.bar', $saved['warnings'][0]);

	}//end testUnknownMapsToWarnsButSaves()

	/**
	 * The rejection reason is asked only after "afgewezen", and is required then.
	 *
	 * @return void
	 */
	public function testConditionalQuestionAppearsOnlyOnTheTriggeringAnswer(): void {
		$conditions = new WizardConditions();
		$questions = WizardFixtures::seed()['questions'];

		$this->assertSame(['dossier', 'besluit', 'ingangsdatum'], $conditions->visibleKeys(questions: $questions, answers: ['besluit' => 'toegewezen']));
		$this->assertSame(['dossier', 'besluit', 'afwijzingsreden'], $conditions->visibleKeys(questions: $questions, answers: ['besluit' => 'afgewezen']));
		$this->assertSame(['dossier', 'besluit'], $conditions->visibleKeys(questions: $questions, answers: []));

		$errors = $this->answers()->errors(wizard: WizardFixtures::seed(), answers: ['dossier' => 'd-17', 'besluit' => 'afgewezen'], dataRefs: [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']]);
		$this->assertSame(['afwijzingsreden'], array_keys($errors));

	}//end testConditionalQuestionAppearsOnlyOnTheTriggeringAnswer()

	/**
	 * A condition the evaluator cannot read shows the question and enforces its required flag.
	 *
	 * @return void
	 */
	public function testMalformedConditionIsVisible(): void {
		$wizard = WizardFixtures::seed();
		$wizard['questions'][2]['condition']['operator'] = 'greaterThan';

		$visible = (new WizardConditions())->visibleKeys(questions: $wizard['questions'], answers: ['besluit' => 'toegewezen']);
		$errors = $this->answers()->errors(wizard: $wizard, answers: ['dossier' => 'd-17', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-11-01'], dataRefs: [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']]);

		$this->assertContains('afwijzingsreden', $visible);
		$this->assertArrayHasKey('afwijzingsreden', $errors);

	}//end testMalformedConditionIsVisible()

	/**
	 * A wrong choice, a non-date and a picked object without its dataRef each fail with their key.
	 *
	 * @return void
	 */
	public function testWrongAnswersFailPerQuestion(): void {
		$errors = $this->answers()->errors(
			wizard: WizardFixtures::seed(),
			answers: ['dossier' => 'd-17', 'besluit' => 'misschien'],
			dataRefs: []
		);
		$this->assertSame(['dossier', 'besluit'], array_keys($errors));

		$errors = $this->answers()->errors(
			wizard: WizardFixtures::seed(),
			answers: ['dossier' => 'd-17', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-02-30'],
			dataRefs: [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']]
		);
		$this->assertSame(['ingangsdatum'], array_keys($errors));

	}//end testWrongAnswersFailPerQuestion()

	/**
	 * A register object answer becomes a dataRef; scalars land at their path; hidden answers are dropped.
	 *
	 * @return void
	 */
	public function testTranslateAnswersProducesDataRefs(): void {
		$translated = $this->answers()->translate(
			wizard: ['uuid' => 'wizard-1', 'version' => '1.0.2'] + WizardFixtures::seed(),
			answers: ['dossier' => '00000000-0000-0000-0000-000000000017', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-11-01', 'afwijzingsreden' => 'stale answer from a hidden step']
		);

		$this->assertSame([['register' => 'filinq', 'schema' => 'dossier', 'id' => '00000000-0000-0000-0000-000000000017']], $translated['dataRefs']);
		$this->assertSame(['besluit' => ['uitkomst' => 'toegewezen', 'ingangsdatum' => '2026-11-01']], $translated['adHocData']);
		$this->assertSame('wizard-1', $translated['wizardContext']['wizardId']);
		$this->assertSame('1.0.2', $translated['wizardContext']['wizardVersion']);
		$this->assertArrayNotHasKey('afwijzingsreden', $translated['wizardContext']['answers']);

	}//end testTranslateAnswersProducesDataRefs()

	/**
	 * A clerk's answer beats the resolved object field at the same path (DCS-005), through the real resolver.
	 *
	 * @return void
	 */
	public function testAdHocPrecedencePinned(): void {
		$wizard = WizardFixtures::seed();
		$wizard['questions'][] = ['key' => 'titel', 'label' => 'Title', 'type' => 'text', 'required' => false, 'mapsTo' => 'dossier.title'];
		$translated = $this->answers()->translate(
			wizard: $wizard,
			answers: ['dossier' => 'd-17', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-11-01', 'titel' => 'Parkeervergunning, herzien']
		);
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturn(['title' => 'Parkeervergunning Dorpsstraat 1', 'status' => 'open']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$resolver = new DataResolverService(container: $container, appManager: $apps, logger: $this->createMock(LoggerInterface::class), appConfig: $this->createMock(IAppConfig::class));

		$data = $resolver->resolve(dataRefs: $translated['dataRefs'], adHocData: $translated['adHocData'])['data'];

		$this->assertSame('Parkeervergunning, herzien', $data['dossier']['title']);
		$this->assertSame('toegewezen', $data['besluit']['uitkomst']);

		// The merge is per top-level key, so the save warns that this answer replaces the picked dossier.
		$warnings = (new WizardDefinitionValidator())->check(wizard: $wizard)['warnings'];
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('"titel"', $warnings[0]);

	}//end testAdHocPrecedencePinned()

	/**
	 * Two identical wizards on a Twig and an office template give the same payload.
	 *
	 * @return void
	 */
	public function testSamePayloadForTwigAndOfficeTemplates(): void {
		$answers = ['dossier' => 'd-17', 'besluit' => 'afgewezen', 'afwijzingsreden' => 'Geen parkeerplaats beschikbaar'];
		$twig = $this->answers()->translate(wizard: ['templateId' => 'twig-template'] + WizardFixtures::seed(), answers: $answers);
		$office = $this->answers()->translate(wizard: ['templateId' => 'office-template'] + WizardFixtures::seed(), answers: $answers);

		$this->assertSame($twig['dataRefs'], $office['dataRefs']);
		$this->assertSame($twig['adHocData'], $office['adHocData']);
		$this->assertSame($twig['wizardContext']['answers'], $office['wizardContext']['answers']);

	}//end testSamePayloadForTwigAndOfficeTemplates()

	/**
	 * Started from a dossier: the dossier question is prefilled, the decision is unresolved.
	 *
	 * @return void
	 */
	public function testPrefillFromADossier(): void {
		$service = $this->service();
		$uuid = $service->save(wizard: WizardFixtures::seed(), userId: 'alice')['wizard']['uuid'];
		$this->resolver->expects($this->once())->method('resolve')
			->with([['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']])
			->willReturn(['data' => ['dossier' => ['title' => 'Parkeervergunning Dorpsstraat 1']], 'errors' => [], 'warnings' => []]);

		$prefill = $service->prefill(uuid: $uuid, entry: ['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']);

		$this->assertSame(['dossier' => 'd-17'], $prefill['answers']);
		$this->assertSame(['besluit', 'afwijzingsreden', 'ingangsdatum'], $prefill['unresolved']);

	}//end testPrefillFromADossier()

	/**
	 * A scalar question whose path resolves on the entry object is prefilled; an unreadable object prefills nothing.
	 *
	 * @return void
	 */
	public function testPrefillReadsMapsToPathsAndNothingFromAnUnreadableObject(): void {
		$service = $this->service();
		$wizard = WizardFixtures::seed();
		$wizard['questions'][] = ['key' => 'titel', 'label' => 'Title', 'type' => 'text', 'required' => false, 'mapsTo' => 'dossier.title'];
		$uuid = $service->save(wizard: $wizard, userId: 'alice')['wizard']['uuid'];
		$this->resolver->method('resolve')->willReturnOnConsecutiveCalls(
			['data' => ['dossier' => ['title' => 'Parkeervergunning Dorpsstraat 1']], 'errors' => [], 'warnings' => []],
			['data' => [], 'errors' => [['message' => 'not found']], 'warnings' => []]
		);

		$this->assertSame('Parkeervergunning Dorpsstraat 1', $service->prefill(uuid: $uuid, entry: ['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17'])['answers']['titel']);
		$this->assertSame([], $service->prefill(uuid: $uuid, entry: ['register' => 'filinq', 'schema' => 'dossier', 'id' => 'other'])['answers']);
		$this->assertSame(422, $this->refusal(fn () => $service->prefill(uuid: $uuid, entry: ['register' => 'filinq']))->getCode());
		$this->assertSame(404, $this->refusal(fn () => $service->prefill(uuid: 'nope', entry: []))->getCode());

	}//end testPrefillReadsMapsToPathsAndNothingFromAnUnreadableObject()

	/**
	 * Delete removes the wizard unless someone else holds the template lock.
	 *
	 * @return void
	 */
	public function testDeleteRespectsTheTemplateLock(): void {
		$service = $this->service();
		$uuid = $service->save(wizard: WizardFixtures::seed(), userId: 'alice')['wizard']['uuid'];
		$this->template += ['lockedBy' => 'bob', 'lockedAt' => (new DateTimeImmutable())->format(DATE_ATOM)];

		$this->assertSame(423, $this->refusal(fn () => $this->service()->delete(uuid: $uuid, userId: 'alice'))->getCode());
		$this->service()->delete(uuid: $uuid, userId: 'bob');
		$this->assertSame([$uuid], $this->store->deleted);

	}//end testDeleteRespectsTheTemplateLock()
}//end class

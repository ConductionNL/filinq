<?php

/**
 * Unit tests for PartySuggestionService
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
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\PartySuggestionService;
use OCA\OpenRegister\Db\GdprEntity;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Asserts that the NAW block is OFFERED and never filed, that the confidence
 * says what it means, and that accept, edit and reject all reach the corpus.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PartySuggestionServiceTest extends TestCase {

	/**
	 * Every object the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * The schemas those writes were addressed to.
	 *
	 * @var array<int, string>
	 */
	private array $writtenTo = [];

	/**
	 * One detected entity.
	 *
	 * @param string $type The entity type.
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
	 * Build the service over a file carrying the given entities.
	 *
	 * @param array<int, GdprEntity>|null $entities The detected entities, or null for no mapper at all.
	 *
	 * @return PartySuggestionService The service under test.
	 */
	private function service(?array $entities): PartySuggestionService {
		$stats = $this->createMock(FileEntityStatsService::class);
		if ($entities === null) {
			$stats->method('tryGetEntityRelationMapper')->willReturn(null);
		} else {
			$mapper = $this->createMock(EntityRelationMapper::class);
			$mapper->method('findEntitiesForFile')->willReturn($entities);
			$stats->method('tryGetEntityRelationMapper')->willReturn($mapper);
		}

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->written[] = ($arguments[0] ?? []);
				$this->writtenTo[] = (string)($arguments[2] ?? '');

				return ($arguments[0] ?? []);
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('anna');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new PartySuggestionService(
			$stats,
			$resolver,
			$session,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The name and address block is offered, with its confidence and its span.
	 *
	 * Nothing is written: the corpus is untouched until somebody decides.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testTheNawBlockIsOfferedAndNotFiled(): void {
		$service = $this->service(
			entities: [
				$this->entity('PERSON', 'J. Jansen'),
				$this->entity('LOCATION', 'Dorpsstraat 1, Voorbeeld'),
			]
		);

		$suggestions = $service->suggestFor(fileId: 4711);

		$this->assertCount(1, $suggestions);
		$this->assertSame('J. Jansen', $suggestions[0]['party']['name']);
		$this->assertSame('Dorpsstraat 1, Voorbeeld', $suggestions[0]['party']['address']);
		$this->assertSame(0.5, $suggestions[0]['confidence']);
		$this->assertSame('J. Jansen', $suggestions[0]['sourceSpan']['name']);
		$this->assertFalse($suggestions[0]['written']);
		$this->assertSame([], $this->written, 'A suggestion must not write anything.');

	}//end testTheNawBlockIsOfferedAndNotFiled()

	/**
	 * A complete block reads as full confidence, and the meaning is stated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAFullBlockReadsAsFullConfidenceAndSaysWhatThatMeans(): void {
		$service = $this->service(
			entities: [
				$this->entity('PERSON', 'J. Jansen'),
				$this->entity('LOCATION', 'Dorpsstraat 1'),
				$this->entity('EMAIL', 'j.jansen@voorbeeld.nl'),
				$this->entity('PHONE_NUMBER', '0612345678'),
			]
		);

		$suggestions = $service->suggestFor(fileId: 4711);

		$this->assertSame(1.0, $suggestions[0]['confidence']);
		$this->assertStringContainsString('share of', $suggestions[0]['confidenceMeaning']);

	}//end testAFullBlockReadsAsFullConfidenceAndSaysWhatThatMeans()

	/**
	 * A document with nothing to read offers nothing, rather than a blank party.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADocumentWithNothingToReadOffersNothing(): void {
		$service = $this->service(entities: [$this->entity('IBAN', 'NL00BANK0123456789')]);

		$this->assertSame([], $service->suggestFor(fileId: 4711));

	}//end testADocumentWithNothingToReadOffersNothing()

	/**
	 * With no entity detection installed there is no suggestion and no error.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testWithoutEntityDetectionThereIsNoSuggestion(): void {
		$service = $this->service(entities: null);

		$this->assertSame([], $service->suggestFor(fileId: 4711));

	}//end testWithoutEntityDetectionThereIsNoSuggestion()

	/**
	 * A rejection reaches the corpus, exactly like an acceptance.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testARejectionTeachesTheCorpus(): void {
		$service = $this->service(entities: []);

		$correction = $service->recordDecision(
			sender: 'facturen@leverancier.nl',
			decision: 'rejected',
			suggested: ['name' => 'J. Jansen'],
			intakeDocument: 'intake-1'
		);

		$this->assertSame('rejected', $correction['decision']);
		$this->assertSame('anna', $correction['decidedBy']);
		$this->assertCount(1, $this->written);
		$this->assertSame(PartySuggestionService::CORRECTION_SCHEMA, $this->writtenTo[0]);

	}//end testARejectionTeachesTheCorpus()

	/**
	 * An edited acceptance records both what was proposed and what was filed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAnEditRecordsBothTheProposalAndWhatWasFiled(): void {
		$service = $this->service(entities: []);

		$correction = $service->recordDecision(
			sender: 'jan@voorbeeld.nl',
			decision: 'edited',
			suggested: ['name' => 'J. Jansen'],
			accepted: ['name' => 'Jan Jansen']
		);

		$this->assertSame(['name' => 'J. Jansen'], $correction['suggested']);
		$this->assertSame(['name' => 'Jan Jansen'], $correction['accepted']);

	}//end testAnEditRecordsBothTheProposalAndWhatWasFiled()
}//end class

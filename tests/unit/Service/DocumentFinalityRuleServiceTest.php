<?php

/**
 * Unit tests for DocumentFinalityRuleService
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
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentFinalityRuleService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a consuming app's declaration freezes the right documents on the
 * right state change, and that a record type with no declaration freezes none.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentFinalityRuleServiceTest extends TestCase {

	/**
	 * The finalise calls the rule made, as `[fileId, reason]` pairs.
	 *
	 * @var array<int, array{int, string}>
	 */
	private array $finalised = [];

	/**
	 * A declaration by dossiq for one case type.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 *
	 * @return array<string, mixed> The declaration.
	 */
	private function declaration(array $overrides = []): array {
		return array_merge([
			'uuid' => 'rule-1',
			'declaringApp' => 'dossiq',
			'typeReference' => 'bezwaarschrift',
			'finalStates' => ['besluit genomen'],
			'documentRole' => 'besluit',
			'reasonTemplate' => '',
		], $overrides);

	}//end declaration()

	/**
	 * Build the service over a fake register holding `$rows`.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored declarations.
	 *
	 * @return DocumentFinalityRuleService The service.
	 */
	private function service(array $rows): DocumentFinalityRuleService {
		$this->finalised = [];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn($rows);
		$objectService->method('saveObject')->willReturnCallback(
			static fn (...$arguments): array => (($arguments[0] ?? []) + ['uuid' => 'rule-1'])
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$finalDocuments = $this->createMock(FinalDocumentService::class);
		$finalDocuments->method('finalise')->willReturnCallback(
			function (...$arguments): array {
				$fileId = (int)($arguments[0] ?? 0);
				$reason = (string)($arguments[1] ?? '');
				$this->finalised[] = [$fileId, $reason];

				return ['fileId' => $fileId, 'status' => 'final', 'finalReason' => $reason];
			}
		);

		return new DocumentFinalityRuleService(
			$resolver,
			$finalDocuments,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * The declared status freezes the besluit, naming the transition as the reason.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheDeclaredStatusFreezesTheBesluit(): void {
		$finalised = $this->service(rows: [$this->declaration()])->applyStateChange(
			declaringApp: 'dossiq',
			typeReference: 'bezwaarschrift',
			state: 'besluit genomen',
			fileIds: [4711],
			documentRole: 'besluit'
		);

		$this->assertCount(1, $finalised);
		$this->assertSame([[4711, 'bezwaarschrift reached the status "besluit genomen".']], $this->finalised);

	}//end testTheDeclaredStatusFreezesTheBesluit()

	/**
	 * A record type with no declaration freezes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testNoDeclarationMeansNoAutomaticFreeze(): void {
		$finalised = $this->service(rows: [])->applyStateChange(
			declaringApp: 'dossiq',
			typeReference: 'melding',
			state: 'afgehandeld',
			fileIds: [4711]
		);

		$this->assertSame([], $finalised);
		$this->assertSame([], $this->finalised);

	}//end testNoDeclarationMeansNoAutomaticFreeze()

	/**
	 * A state the declaration does not name freezes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUndeclaredStateFreezesNothing(): void {
		$finalised = $this->service(rows: [$this->declaration()])->applyStateChange(
			declaringApp: 'dossiq',
			typeReference: 'bezwaarschrift',
			state: 'in behandeling',
			fileIds: [4711],
			documentRole: 'besluit'
		);

		$this->assertSame([], $finalised);
		$this->assertSame([], $this->finalised);

	}//end testAnUndeclaredStateFreezesNothing()

	/**
	 * A declared reason template is used instead of the generated sentence.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testADeclaredReasonTemplateIsUsed(): void {
		$this->service(rows: [$this->declaration(['reasonTemplate' => 'Het besluit is genomen'])])
			->applyStateChange(
				declaringApp: 'dossiq',
				typeReference: 'bezwaarschrift',
				state: 'besluit genomen',
				fileIds: [4711],
				documentRole: 'besluit'
			);

		$this->assertSame([[4711, 'Het besluit is genomen']], $this->finalised);

	}//end testADeclaredReasonTemplateIsUsed()

	/**
	 * A declaration names the app, the type and at least one state.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testADeclarationNeedsAnAppATypeAndAState(): void {
		$service = $this->service(rows: []);

		$this->expectException(RuntimeException::class);

		$service->declareRule(declaringApp: 'dossiq', typeReference: 'bezwaarschrift', finalStates: []);

	}//end testADeclarationNeedsAnAppATypeAndAState()

	/**
	 * A stored declaration keeps what the consuming app declared.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAStoredDeclarationKeepsWhatTheConsumingAppDeclared(): void {
		$stored = $this->service(rows: [])->declareRule(
			declaringApp: 'dossiq',
			typeReference: 'bezwaarschrift',
			finalStates: ['besluit genomen', ' '],
			documentRole: 'besluit',
			declaredBy: 'anna'
		);

		$this->assertSame('dossiq', $stored['declaringApp']);
		$this->assertSame('bezwaarschrift', $stored['typeReference']);
		$this->assertSame(['besluit genomen'], $stored['finalStates']);
		$this->assertSame('besluit', $stored['documentRole']);
		$this->assertSame('anna', $stored['declaredBy']);

	}//end testAStoredDeclarationKeepsWhatTheConsumingAppDeclared()

	/**
	 * Declaring a rule for a type that already has one updates that record in place.
	 *
	 * @return void
	 */
	public function testARedeclarationUpdatesTheStoredRecord(): void {
		$saved = [];
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn([$this->declaration()]);
		$objectService->method('saveObject')->willReturnCallback(
			static function (...$arguments) use (&$saved): array {
				$saved[] = $arguments;
				return $arguments[0];
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);
		$service = new DocumentFinalityRuleService($resolver, $this->createMock(FinalDocumentService::class), $this->createMock(LoggerInterface::class));

		$service->declareRule(declaringApp: 'dossiq', typeReference: 'bezwaarschrift', finalStates: ['afgerond'], documentRole: 'besluit', reasonTemplate: 'Klaar');

		$this->assertCount(1, $saved);
		$this->assertSame('rule-1', $saved[0][3]);
		$this->assertSame('documentFinalityRule', $saved[0][2]);
		$this->assertSame(['afgerond'], $saved[0][0]['finalStates']);
		$this->assertSame('Klaar', $saved[0][0]['reasonTemplate']);
		$this->assertSame('', $saved[0][0]['declaredBy']);

	}//end testARedeclarationUpdatesTheStoredRecord()

	/**
	 * A state change skips invalid ids and documents already final, and keeps going past a failure.
	 *
	 * @return void
	 */
	public function testAStateChangeSkipsInvalidAndAlreadyFinalDocuments(): void {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjectsBySlug')->willReturn([$this->declaration()]);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);
		$finalDocuments = $this->createMock(FinalDocumentService::class);
		$finalDocuments->method('finalise')->willReturnCallback(
			static function (int $fileId, string $reason): array {
				if ($fileId === 5) {
					throw new \OCA\Filinq\Exception\DocumentFinalException(message: 'already final');
				}

				if ($fileId === 6) {
					throw new RuntimeException('storage gone');
				}

				return ['fileId' => $fileId, 'reason' => $reason];
			}
		);
		$service = new DocumentFinalityRuleService($resolver, $finalDocuments, $this->createMock(LoggerInterface::class));

		$finalised = $service->applyStateChange(
			declaringApp: 'dossiq',
			typeReference: 'bezwaarschrift',
			state: 'besluit genomen',
			fileIds: [0, -1, 5, 6, 7],
			documentRole: 'besluit'
		);

		$this->assertSame([['fileId' => 7, 'reason' => 'bezwaarschrift reached the status "besluit genomen".']], $finalised);

	}//end testAStateChangeSkipsInvalidAndAlreadyFinalDocuments()
}//end class

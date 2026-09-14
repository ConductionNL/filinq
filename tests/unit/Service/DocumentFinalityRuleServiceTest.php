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
		$objectService->method('searchObjects')->willReturn($rows);
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
}//end class

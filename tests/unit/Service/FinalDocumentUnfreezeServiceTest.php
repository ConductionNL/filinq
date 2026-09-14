<?php

/**
 * Unit tests for FinalDocumentUnfreezeService
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

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\FinalDocumentService;
use OCA\Filinq\Service\FinalDocumentUnfreezeService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that unfreezing needs the named right and a reason, that both are
 * recorded, and that the mark it leaves cannot be cleared.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentUnfreezeServiceTest extends TestCase {

	/**
	 * Objects the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * One stored final record.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function finalRecord(): array {
		return [
			'uuid' => 'version-1',
			'fileId' => 4711,
			'versionLabel' => '',
			'documentName' => 'Besluit.docx',
			'status' => 'final',
			'finalisedBy' => 'anna',
			'finalisedByName' => 'Anna de Boer',
			'finalisedAt' => '2026-09-03T14:05:00+00:00',
			'finalReason' => 'Het besluit is genomen',
			'unfrozen' => false,
		];

	}//end finalRecord()

	/**
	 * Build the service.
	 *
	 * @param bool $holdsRight Whether the acting user holds the named right.
	 * @param array<int, array<string, mixed>> $rows The stored records.
	 * @param bool $authenticated Whether anybody is signed in.
	 *
	 * @return FinalDocumentUnfreezeService The service.
	 */
	private function service(bool $holdsRight, array $rows, bool $authenticated = true): FinalDocumentUnfreezeService {
		$this->written = [];

		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturn($rows);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$object = ($arguments[0] ?? []);
				$this->written[] = $object;

				return ($object + ['uuid' => 'version-1']);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$repository = new FinalDocumentRepository($resolver, $this->createMock(LoggerInterface::class));

		$session = $this->createMock(IUserSession::class);
		if ($authenticated === true) {
			$user = $this->createMock(IUser::class);
			$user->method('getUID')->willReturn('boris');
			$user->method('getDisplayName')->willReturn('Boris Jansen');
			$session->method('getUser')->willReturn($user);
		} else {
			$session->method('getUser')->willReturn(null);
		}

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('isAdmin')->willReturn(false);
		$groupManager->method('isInGroup')->willReturn($holdsRight);

		return new FinalDocumentUnfreezeService(
			$repository,
			$this->createMock(FinalDocumentService::class),
			$session,
			$groupManager,
			$this->createMock(LoggerInterface::class)
		);

	}//end service()

	/**
	 * Without the named right, unfreezing is refused and the right is named.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testUnfreezingNeedsTheNamedRight(): void {
		$service = $this->service(holdsRight: false, rows: [$this->finalRecord()]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/docudesk-final-document-admins/');

		$service->unfreeze(fileId: 4711, reason: 'The besluit named the wrong street');

	}//end testUnfreezingNeedsTheNamedRight()

	/**
	 * An unauthenticated caller cannot unfreeze at all.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUnauthenticatedCallerCannotUnfreeze(): void {
		$service = $this->service(holdsRight: true, rows: [$this->finalRecord()], authenticated: false);

		$this->expectException(RuntimeException::class);

		$service->unfreeze(fileId: 4711, reason: 'no session');

	}//end testAnUnauthenticatedCallerCannotUnfreeze()

	/**
	 * Unfreezing without a reason is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testUnfreezingNeedsAReason(): void {
		$service = $this->service(holdsRight: true, rows: [$this->finalRecord()]);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/needs a reason/');

		$service->unfreeze(fileId: 4711, reason: '   ');

	}//end testUnfreezingNeedsAReason()

	/**
	 * A recorded unfreeze names the person, the moment and the reason, and leaves the mark.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUnfreezeIsRecordedWithPersonMomentAndReason(): void {
		$service = $this->service(holdsRight: true, rows: [$this->finalRecord()]);

		$stored = $service->unfreeze(fileId: 4711, reason: 'The besluit named the wrong street');

		$this->assertTrue($stored['unfrozen']);
		$this->assertSame('boris', $stored['unfrozenBy']);
		$this->assertSame('The besluit named the wrong street', $stored['unfrozenReason']);
		$this->assertNotSame('', (string)$stored['unfrozenAt']);
		$this->assertSame('draft', $stored['status']);

		// The facts that made it final are not erased by unfreezing it.
		$this->assertSame('anna', $stored['finalisedBy']);
		$this->assertSame('Het besluit is genomen', $stored['finalReason']);

	}//end testAnUnfreezeIsRecordedWithPersonMomentAndReason()

	/**
	 * A document nobody made final has nothing to unfreeze.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testADocumentThatIsNotFinalHasNothingToUnfreeze(): void {
		$service = $this->service(holdsRight: true, rows: []);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/not final/');

		$service->unfreeze(fileId: 4711, reason: 'nothing to do');

	}//end testADocumentThatIsNotFinalHasNothingToUnfreeze()

	/**
	 * The mark cannot be cleared once it is there.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testTheMarkCannotBeCleared(): void {
		$service = $this->service(holdsRight: true, rows: []);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessageMatches('/cannot be taken back/');

		$service->refuseClearingTheMark(
			incoming: ['unfrozen' => false],
			stored: ['unfrozen' => true]
		);

	}//end testTheMarkCannotBeCleared()

	/**
	 * A write that keeps the mark is allowed through.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAWriteThatKeepsTheMarkIsAllowed(): void {
		$service = $this->service(holdsRight: true, rows: []);

		$service->refuseClearingTheMark(
			incoming: ['unfrozen' => true, 'finalReason' => 'corrected'],
			stored: ['unfrozen' => true]
		);

		$this->addToAssertionCount(1);

	}//end testAWriteThatKeepsTheMarkIsAllowed()
}//end class

<?php

/**
 * Unit tests for DocumentGuard
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Editing\DocumentGuard;
use OCA\OpenRegister\Service\ObjectService;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Unit tests for the standing document refusals.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentGuardTest extends TestCase {

	/**
	 * A file with a fixed id.
	 *
	 * @return File The file.
	 */
	private function file(): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(4711);

		return $file;

	}//end file()

	/**
	 * Build a guard whose OpenRegister returns `$rows` for the named schema.
	 *
	 * @param array<string, array<int, mixed>> $bySchema Rows keyed by schema slug.
	 * @param bool $unreachable Whether OpenRegister throws instead of answering.
	 *
	 * @return DocumentGuard The guard.
	 */
	private function guard(array $bySchema, bool $unreachable = false): DocumentGuard {
		$objectService = $this->createMock(ObjectService::class);

		if ($unreachable === true) {
			$objectService->method('searchObjects')->willThrowException(new RuntimeException('no register'));
		} else {
			$objectService->method('searchObjects')->willReturnCallback(
				static function (array $query) use ($bySchema): array {
					return ($bySchema[($query['@self']['schema'] ?? '')] ?? []);
				}
			);
		}

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new DocumentGuard($resolver, $this->createMock(LoggerInterface::class));

	}//end guard()

	/**
	 * A file no signing request references is free to edit.
	 *
	 * @return void
	 */
	public function testAFileUnderNoSigningRequestPasses(): void {
		$this->assertNull($this->guard(['signingRequest' => []])->signatureRefusal($this->file()));

	}//end testAFileUnderNoSigningRequestPasses()

	/**
	 * A live signing request refuses the edit and names the status, so the
	 * caller can act on it rather than retry.
	 *
	 * @return void
	 */
	public function testALiveSigningRequestRefusesAndNamesTheStatus(): void {
		$refusal = $this->guard(['signingRequest' => [['status' => 'awaiting_signature']]])
			->signatureRefusal($this->file());

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('awaiting_signature', (string)$refusal);
		$this->assertStringContainsString('signing request', (string)$refusal);

	}//end testALiveSigningRequestRefusesAndNamesTheStatus()

	/**
	 * A cancelled signing request does NOT block: the process it belonged to
	 * was abandoned, so nothing depends on the document any more.
	 *
	 * @return void
	 */
	public function testACancelledSigningRequestDoesNotBlock(): void {
		$this->assertNull(
			$this->guard(['signingRequest' => [['status' => 'cancelled']]])->signatureRefusal($this->file())
		);

	}//end testACancelledSigningRequestDoesNotBlock()

	/**
	 * A row shaped as an OpenRegister object rather than a plain array is read
	 * the same way — the guard must not depend on which of the two shapes the
	 * register happens to return.
	 *
	 * @return void
	 */
	public function testAnObjectShapedRowIsReadTheSameWay(): void {
		$row = new class {
			/**
			 * Serialise like an OpenRegister object entity.
			 *
			 * @return array<string, mixed>
			 */
			public function jsonSerialize(): array {
				return ['object' => ['status' => 'signed']];
			}
		};

		$refusal = $this->guard(['signingRequest' => [$row]])->signatureRefusal($this->file());

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('signed', (string)$refusal);

	}//end testAnObjectShapedRowIsReadTheSameWay()

	/**
	 * A signing request with an unreadable status still refuses. "I could not
	 * tell" is not "it is fine" when the question is whether a signature
	 * depends on this document.
	 *
	 * @return void
	 */
	public function testAnUnreadableStatusStillRefuses(): void {
		$refusal = $this->guard(['signingRequest' => [['documentFileId' => '4711']]])
			->signatureRefusal($this->file());

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('unknown', (string)$refusal);

	}//end testAnUnreadableStatusStillRefuses()

	/**
	 * The signature check FAILS CLOSED. An unreachable register is exactly when
	 * an unnoticed edit to a document under signature is most likely, so the
	 * answer is "refuse", not "allow".
	 *
	 * @return void
	 */
	public function testTheSignatureCheckFailsClosedWhenTheRegisterIsUnreachable(): void {
		$refusal = $this->guard([], true)->signatureRefusal($this->file());

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('unreachable', (string)$refusal);

	}//end testTheSignatureCheckFailsClosedWhenTheRegisterIsUnreachable()

	/**
	 * Anonymisation output is refused, and the refusal says what to do instead.
	 *
	 * @return void
	 */
	public function testAnonymisationOutputIsRefusedWithAWayForward(): void {
		$refusal = $this->guard(['anonymizationLink' => [['anonymizedFileId' => 4711]]])
			->anonymisationRefusal($this->file());

		$this->assertNotNull($refusal);
		$this->assertStringContainsString('re-anonymise', (string)$refusal);

	}//end testAnonymisationOutputIsRefusedWithAWayForward()

	/**
	 * A document that is not redaction output passes.
	 *
	 * @return void
	 */
	public function testAnOrdinaryDocumentIsNotTreatedAsRedactionOutput(): void {
		$this->assertNull($this->guard(['anonymizationLink' => []])->anonymisationRefusal($this->file()));

	}//end testAnOrdinaryDocumentIsNotTreatedAsRedactionOutput()

	/**
	 * The anonymisation check fails OPEN, unlike the signature check. Failing
	 * closed here would block every ordinary edit on every instance that has
	 * never run an anonymisation, for a narrower harm.
	 *
	 * @return void
	 */
	public function testTheAnonymisationCheckFailsOpenWhenTheRegisterIsUnreachable(): void {
		$this->assertNull($this->guard([], true)->anonymisationRefusal($this->file()));

	}//end testTheAnonymisationCheckFailsOpenWhenTheRegisterIsUnreachable()
}//end class

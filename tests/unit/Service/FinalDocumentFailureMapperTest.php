<?php

/**
 * Unit tests for FinalDocumentFailureMapper
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

use OCA\Filinq\Exception\DocumentFinalException;
use OCA\Filinq\Service\FinalDocumentFailureMapper;
use OCP\AppFramework\Http;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that a refusal reaches the caller as a refusal, and that an
 * unrecognised failure reaches them as a generic 500 with the detail logged
 * rather than shown.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FinalDocumentFailureMapperTest extends TestCase {

	/**
	 * Build the mapper over a logger the test can read back.
	 *
	 * @param LoggerInterface|null $logger The logger to hand it, or null for a silent one.
	 *
	 * @return FinalDocumentFailureMapper The mapper.
	 */
	private function mapper(?LoggerInterface $logger = null): FinalDocumentFailureMapper {
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);

		return new FinalDocumentFailureMapper(
			$l10n,
			($logger ?? $this->createMock(LoggerInterface::class))
		);
	}//end mapper()

	/**
	 * A document that is final answers 409, carrying the guard's own sentence
	 * and the version it refused for.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAFinalDocumentIsAConflictCarryingTheSentenceAndTheVersion(): void {
		$shape = $this->mapper()->shape(
			exception: new DocumentFinalException(
				message: 'You cannot change this document.',
				version: ['status' => 'final', 'finalisedBy' => 'anna']
			),
			fallback: 'Could not carry that out'
		);

		$this->assertSame(Http::STATUS_CONFLICT, $shape['status']);
		$this->assertSame('You cannot change this document.', $shape['body']['error']);
		$this->assertSame('document-final', $shape['body']['reason']);
		$this->assertSame('anna', $shape['body']['version']['finalisedBy']);
	}//end testAFinalDocumentIsAConflictCarryingTheSentenceAndTheVersion()

	/**
	 * A document nobody can reach answers 404, and a service refusal with a
	 * sentence of its own answers 400 carrying it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAMissingDocumentIsNotFoundAndAServiceRefusalIsABadRequest(): void {
		$mapper = $this->mapper();

		$missing = $mapper->shape(
			exception: new RuntimeException('Document not found.'),
			fallback: 'Could not carry that out'
		);
		$this->assertSame(Http::STATUS_NOT_FOUND, $missing['status']);
		$this->assertSame('not-found', $missing['body']['reason']);

		$refused = $mapper->shape(
			exception: new RuntimeException('Unfreezing a final document needs a reason.'),
			fallback: 'Could not carry that out'
		);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $refused['status']);
		$this->assertSame('refused', $refused['body']['reason']);
		$this->assertSame('Unfreezing a final document needs a reason.', $refused['body']['error']);
	}//end testAMissingDocumentIsNotFoundAndAServiceRefusalIsABadRequest()

	/**
	 * An unrecognised failure is logged and answered generically, so a path or
	 * an identity in its message never reaches the caller.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function testAnUnrecognisedFailureIsLoggedAndAnsweredWithTheFallback(): void {
		$logged = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('error')->willReturnCallback(
			function (...$arguments) use (&$logged): void {
				$logged[] = (string)($arguments[0] ?? '');
			}
		);

		$shape = $this->mapper(logger: $logger)->shape(
			exception: new \LogicException('/var/www/html/data/anna/files/Besluit.docx is unreadable'),
			fallback: 'Could not carry that out'
		);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $shape['status']);
		$this->assertSame('Could not carry that out', $shape['body']['error']);
		$this->assertArrayNotHasKey('reason', $shape['body']);
		$this->assertStringNotContainsString('/var/www', json_encode($shape['body']));
		$this->assertCount(1, $logged);
	}//end testAnUnrecognisedFailureIsLoggedAndAnsweredWithTheFallback()
}//end class

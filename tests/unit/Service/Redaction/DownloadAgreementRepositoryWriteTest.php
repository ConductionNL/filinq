<?php

/**
 * Unit tests for DownloadAgreementRepository: writing an acceptance
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Redaction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Redaction\DownloadAgreementRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * An acceptance is written as its own row and a failed write stops the
 * download; a non-array search answer means no terms.
 */
#[CoversClass(DownloadAgreementRepository::class)]
class DownloadAgreementRepositoryWriteTest extends TestCase {

	/**
	 * The OpenRegister object service.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService $objects;

	/**
	 * The subject.
	 *
	 * @var DownloadAgreementRepository
	 */
	private DownloadAgreementRepository $repository;

	/**
	 * Build the subject over a mocked object service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->objects = $this->createMock(ObjectService::class);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objects);
		$this->repository = new DownloadAgreementRepository(
			objectResolver: $resolver,
			logger: $this->createMock(LoggerInterface::class)
		);
	}//end setUp()

	/**
	 * The acceptance row names the document, version, text and trimmed person, with a timestamp.
	 *
	 * @return void
	 */
	public function testAnAcceptanceIsWrittenAsItsOwnRow(): void {
		$written = null;
		$this->objects->expects($this->once())
			->method('saveObject')
			->willReturnCallback(function (...$args) use (&$written): array {
				// Positional: object, register, schema.
				$written = $args;
				return ['object' => $args[0] + ['stored' => true]];
			});

		$stored = $this->repository->recordAcceptance(document: 'doc-1', person: '  alice ', version: '2', text: 'Do not share.');

		$this->assertSame('filinq', $written[1]);
		$this->assertSame('downloadAgreement', $written[2]);
		$row = $written[0];
		$this->assertSame('doc-1', $row['document']);
		$this->assertSame('2', $row['version']);
		$this->assertSame('Do not share.', $row['text']);
		$this->assertSame('alice', $row['acceptedBy']);
		$this->assertSame('2', $row['acceptedVersion']);
		$this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $row['acceptedAt']));
		$this->assertTrue($stored['stored']);
		$this->assertSame('alice', $stored['acceptedBy']);
	}//end testAnAcceptanceIsWrittenAsItsOwnRow()

	/**
	 * A failed write raises with the cause, so the download does not go ahead.
	 *
	 * @return void
	 */
	public function testAFailedWriteStopsTheDownload(): void {
		$cause = new \RuntimeException('disk full');
		$this->objects->method('saveObject')->willThrowException($cause);

		try {
			$this->repository->recordAcceptance(document: 'doc-1', person: 'alice', version: '1', text: 'x');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('The acceptance could not be recorded: disk full', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAFailedWriteStopsTheDownload()

	/**
	 * A non-array search answer means there are no terms in force.
	 *
	 * @return void
	 */
	public function testANonArraySearchAnswerMeansNoTerms(): void {
		$this->objects->expects($this->once())
			->method('searchObjectsBySlug')
			->with('filinq', 'downloadAgreement', ['document' => 'doc-2'])
			->willReturn(0);

		$this->assertNull($this->repository->forDocument(document: 'doc-2'));
	}//end testANonArraySearchAnswerMeansNoTerms()
}//end class

<?php

/**
 * Golden tests for which terms are in force and which acceptance counts.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Redaction\DownloadAgreementRepository;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins the terms in force (newest version, never an acceptance row) and the
 * acceptance that counts (the latest of this person's, nobody else's).
 */
class DownloadAgreementRepositoryReadGoldenTest extends TestCase {

	/**
	 * A repository over a store holding these rows for every document.
	 *
	 * @param array<int, array<string, mixed>> $rows The stored rows.
	 *
	 * @return DownloadAgreementRepository
	 */
	private function repository(array $rows): DownloadAgreementRepository {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('searchObjectsBySlug')->willReturn($rows);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new DownloadAgreementRepository($resolver, $this->createMock(LoggerInterface::class));

	}//end repository()

	/**
	 * A row's marker field, or null for no row.
	 *
	 * @param array<string, mixed>|null $row The row.
	 *
	 * @return string|null
	 */
	private function marker(?array $row): ?string {
		if ($row === null) {
			return null;
		}

		return (string) ($row['marker'] ?? '');

	}//end marker()

	/**
	 * The newest terms are in force; acceptance rows and empty texts are never terms.
	 *
	 * @return void
	 */
	public function testTheTermsInForce(): void {
		$rows = [
			['marker' => 'v2', 'version' => '2', 'text' => 'Twee'],
			['marker' => 'accepted', 'version' => '9', 'text' => 'Negen', 'acceptedBy' => 'anna'],
			['marker' => 'empty', 'version' => '8', 'text' => '  '],
			['marker' => 'v10', 'version' => '10', 'text' => 'Tien'],
			['marker' => 'v1', 'version' => '1', 'text' => 'Een'],
			['marker' => 'v10-again', 'version' => '10', 'text' => 'Tien opnieuw'],
			['marker' => 'blank-acceptor', 'version' => '1.5', 'text' => 'Anderhalf', 'acceptedBy' => ' '],
		];

		$this->assertSame('v10-again', $this->marker($this->repository($rows)->forDocument('doc-1')));
		$this->assertSame('first', $this->marker($this->repository([['marker' => 'first', 'text' => 'Zonder versie']])->forDocument('doc-1')));
		$this->assertNull($this->repository([['marker' => 'x', 'version' => '3', 'acceptedBy' => 'anna', 'text' => 'Drie']])->forDocument('doc-1'));
		$this->assertNull($this->repository([['marker' => 'y', 'version' => '3']])->forDocument('doc-1'));
		$this->assertNull($this->repository([])->forDocument('doc-1'));
		$this->assertNull($this->repository($rows)->forDocument(' '));

	}//end testTheTermsInForce()

	/**
	 * The latest acceptance of this person counts; nobody else's does, and a blank person has none.
	 *
	 * @return void
	 */
	public function testTheAcceptanceThatCounts(): void {
		$rows = [
			['marker' => 'terms', 'version' => '2', 'text' => 'Twee'],
			['marker' => 'anna-old', 'acceptedBy' => 'anna', 'acceptedAt' => '2026-09-01T10:00:00+00:00'],
			['marker' => 'anna-new', 'acceptedBy' => ' anna ', 'acceptedAt' => '2026-09-03T10:00:00+00:00'],
			['marker' => 'anna-same', 'acceptedBy' => 'anna', 'acceptedAt' => '2026-09-03T10:00:00+00:00'],
			['marker' => 'bert', 'acceptedBy' => 'bert', 'acceptedAt' => '2026-09-09T10:00:00+00:00'],
		];

		$this->assertSame('anna-new', $this->marker($this->repository($rows)->acceptanceOf('doc-1', 'anna')));
		$this->assertSame('anna-new', $this->marker($this->repository($rows)->acceptanceOf('doc-1', ' anna')));
		$this->assertSame('bert', $this->marker($this->repository($rows)->acceptanceOf('doc-1', 'bert')));
		$this->assertNull($this->repository($rows)->acceptanceOf('doc-1', 'carla'));
		$this->assertNull($this->repository($rows)->acceptanceOf('doc-1', '  '));

		$undated = [['marker' => 'undated', 'acceptedBy' => 'anna'], ['marker' => 'also-undated', 'acceptedBy' => 'anna']];
		$this->assertSame('undated', $this->marker($this->repository($undated)->acceptanceOf('doc-1', 'anna')));

	}//end testTheAcceptanceThatCounts()
}//end class

<?php

/**
 * Portal Released Documents Test
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Portal
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-5.1
 * @spec openspec/changes/specify-capability-rows/tasks.md#task-3.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Portal;

use OCA\Filinq\Kernel\Contracts\DownloadAgreementGateInterface;
use OCA\Filinq\Portal\PortalContributionProvider;
use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCA\Filinq\Service\DocumentReleaseService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The data-subject manifest lists a requester's released documents, and a
 * download waits for the conditions the document carries.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Portal
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
#[CoversClass(PortalContributionProvider::class)]
class PortalReleasedDocumentsTest extends TestCase {

	/**
	 * The one entry a release lists.
	 *
	 * @var array<string, mixed>
	 */
	private const ENTRY = [
		'id' => 'rel-1',
		'title' => 'Besluit.pdf',
		'kind' => 'decision',
		'date' => '2026-11-01T10:00:00+00:00',
		'file' => ['register' => 'zaken', 'schema' => 'zaak', 'id' => 'zaak-1', 'fileId' => '4711'],
	];

	/**
	 * A release service that lists ENTRY for rel-1 and nothing for anything else.
	 *
	 * @return DocumentReleaseService The fake.
	 */
	private function releases(): DocumentReleaseService {
		$releases = $this->createMock(DocumentReleaseService::class);
		$releases->method('portalEntries')->willReturnCallback(
			static fn (string $releaseId): array => ($releaseId === 'rel-1' ? [self::ENTRY] : [])
		);

		return $releases;

	}//end releases()

	/**
	 * The data-subject manifest declares the requester's documents, scoped by their contact.
	 *
	 * @return void
	 */
	public function testTheRequesterDocumentsCollectionIsScopedToTheirContact(): void {
		$manifest = (new PortalContributionProvider())->getContribution(subject: ['audience' => 'data-subject']);
		$documents = array_values(array_filter($manifest['collections'], static fn (array $one): bool => $one['id'] === 'subjectDocuments'))[0];

		$this->assertSame(expected: 'documentRelease', actual: $documents['schema']);
		$this->assertSame(expected: 'contactRef', actual: $documents['scopeField']);
		$this->assertSame(expected: 'contactId', actual: $documents['scopeClaim']);
		$this->assertSame(expected: 'substantial', actual: $documents['minTrust']);
		$this->assertSame(expected: ['documentName', 'kind', 'releasedAt', 'withdrawnAt'], actual: $documents['fields']);
		$this->assertSame(expected: 'releasedDocuments', actual: $documents['documents']['provider']);
		$this->assertSame(expected: 'releasedDocumentOpened', actual: $documents['documents']['opened']);
		$this->assertArrayNotHasKey(key: 'contactRef', array: array_flip($documents['fields']), message: 'The scope key is never projected.');

	}//end testTheRequesterDocumentsCollectionIsScopedToTheirContact()

	/**
	 * The documents call answers the release's document, and nothing without the service.
	 *
	 * @return void
	 */
	public function testTheDocumentsCallListsOnlyTheReleasedDocument(): void {
		$provider = new PortalContributionProvider(releases: $this->releases());

		$this->assertSame(expected: [self::ENTRY], actual: $provider->releasedDocuments(releaseId: 'rel-1'));
		$this->assertSame(expected: [], actual: $provider->releasedDocuments(releaseId: 'rel-2'));
		$this->assertSame(expected: [], actual: (new PortalContributionProvider())->releasedDocuments(releaseId: 'rel-1'));

	}//end testTheDocumentsCallListsOnlyTheReleasedDocument()

	/**
	 * A download is allowed only once this subject accepted the conditions the file carries.
	 *
	 * @return void
	 */
	public function testADownloadWaitsForTheAcceptedAgreement(): void {
		$asked = [];
		$agreements = $this->createMock(DownloadAgreementGateInterface::class);
		$agreements->method('check')->willReturnCallback(
			static function (string $document, string $person) use (&$asked): array {
				$asked[] = $document . '|' . $person;

				return ['mayDownload' => ($person === 'portal:subject-accepted')];
			}
		);
		$provider = new PortalContributionProvider(releases: $this->releases(), agreements: $agreements);

		$this->assertFalse(condition: $provider->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: ['subjectRef' => 'subject-new']));
		$this->assertTrue(condition: $provider->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: ['subjectRef' => 'subject-accepted']));
		$this->assertSame(expected: ['4711|portal:subject-new', '4711|portal:subject-accepted'], actual: $asked);

	}//end testADownloadWaitsForTheAcceptedAgreement()

	/**
	 * Anything that cannot be decided refuses: no gate, no subject, an unlisted id, a failing gate.
	 *
	 * @return void
	 */
	public function testAnUndecidableDownloadIsRefused(): void {
		$open = $this->createMock(DownloadAgreementGateInterface::class);
		$open->method('check')->willReturn(['mayDownload' => true]);
		$failing = $this->createMock(DownloadAgreementGateInterface::class);
		$failing->method('check')->willThrowException(new RuntimeException('store down'));

		$subject = ['subjectRef' => 'subject-1'];
		$this->assertFalse(condition: (new PortalContributionProvider(releases: $this->releases()))->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: $subject));
		$this->assertFalse(condition: (new PortalContributionProvider(releases: $this->releases(), agreements: $open))->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: []));
		$this->assertFalse(condition: (new PortalContributionProvider(releases: $this->releases(), agreements: $open))->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'other', subject: $subject));
		$this->assertFalse(condition: (new PortalContributionProvider(releases: $this->releases(), agreements: $failing))->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: $subject));
		$this->assertTrue(condition: (new PortalContributionProvider(releases: $this->releases(), agreements: $open))->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: $subject));

	}//end testAnUndecidableDownloadIsRefused()

	/**
	 * An opening portaliq may go ahead with enters the document's trail once;
	 * a refused one writes nothing.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/tasks.md#task-2.3
	 */
	public function testAnAllowedOpeningIsWrittenIntoTheTrail(): void {
		$agreements = $this->createMock(DownloadAgreementGateInterface::class);
		$agreements->method('check')->willReturnCallback(
			static fn (string $document, string $person): array => ['mayDownload' => ($person === 'portal:subject-accepted')]
		);
		$audit = $this->createMock(DocumentActionAudit::class);
		$audit->expects($this->once())->method('record')
			->with(4711, 'opened', ['release' => 'rel-1', 'via' => 'portal'])
			->willReturn(true);
		$provider = new PortalContributionProvider(releases: $this->releases(), agreements: $agreements, documentAudit: $audit);

		$this->assertFalse(condition: $provider->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: ['subjectRef' => 'subject-new']));
		$this->assertTrue(condition: $provider->releasedDocumentOpened(releaseId: 'rel-1', documentId: 'rel-1', subject: ['subjectRef' => 'subject-accepted']));

	}//end testAnAllowedOpeningIsWrittenIntoTheTrail()
}//end class

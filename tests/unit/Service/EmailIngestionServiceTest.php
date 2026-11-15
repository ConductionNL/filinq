<?php

/**
 * EmailIngestionService tests
 *
 * The real service, reader, thread-header extraction, filing, settings and
 * repository over an in-memory OpenRegister that validates every record
 * against the real emailDocument fragment, and a filesystem whose files
 * really leave the inbox when they are filed. Only OpenRegister's EML parser
 * and the conversion cascade are doubles, both of their real classes.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/EmailIngestion/EmailIngestionDoubles.php';

use OCA\Filinq\Service\EmailIngestion\EmailIngestionService;
use OCA\Filinq\Service\EmailIngestion\EmailIngestionSettings;
use OCA\Filinq\Tests\Unit\Service\EmailIngestion\EmailInboxWorld;
use PHPUnit\Framework\TestCase;

/**
 * Watched-folder email ingestion, end to end below the controller.
 */
class EmailIngestionServiceTest extends TestCase {
	use EmailInboxWorld;

	/**
	 * Build the world.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->setUpWorld();

	}//end setUp()


	/**
	 * Two dropped emails are moved into the dossier's folder, each with a filed record.
	 *
	 * @return void
	 */
	public function testDroppedEmailsAreFiledIntoTheMappedDossier(): void {
		$first = self::eml(messageId: 'aaa-1@example.org', subject: 'Woo-verzoek horeca');
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: $first);
		$this->drop(folderId: self::INBOX, name: 'aanvulling.eml', content: self::eml(messageId: 'aaa-2@example.org'));

		$summary = $this->service()->scan(limit: 25);

		$this->assertSame(['processed' => 2, 'filed' => 2, 'failed' => 0, 'duplicates' => 0], $summary);
		$this->assertSame([], $this->namesIn(folderId: self::INBOX));
		$this->assertSame(
			['aanvulling.eml', 'aanvulling_anonymized.pdf', 'verzoek.eml', 'verzoek_anonymized.pdf'],
			$this->namesIn(folderId: self::DOSSIER_FOLDER)
		);
		$this->assertCount(2, $this->store->rows);
		$record = $this->recordFor(messageId: 'aaa-1@example.org');
		$this->assertSame('filed', $record['status']);
		$this->assertSame('dossier-017', $record['dossierRef']);
		$this->assertSame('Woo-verzoek horeca', $record['subject']);
		$this->assertSame('verzoeker@example.org', $record['fromAddress']);
		$this->assertSame(['woo@demostad.example'], $record['toAddresses']);
		$this->assertSame('2026-06-20T08:41:00+00:00', $record['sentAt']);
		$this->assertSame(hash('sha256', $first), $record['contentHash']);
		$this->assertSame('watched-folder', $record['ingestSource']);
		$this->assertSame(1, $record['attachmentCount']);
		$this->assertSame(['machtiging.pdf'], $record['attachmentNames']);
		$this->assertNotSame('', (string) ($record['pdfFileRef'] ?? ''));
		// The job writes as the system, past OpenRegister's RBAC (recorded bypass).
		$this->assertFalse($this->store->saves[0][1]);

	}//end testDroppedEmailsAreFiledIntoTheMappedDossier()

	/**
	 * The same file dropped again leaves no second record and no second copy.
	 *
	 * @return void
	 */
	public function testRedropIsIdempotent(): void {
		$bytes = self::eml(messageId: 'redrop@example.org');
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: $bytes);
		$this->service()->scan(limit: 25);

		$again = $this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: $bytes);
		$summary = $this->service()->scan(limit: 25);

		$this->assertSame(['processed' => 1, 'filed' => 0, 'failed' => 0, 'duplicates' => 1], $summary);
		$this->assertCount(1, $this->store->rows);
		$this->assertSame([], $this->namesIn(folderId: self::INBOX));
		$this->assertSame(['verzoek.eml', 'verzoek_anonymized.pdf'], $this->namesIn(folderId: self::DOSSIER_FOLDER));
		$this->assertSame([$again], $this->deleted);

	}//end testRedropIsIdempotent()

	/**
	 * A re-export of the same message (other bytes, same Message-ID) for the same dossier is a duplicate too.
	 *
	 * @return void
	 */
	public function testSameMessageIdForTheSameDossierIsADuplicate(): void {
		$this->drop(folderId: self::INBOX, name: 'a.eml', content: self::eml(messageId: 'same@example.org', subject: 'Eerste export'));
		$this->service()->scan(limit: 25);
		$this->drop(folderId: self::INBOX, name: 'b.eml', content: self::eml(messageId: 'same@example.org', subject: 'Tweede export'));

		$summary = $this->service()->scan(limit: 25);

		$this->assertSame(1, $summary['duplicates']);
		$this->assertCount(1, $this->store->rows);

	}//end testSameMessageIdForTheSameDossierIsADuplicate()

	/**
	 * A reply carries the message id it answers and shares the thread key; body lines are not headers.
	 *
	 * @return void
	 */
	public function testThreadingHeadersExtracted(): void {
		$this->drop(folderId: self::INBOX, name: 'a.eml', content: self::eml(messageId: 'Root-A@Example.org'));
		$this->service()->scan(limit: 25);
		$reply = self::eml(
			messageId: 'reply-b@demostad.example',
			subject: 'Re: Woo-verzoek',
			extraHeads: "In-Reply-To: <Root-A@Example.org>\r\nReferences: <Root-A@Example.org>\r\n <mid-c@example.org>"
		);
		$this->drop(folderId: self::INBOX, name: 'b.eml', content: $reply);

		$this->service()->scan(limit: 25);

		$first = $this->recordFor(messageId: 'Root-A@Example.org');
		$second = $this->recordFor(messageId: 'reply-b@demostad.example');
		$this->assertArrayNotHasKey('inReplyTo', $first, 'a body line that looks like a header is not a header');
		$this->assertSame([], $first['references']);
		$this->assertSame('Root-A@Example.org', $second['inReplyTo']);
		$this->assertSame(['Root-A@Example.org', 'mid-c@example.org'], $second['references']);
		$this->assertSame('root-a@example.org', $first['threadKey']);
		$this->assertSame($first['threadKey'], $second['threadKey']);

	}//end testThreadingHeadersExtracted()

	/**
	 * A conversion outage still files the mail; retrying converts without a second record.
	 *
	 * @return void
	 */
	public function testConversionOutageStillCapturesTheMail(): void {
		$this->conversionDown = true;
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: self::eml(messageId: 'outage@example.org'));

		$summary = $this->service()->scan(limit: 25);

		$this->assertSame(1, $summary['filed']);
		$uuid = (string) array_key_first($this->store->rows);
		$this->assertSame('filed', $this->store->rows[$uuid]['status']);
		$this->assertArrayNotHasKey('pdfFileRef', $this->store->rows[$uuid]);
		$this->assertSame(['verzoek.eml'], $this->namesIn(folderId: self::DOSSIER_FOLDER));

		$this->conversionDown = false;
		$record = $this->service()->retryConversion(uuid: $uuid);

		$this->assertNotSame('', (string) ($record['pdfFileRef'] ?? ''));
		$this->assertCount(1, $this->store->rows);
		$this->assertSame(['verzoek.eml', 'verzoek_anonymized.pdf'], $this->namesIn(folderId: self::DOSSIER_FOLDER));

	}//end testConversionOutageStillCapturesTheMail()

	/**
	 * A dropped .msg is a failed record, stays in the inbox, and is recorded once.
	 *
	 * @return void
	 */
	public function testMsgIsAVisibleFailureThatStaysInTheInbox(): void {
		$this->drop(folderId: self::INBOX, name: 'outlook.msg', content: "\xD0\xCF\x11\xE0 binary");

		$summary = $this->service()->scan(limit: 25);
		$this->service()->scan(limit: 25);

		$this->assertSame(['processed' => 1, 'filed' => 0, 'failed' => 1, 'duplicates' => 0], $summary);
		$this->assertSame(['outlook.msg'], $this->namesIn(folderId: self::INBOX));
		$this->assertCount(1, $this->store->rows, 'a failure is recorded once, not on every tick');
		$record = array_values($this->store->rows)[0];
		$this->assertSame('failed', $record['status']);
		$this->assertSame(EmailIngestionService::REASON_UNSUPPORTED_FORMAT, $record['failureReason']);

	}//end testMsgIsAVisibleFailureThatStaysInTheInbox()

	/**
	 * An email the parser refuses is a failed record whose reason carries no body text.
	 *
	 * @return void
	 */
	public function testUnparseableEmailFailsWithoutBodyContent(): void {
		$this->drop(folderId: self::INBOX, name: 'kapot.eml', content: "garbage BSN 123456782\r\n\r\ngeheime inhoud");

		$this->service()->scan(limit: 25);

		$record = array_values($this->store->rows)[0];
		$this->assertSame('failed', $record['status']);
		$this->assertSame(EmailIngestionService::REASON_UNPARSEABLE, $record['failureReason']);
		$this->assertSame(['kapot.eml'], $this->namesIn(folderId: self::INBOX));

	}//end testUnparseableEmailFailsWithoutBodyContent()

	/**
	 * Without OpenRegister's parser the mail is still filed, with the id-shaped headers read by filinq.
	 *
	 * @return void
	 */
	public function testMissingParserStillFilesTheMail(): void {
		$this->parserInstalled = false;
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: self::eml(messageId: 'noparser@example.org'));

		$this->service()->scan(limit: 25);

		$record = $this->recordFor(messageId: 'noparser@example.org');
		$this->assertSame('filed', $record['status']);
		$this->assertSame('noparser@example.org', $record['threadKey']);

	}//end testMissingParserStillFilesTheMail()

	/**
	 * A dossier without a reachable folder leaves the mail in the inbox with a reason.
	 *
	 * @return void
	 */
	public function testMissingDossierFolderFailsVisibly(): void {
		$this->config[EmailIngestionSettings::KEY_INBOXES] = json_encode([['folderId' => self::INBOX, 'dossierRef' => 'dossier-gone']]);
		$this->drop(folderId: self::INBOX, name: 'verzoek.eml', content: self::eml(messageId: 'orphan@example.org'));

		$this->service()->scan(limit: 25);

		$record = array_values($this->store->rows)[0];
		$this->assertSame('failed', $record['status']);
		$this->assertSame(EmailIngestionService::REASON_NO_DOSSIER_FOLDER, $record['failureReason']);
		$this->assertSame(['verzoek.eml'], $this->namesIn(folderId: self::INBOX));

	}//end testMissingDossierFolderFailsVisibly()

	/**
	 * Files that are not email are not touched and not counted.
	 *
	 * @return void
	 */
	public function testOtherFilesAreLeftAlone(): void {
		$this->drop(folderId: self::INBOX, name: 'notitie.txt', content: 'x');

		$summary = $this->service()->scan(limit: 25);

		$this->assertSame(0, $summary['processed']);
		$this->assertSame(['notitie.txt'], $this->namesIn(folderId: self::INBOX));
		$this->assertSame([], $this->store->rows);

	}//end testOtherFilesAreLeftAlone()

	/**
	 * A tick processes at most its budget.
	 *
	 * @return void
	 */
	public function testATickStopsAtItsBudget(): void {
		for ($i = 0; $i < 7; $i++) {
			$this->drop(folderId: self::INBOX, name: sprintf('m%02d.eml', $i), content: self::eml(messageId: 'budget-' . $i . '@example.org'));
		}

		$this->assertSame(5, $this->service()->scan(limit: 5)['processed']);
		$this->assertCount(2, $this->namesIn(folderId: self::INBOX));

	}//end testATickStopsAtItsBudget()

	/**
	 * The stored record for a message id.
	 *
	 * @param string $messageId The id.
	 *
	 * @return array<string, mixed> The record.
	 */
	private function recordFor(string $messageId): array {
		foreach ($this->store->rows as $row) {
			if (($row['messageId'] ?? '') === $messageId) {
				return $row;
			}
		}

		$this->fail('no record for ' . $messageId);

	}//end recordFor()
}//end class

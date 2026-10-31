<?php

/**
 * EmailThreadHeaders tests
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EmailIngestion;

use OCA\Filinq\Service\EmailIngestion\EmailThreadHeaders;
use PHPUnit\Framework\TestCase;

/**
 * Reading In-Reply-To and References from the raw header block.
 */
class EmailThreadHeadersTest extends TestCase {

	/**
	 * Folded References, lower-case header names and LF line ends are read; the body is not.
	 *
	 * @return void
	 */
	public function testFoldedAndCaseInsensitiveHeadersAreRead(): void {
		$raw = "message-id: <Own@X.org>\nreferences: <Root@X.org>\n\t<Mid@X.org>  <Parent@X.org>\nin-reply-to: <Parent@X.org> (from Jan)\n\nReferences: <body@x.org>\n";

		$headers = (new EmailThreadHeaders())->read(raw: $raw);

		$this->assertSame(['messageId' => 'Own@X.org', 'inReplyTo' => 'Parent@X.org', 'references' => ['Root@X.org', 'Mid@X.org', 'Parent@X.org']], $headers);

	}//end testFoldedAndCaseInsensitiveHeadersAreRead()

	/**
	 * The thread key: first reference, else in-reply-to, else the own id; brackets stripped, lower case.
	 *
	 * @return void
	 */
	public function testTheThreadKeyFallsBackInOrder(): void {
		$headers = new EmailThreadHeaders();

		$this->assertSame('root@x.org', $headers->threadKey(messageId: 'Own@X.org', inReplyTo: 'Parent@X.org', references: ['<Root@X.org>', 'Parent@X.org']));
		$this->assertSame('parent@x.org', $headers->threadKey(messageId: 'Own@X.org', inReplyTo: '<Parent@X.org>', references: []));
		$this->assertSame('own@x.org', $headers->threadKey(messageId: ' <Own@X.org> ', inReplyTo: '', references: []));
		$this->assertSame('', $headers->threadKey(messageId: '', inReplyTo: '', references: []));

	}//end testTheThreadKeyFallsBackInOrder()

	/**
	 * A message without thread headers answers empty values, not an error.
	 *
	 * @return void
	 */
	public function testMissingHeadersAreEmpty(): void {
		$this->assertSame(['messageId' => '', 'inReplyTo' => '', 'references' => []], (new EmailThreadHeaders())->read(raw: "Subject: hallo\r\n\r\nbody"));

	}//end testMissingHeadersAreEmpty()
}//end class

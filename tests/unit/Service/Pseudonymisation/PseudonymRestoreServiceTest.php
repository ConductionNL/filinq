<?php

/**
 * Unit tests for the restore of a reversibly anonymised copy
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-5.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Pseudonymisation;
use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreAudit;
use PHPUnit\Framework\TestCase;

/**
 * The refusal paths first: who may not, what may not happen without an audit
 * entry, and what may not come out broken. Then the restore itself.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Pseudonymisation
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymRestoreServiceTest extends TestCase {
	use PseudonymDoubles;

	private const MEMBERS = [
		'root' => ['admin'],
		'petra' => ['privacy-officers'],
		'bob' => ['staff'],
	];

	/**
	 * Refuse and return the refusal.
	 *
	 * @param callable $call The restore call.
	 *
	 * @return PseudonymRestoreRefusedException The refusal.
	 */
	private function refusal(callable $call): PseudonymRestoreRefusedException {
		try {
			$call();
		} catch (PseudonymRestoreRefusedException $refusal) {
			return $refusal;
		}

		$this->fail('The restore was expected to be refused.');

	}//end refusal()

	/**
	 * A user in neither the group nor admin is refused, nothing is written, and
	 * the denial is in the audit trail.
	 *
	 * @return void
	 */
	public function testANonMemberIsRefusedAndTheDenialIsLogged(): void {
		$container = $this->container();
		$linkId = $this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$written = [];
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS),
			copy: $this->anonymisedCopy(mimeType: 'text/plain', content: '[PERSOON: 1]', written: $written),
			container: $container
		);

		$refusal = $this->refusal(fn () => $service->restore(linkId: $linkId, userId: 'bob'));

		$this->assertSame(PseudonymRestoreRefusedException::REASON_NOT_ALLOWED, $refusal->getReason());
		$this->assertSame([], $written);
		$this->assertSame(PseudonymRestoreAudit::ACTION_DENIED, $this->auditEntries[0][0]);
		$this->assertSame('bob', $this->auditEntries[0][2]['actor']);
		$this->assertSame('not_allowed', $this->auditEntries[0][2]['reason']);

	}//end testANonMemberIsRefusedAndTheDenialIsLogged()

	/**
	 * An empty list means admins only; a list that does not read means nobody, not even an admin.
	 *
	 * @return void
	 */
	public function testTheGroupListFailsClosed(): void {
		$this->assertTrue($this->gate(allowedGroups: '[]', membership: self::MEMBERS)->mayRestore(userId: 'root'));
		$this->assertFalse($this->gate(allowedGroups: '[]', membership: self::MEMBERS)->mayRestore(userId: 'petra'));
		$this->assertTrue($this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS)->mayRestore(userId: 'petra'));

		foreach (['not json', '{"a":"privacy-officers"}', '[1]', ''] as $broken) {
			$this->assertFalse($this->gate(allowedGroups: $broken, membership: self::MEMBERS)->mayRestore(userId: 'root'), $broken);
		}

	}//end testTheGroupListFailsClosed()

	/**
	 * When the audit trail cannot record the grant, nothing is decrypted into a file.
	 *
	 * @return void
	 */
	public function testAFailedAuditWriteBlocksTheRestore(): void {
		$container = $this->container();
		$linkId = $this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$this->failingAuditActions = [PseudonymRestoreAudit::ACTION_GRANTED];
		$written = [];
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS),
			copy: $this->anonymisedCopy(mimeType: 'text/plain', content: '[PERSOON: 1]', written: $written),
			container: $container
		);

		$refusal = $this->refusal(fn () => $service->restore(linkId: $linkId, userId: 'petra'));

		$this->assertSame(PseudonymRestoreRefusedException::REASON_AUDIT_UNAVAILABLE, $refusal->getReason());
		$this->assertSame([], $written);

	}//end testAFailedAuditWriteBlocksTheRestore()

	/**
	 * A permitted user who cannot open the copy gets the same answer as for a link that does not exist.
	 *
	 * @return void
	 */
	public function testACopyTheCallerCannotOpenIsNotFound(): void {
		$container = $this->container();
		$linkId = $this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS),
			copy: null,
			container: $container
		);

		$this->assertSame(PseudonymRestoreRefusedException::REASON_NOT_FOUND, $this->refusal(fn () => $service->restore(linkId: $linkId, userId: 'petra'))->getReason());
		$this->assertSame(PseudonymRestoreRefusedException::REASON_NOT_FOUND, $this->refusal(fn () => $service->restore(linkId: 'no-such-link', userId: 'petra'))->getReason());
		$this->assertSame(PseudonymRestoreAudit::ACTION_DENIED, $this->auditEntries[0][0]);

	}//end testACopyTheCallerCannotOpenIsNotFound()

	/**
	 * An irreversible run has no key: refused, and the failure is on record.
	 *
	 * @return void
	 */
	public function testAnIrreversibleCopyCannotBeRestored(): void {
		$container = $this->container();
		$this->rows['anonymizationLink']['link-1'] = ['sourceFileId' => 812200, 'anonymizedFileId' => 812201];
		$written = [];
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '[]', membership: self::MEMBERS),
			copy: $this->anonymisedCopy(mimeType: 'text/plain', content: '[PERSOON: 1]', written: $written),
			container: $container
		);

		$this->assertSame(PseudonymRestoreRefusedException::REASON_NO_MAP, $this->refusal(fn () => $service->restore(linkId: 'link-1', userId: 'root'))->getReason());
		$this->assertSame(PseudonymRestoreAudit::ACTION_FAILED, $this->auditEntries[0][0]);

	}//end testAnIrreversibleCopyCannotBeRestored()

	/**
	 * A permitted user gets a restored copy next to the anonymised one, which is not touched.
	 *
	 * @return void
	 */
	public function testAPermittedUserGetsARestoredCopy(): void {
		$container = $this->container();
		$linkId = $this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$written = [];
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS),
			copy: $this->anonymisedCopy(mimeType: 'text/plain', content: 'Geachte [PERSOON: 10], namens [PERSOON: 1].', written: $written),
			container: $container
		);

		$result = $service->restore(linkId: $linkId, userId: 'petra');

		$this->assertSame('copy', $result['mode']);
		$this->assertSame([['besluit_restored.txt', 'Geachte Marieke de Vries, namens Jan Jansen.']], $written);
		$this->assertSame(2, $result['restored']);
		$this->assertSame(
			[PseudonymRestoreAudit::ACTION_GRANTED, PseudonymRestoreAudit::ACTION_RESTORED],
			array_column($this->auditEntries, 0)
		);
		foreach ($this->auditEntries as $entry) {
			$this->assertStringNotContainsString('Jan Jansen', (string) json_encode($entry), 'An audit entry names what was restored, never the value.');
		}

	}//end testAPermittedUserGetsARestoredCopy()

	/**
	 * A PDF cannot be rewritten safely: the answer is the placeholder list, and no file.
	 *
	 * @return void
	 */
	public function testUnsafeFormatReturnsReport(): void {
		$container = $this->container();
		$linkId = $this->seedReversibleRun(container: $container, pairs: array_reverse($this->twoPeople()));
		$written = [];
		$service = $this->restoreService(
			gate: $this->gate(allowedGroups: '[]', membership: self::MEMBERS),
			copy: $this->anonymisedCopy(mimeType: 'application/pdf', content: '%PDF-1.7 binary', written: $written),
			container: $container
		);

		$result = $service->restore(linkId: $linkId, userId: 'root');

		$this->assertSame('report', $result['mode']);
		$this->assertSame('format_not_rewritable', $result['reason']);
		$this->assertSame(['[PERSOON: 1]', '[PERSOON: 10]'], array_column($result['entries'], 'placeholder'));
		$this->assertSame([], $written);

	}//end testUnsafeFormatReturnsReport()

	/**
	 * The status names the link and whether this caller may restore, without the pairs.
	 *
	 * @return void
	 */
	public function testTheStatusSaysWhetherTheCallerMayRestore(): void {
		$container = $this->container();
		$this->seedReversibleRun(container: $container, pairs: $this->twoPeople());
		$written = [];
		$copy = $this->anonymisedCopy(mimeType: 'text/plain', content: '', written: $written);
		$gate = $this->gate(allowedGroups: '["privacy-officers"]', membership: self::MEMBERS);

		$forPetra = $this->restoreService(gate: $gate, copy: $copy, container: $container)->status(anonymizedFileId: 812201, userId: 'petra');
		$forBob = $this->restoreService(gate: $gate, copy: $copy, container: $container)->status(anonymizedFileId: 812201, userId: 'bob');

		$this->assertSame(['linkId' => 'link-1', 'reversible' => true, 'entryCount' => 2, 'mayRestore' => true], $forPetra);
		$this->assertFalse($forBob['mayRestore']);
		$this->assertNull($this->restoreService(gate: $gate, copy: null, container: $container)->status(anonymizedFileId: 812201, userId: 'petra'));

	}//end testTheStatusSaysWhetherTheCallerMayRestore()
}//end class

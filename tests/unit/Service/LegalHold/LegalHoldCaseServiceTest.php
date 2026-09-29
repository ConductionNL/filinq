<?php

/**
 * Legal hold cases: refusals first, then freezing, overlap, retry, release.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\LegalHold
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\LegalHold;

use OCA\Filinq\Exception\LegalHoldRefusedException;
use PHPUnit\Framework\TestCase;

/**
 * LegalHoldCaseService against OpenRegister's hold semantics and files_lock.
 */
class LegalHoldCaseServiceTest extends TestCase {
	use LegalHoldDoubles;

	/**
	 * Run an action and return the refusal reason, or '' when it was not refused.
	 *
	 * @param callable $action The action.
	 *
	 * @return string The reason.
	 */
	private function refusal(callable $action): string {
		try {
			$action();
		} catch (LegalHoldRefusedException $refusal) {
			return $refusal->getReason();
		}

		return '';

	}//end refusal()

	/**
	 * Somebody outside the authority groups can do nothing, and nothing is frozen.
	 *
	 * @return void
	 */
	public function testSomebodyWithoutAuthorityIsRefusedEverything(): void {
		$this->record(uuid: 'doc-1', fileId: 11);
		$service = $this->service();

		$this->assertSame('not_allowed', $this->refusal(fn () => $service->place(input: $this->input(documents: ['doc-1']), userId: 'mallory')));
		$this->assertSame('not_allowed', $this->refusal(fn () => $service->list(filters: [], userId: 'mallory')));
		$this->assertSame('not_allowed', $this->refusal(fn () => $service->release(uuid: 'case-1', releaseReason: 'done', userId: 'mallory')));
		$this->assertSame('not_allowed', $this->refusal(fn () => $service->addScope(uuid: 'case-1', input: [], userId: 'mallory')));
		$this->assertSame('not_allowed', $this->refusal(fn () => $service->retry(uuid: 'case-1', userId: 'mallory')));
		$this->assertSame('not_allowed', $this->refusal(fn () => $service->place(input: $this->input(documents: ['doc-1']), userId: '')));

		$this->assertSame([], $this->cases);
		$this->assertSame([], $this->holdService->calls);
		$this->assertSame([], $this->locks);

	}//end testSomebodyWithoutAuthorityIsRefusedEverything()

	/**
	 * A setting that is not a list of group names refuses everyone, admins included.
	 *
	 * @return void
	 */
	public function testABrokenAuthoritySettingRefusesEvenAdmins(): void {
		$this->record(uuid: 'doc-1');
		foreach (['legal', '{"a":"legal"}', '[1]', 'null'] as $setting) {
			$this->authoritySetting = $setting;
			$this->assertSame('config_unreadable', $this->refusal(fn () => $this->service()->place(input: $this->input(documents: ['doc-1']), userId: 'admin')), $setting);
		}

		$this->assertSame([], $this->cases);

	}//end testABrokenAuthoritySettingRefusesEvenAdmins()

	/**
	 * A case needs a name, a known type, a reason and a scope.
	 *
	 * @return void
	 */
	public function testAnIncompleteCaseIsRefused(): void {
		$service = $this->service();
		$input = $this->input(documents: ['doc-1']);

		$this->assertSame('invalid', $this->refusal(fn () => $service->place(input: array_merge($input, ['reason' => '  ']), userId: 'alice')));
		$this->assertSame('invalid', $this->refusal(fn () => $service->place(input: array_merge($input, ['name' => '']), userId: 'alice')));
		$this->assertSame('invalid', $this->refusal(fn () => $service->place(input: array_merge($input, ['holdType' => 'review']), userId: 'alice')));
		$this->assertSame('invalid', $this->refusal(fn () => $service->place(input: array_merge($input, ['scopeDocuments' => []]), userId: 'alice')));
		$this->assertSame([], $this->cases);

	}//end testAnIncompleteCaseIsRefused()

	/**
	 * A release without a reason is refused and the record stays frozen.
	 *
	 * @return void
	 */
	public function testReleaseWithoutAReasonIsBlocked(): void {
		$this->record(uuid: 'doc-1');
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');

		$this->assertSame('invalid', $this->refusal(fn () => $service->release(uuid: $case['uuid'], releaseReason: ' ', userId: 'alice')));
		$this->assertSame('active', $this->cases[$case['uuid']]['status']);
		$this->assertTrue($this->records['doc-1']->hasActiveLegalHold());

	}//end testReleaseWithoutAReasonIsBlocked()

	/**
	 * Released is final: no more scope, no second release.
	 *
	 * @return void
	 */
	public function testAReleasedCaseIsFinal(): void {
		$this->record(uuid: 'doc-1');
		$this->record(uuid: 'doc-2');
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');
		$service->release(uuid: $case['uuid'], releaseReason: 'Settled.', userId: 'alice');

		$this->assertSame('released', $this->refusal(fn () => $service->addScope(uuid: $case['uuid'], input: ['scopeDocuments' => ['doc-2']], userId: 'alice')));
		$this->assertSame('released', $this->refusal(fn () => $service->release(uuid: $case['uuid'], releaseReason: 'Again.', userId: 'alice')));
		$this->assertFalse($this->records['doc-2']->hasActiveLegalHold());
		$this->assertSame('not_found', $this->refusal(fn () => $service->get(uuid: 'no-such-case', userId: 'alice')));

	}//end testAReleasedCaseIsFinal()

	/**
	 * Placing a case holds every record under its reason, locks the files, and tells owners and custodian.
	 *
	 * @return void
	 */
	public function testPlacingFreezesEveryRecordAndLocksItsFiles(): void {
		$this->record(uuid: 'doc-1', owner: 'bob', fileId: 11);
		$this->record(uuid: 'dossier-1', owner: 'dave');

		$case = $this->service()->place(input: $this->input(documents: ['doc-1'], dossiers: ['dossier-1']), userId: 'alice');

		$this->assertSame('active', $case['status']);
		$this->assertSame('complete', $case['protection']);
		$this->assertSame('available', $case['fileLockBackstop']);
		$this->assertSame('filinq-hold-case:' . $case['uuid'], $this->records['doc-1']->getRetention()['legalHold']['reason']);
		$this->assertTrue($this->records['dossier-1']->hasActiveLegalHold());
		$this->assertSame([11 => 'filinq'], $this->locks);
		$entries = array_column($case['fanOut'], null, 'ref');
		$this->assertSame(['held', 'locked', [11]], [$entries['doc-1']['record'], $entries['doc-1']['file'], $entries['doc-1']['fileIds']]);
		$this->assertSame(['held', 'no_files', 'dossier'], [$entries['dossier-1']['record'], $entries['dossier-1']['file'], $entries['dossier-1']['kind']]);

		$this->assertEqualsCanonicalizing(['bob', 'dave', 'carol'], $case['notifiedOwners']);
		$this->assertEqualsCanonicalizing(['bob', 'dave', 'carol'], array_column($this->sent, 'user'));
		$this->assertSame(['legal_hold_placed'], array_values(array_unique(array_column($this->sent, 'subject'))));
		$this->assertSame('Bezwaar 2026-004', $this->sent[0]['name']);

	}//end testPlacingFreezesEveryRecordAndLocksItsFiles()

	/**
	 * Somebody else's hold is recorded, never overwritten, and never lifted.
	 *
	 * @return void
	 */
	public function testAnotherPartysHoldIsLeftAlone(): void {
		$record = $this->record(uuid: 'doc-1');
		$record->setRetention(['legalHold' => ['active' => true, 'reason' => 'Rijksarchief inspectie', 'history' => []]]);
		$service = $this->service();

		$case = $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');
		$this->assertSame('held_by_other', $case['fanOut'][0]['record']);
		$this->assertSame('partial', $case['protection']);

		$released = $service->release(uuid: $case['uuid'], releaseReason: 'Settled.', userId: 'alice');
		$this->assertSame('left_to_other_party', $released['fanOut'][0]['record']);
		$this->assertSame('Rijksarchief inspectie', $record->getRetention()['legalHold']['reason']);
		$this->assertTrue($record->hasActiveLegalHold());
		$this->assertSame([], $this->holdService->calls);

	}//end testAnotherPartysHoldIsLeftAlone()

	/**
	 * A failed placement is on the case, the case says partial, and a retry finishes it.
	 *
	 * @return void
	 */
	public function testAPartialFanOutIsVisibleAndRetried(): void {
		$this->record(uuid: 'doc-1');
		$this->record(uuid: 'doc-2', fileId: 22);
		$this->record(uuid: 'doc-3');
		$this->holdService = new FakeLegalHoldService();
		$this->holdService->failOn = ['doc-2'];
		$this->lockFailsOn = [22];
		$service = $this->service();

		$case = $service->place(input: $this->input(documents: ['doc-1', 'doc-2', 'doc-3', 'doc-gone']), userId: 'alice');
		$entries = array_column($case['fanOut'], null, 'ref');
		$this->assertSame('partial', $case['protection']);
		$this->assertSame(['failed', 'database is locked'], [$entries['doc-2']['record'], $entries['doc-2']['recordError']]);
		$this->assertSame(['failed', 'File is locked'], [$entries['doc-2']['file'], $entries['doc-2']['fileError']]);
		$this->assertSame(['failed', 'The record could not be found.'], [$entries['doc-gone']['record'], $entries['doc-gone']['recordError']]);
		$this->assertSame('held', $entries['doc-1']['record']);

		$this->holdService->failOn = [];
		$this->lockFailsOn = [];
		$this->record(uuid: 'doc-gone');
		$retried = $service->retry(uuid: $case['uuid'], userId: 'alice');

		$this->assertSame('complete', $retried['protection']);
		$this->assertTrue($this->records['doc-2']->hasActiveLegalHold());
		$this->assertSame([22 => 'filinq'], $this->locks);
		$this->assertCount(4, $retried['fanOut']);
		// Each user heard once, on placement; the retry notified nobody again.
		$this->assertEqualsCanonicalizing(['bob', 'carol'], array_column($this->sent, 'user'));

	}//end testAPartialFanOutIsVisibleAndRetried()

	/**
	 * Two cases on one record: it stays frozen, file lock included, until the last one is released, in either order.
	 *
	 * @return void
	 */
	public function testOverlappingCasesKeepTheRecordFrozenUntilTheLastRelease(): void {
		foreach ([['first', 'second'], ['second', 'first']] as $order) {
			$this->cases = [];
			$this->locks = [];
			$this->record(uuid: 'doc-1', fileId: 11);
			$service = $this->service();
			$cases = [
				'first' => $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice'),
				'second' => $service->place(input: array_merge($this->input(documents: ['doc-1']), ['name' => 'Audit 2026']), userId: 'alice'),
			];
			$other = ($order[0] === 'first' ? 'second' : 'first');

			$released = $service->release(uuid: $cases[$order[0]]['uuid'], releaseReason: 'Settled.', userId: 'alice');
			$this->assertSame('kept_for_other_case', $released['fanOut'][0]['record'], implode(',', $order));
			$this->assertSame('kept_for_other_case', $released['fanOut'][0]['file']);
			$this->assertTrue($this->records['doc-1']->hasActiveLegalHold());
			$this->assertSame('filinq-hold-case:' . $cases[$other]['uuid'], $this->records['doc-1']->getRetention()['legalHold']['reason']);
			$this->assertSame([11 => 'filinq'], $this->locks);

			$last = $service->release(uuid: $cases[$other]['uuid'], releaseReason: 'Audit closed.', userId: 'alice');
			$this->assertSame(['released', 'unlocked'], [$last['fanOut'][0]['record'], $last['fanOut'][0]['file']]);
			$this->assertFalse($this->records['doc-1']->hasActiveLegalHold());
			$this->assertSame([], $this->locks);
		}//end foreach

	}//end testOverlappingCasesKeepTheRecordFrozenUntilTheLastRelease()

	/**
	 * Without files_lock the record is still frozen, and the case says the files are not locked.
	 *
	 * @return void
	 */
	public function testAMissingLockProviderDegradesHonestly(): void {
		$this->record(uuid: 'doc-1', fileId: 11);
		$this->lockProvider = false;

		$case = $this->service()->place(input: $this->input(documents: ['doc-1']), userId: 'alice');

		$this->assertTrue($this->records['doc-1']->hasActiveLegalHold());
		$this->assertSame('unavailable', $case['fileLockBackstop']);
		$this->assertSame('unavailable', $case['fanOut'][0]['file']);
		$this->assertSame([], $this->locks);
		$this->assertValidCase(payload: $this->cases[$case['uuid']]);

	}//end testAMissingLockProviderDegradesHonestly()

	/**
	 * Adding a reference freezes that record only; the others are not touched again.
	 *
	 * @return void
	 */
	public function testAddingScopeFreezesOnlyTheAdditions(): void {
		$this->record(uuid: 'doc-1');
		$this->record(uuid: 'doc-2');
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');
		$this->holdService->calls = [];

		$case = $service->addScope(uuid: $case['uuid'], input: ['scopeDocuments' => ['doc-1', 'doc-2']], userId: 'alice');

		$this->assertSame(['place:doc-2:filinq-hold-case:' . $case['uuid']], $this->holdService->calls);
		$this->assertSame(['doc-1', 'doc-2'], $case['scopeDocuments']);
		$this->assertCount(2, $case['fanOut']);
		// Every field survived the save: the reason, the custodian, the reference.
		$this->assertSame('carol', $this->cases[$case['uuid']]['custodian']);
		$this->assertSame('BZW-2026-004', $this->cases[$case['uuid']]['caseReference']);

	}//end testAddingScopeFreezesOnlyTheAdditions()

	/**
	 * After release the record's hold history has the whole entry, and the case is still in the register.
	 *
	 * @return void
	 */
	public function testTheHistorySurvivesRelease(): void {
		$this->record(uuid: 'doc-1', owner: 'bob');
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');
		$this->sent = [];

		$released = $service->release(uuid: $case['uuid'], releaseReason: 'The appeal was withdrawn.', userId: 'alice');

		$history = $this->records['doc-1']->getRetention()['legalHold']['history'];
		$this->assertSame('filinq-hold-case:' . $case['uuid'], $history[0]['reason']);
		$this->assertSame('The appeal was withdrawn.', $history[0]['releaseReason']);
		$this->assertSame(['released', 'alice', 'The appeal was withdrawn.'], [$released['status'], $released['releasedBy'], $released['releaseReason']]);
		$this->assertSame([$case['uuid']], array_column($service->list(filters: ['status' => 'released'], userId: 'alice'), 'uuid'));
		$this->assertEqualsCanonicalizing(['bob', 'carol'], array_column($this->sent, 'user'));
		$this->assertSame(['legal_hold_released'], array_values(array_unique(array_column($this->sent, 'subject'))));

	}//end testTheHistorySurvivesRelease()

	/**
	 * The register filters by status, matter type and custodian.
	 *
	 * @return void
	 */
	public function testTheRegisterFilters(): void {
		$this->record(uuid: 'doc-1');
		$service = $this->service();
		$service->place(input: $this->input(documents: ['doc-1']), userId: 'alice');
		$service->place(input: array_merge($this->input(documents: ['doc-1']), ['holdType' => 'audit', 'custodian' => 'erin']), userId: 'alice');

		$this->assertCount(1, $service->list(filters: ['holdType' => 'woo-appeal', 'custodian' => 'carol'], userId: 'alice'));
		$this->assertCount(1, $service->list(filters: ['custodian' => 'erin'], userId: 'alice'));
		$this->assertCount(2, $service->list(filters: ['status' => 'active', 'unknown' => 'x'], userId: 'alice'));

	}//end testTheRegisterFilters()

	/**
	 * Anyone who can read the record learns it is held; only hold authority learns the matter.
	 *
	 * @return void
	 */
	public function testTheStatusNamesTheMatterOnlyToHoldAuthority(): void {
		$this->record(uuid: 'doc-1');
		$this->record(uuid: 'doc-secret');
		$this->readable = ['doc-1'];
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1', 'doc-secret']), userId: 'alice');

		$this->assertSame(['held' => true, 'cases' => []], $service->statusFor(ref: 'doc-1', userId: 'mallory'));
		$this->assertSame(['held' => true, 'cases' => [['uuid' => $case['uuid'], 'name' => 'Bezwaar 2026-004']]], $service->statusFor(ref: 'doc-1', userId: 'alice'));
		$this->assertSame('not_found', $this->refusal(fn () => $service->statusFor(ref: 'doc-secret', userId: 'mallory')));

	}//end testTheStatusNamesTheMatterOnlyToHoldAuthority()

	/**
	 * Every payload written to the register validates against the real legalHoldCase fragment.
	 *
	 * @return void
	 */
	public function testEveryPayloadWrittenValidates(): void {
		$this->record(uuid: 'doc-1', fileId: 11);
		$this->record(uuid: 'doc-2');
		$this->holdService = new FakeLegalHoldService();
		$this->holdService->failOn = ['doc-2'];
		$service = $this->service();
		$case = $service->place(input: $this->input(documents: ['doc-1', 'doc-2', 'doc-gone'], dossiers: ['doc-1']), userId: 'alice');
		$service->addScope(uuid: $case['uuid'], input: ['scopeDossiers' => ['dossier-9']], userId: 'alice');
		$service->retry(uuid: $case['uuid'], userId: 'alice');
		$service->release(uuid: $case['uuid'], releaseReason: 'Settled.', userId: 'alice');

		$this->assertGreaterThan(4, count($this->written));
		foreach ($this->written as $payload) {
			$this->assertValidCase(payload: $payload);
		}

	}//end testEveryPayloadWrittenValidates()
}//end class

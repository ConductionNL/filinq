<?php

/**
 * The person who anonymises differs from the person who checked (REQ-RWB-09).
 *
 * The admin's choice decides when the rule applies, a run's Woo flag or dossier
 * scope decides whether it is a Woo run, and the guard refuses the write when
 * the checker is the one anonymising, or when nobody says who is.
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
 * @spec openspec/changes/redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Exception\RedactionNotReviewedException;
use OCA\Filinq\Service\AnonymizeOptions;
use OCA\Filinq\Service\Redaction\DetectionRunIdentity;
use OCA\Filinq\Service\Redaction\FourEyesPolicy;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\Filinq\Service\Redaction\RedactionReviewGate;
use OCA\Filinq\Service\Redaction\RedactionReviewMarkRepository;
use OCA\Filinq\Service\SettingsService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the four-eyes rule: FourEyesPolicy, AnonymizeRun::isWooRun(),
 * RedactionReviewGate::refuseFourEyes() and the guard that asks both.
 */
class FourEyesTest extends TestCase {

	/**
	 * What one detection run found.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private const FOUND = [
		['entityType' => 'PERSON', 'text' => 'Fatima El-Amrani', 'start' => 120, 'end' => 136],
	];

	/**
	 * The policy over a stored admin choice.
	 *
	 * @param string $mode The choice.
	 *
	 * @return FourEyesPolicy
	 */
	private function policy(string $mode): FourEyesPolicy {
		return new FourEyesPolicy(settings: $this->createConfiguredMock(SettingsService::class, ['getFourEyesMode' => $mode]));
	}//end policy()

	/**
	 * A guard whose only mark says Fenna checked this run of file 42.
	 *
	 * @param string $mode The admin's choice.
	 *
	 * @return RedactionOutputGuard
	 */
	private function guardCheckedByFenna(string $mode): RedactionOutputGuard {
		$runs = new DetectionRunIdentity();
		$marks = $this->getMockBuilder(RedactionReviewMarkRepository::class)
			->disableOriginalConstructor()
			->onlyMethods(['findFor', 'save'])
			->getMock();
		$marks->method('findFor')->willReturn(
			[
				'document' => '42',
				'detectionRun' => $runs->of(fileId: 42, entities: self::FOUND),
				'checkedBy' => 'fenna',
				'checkedAt' => '2026-10-30T09:00:00+00:00',
			]
		);

		return new RedactionOutputGuard(
			gate: new RedactionReviewGate(),
			marks: $marks,
			runs: $runs,
			fourEyes: $this->policy(mode: $mode)
		);
	}//end guardCheckedByFenna()

	/**
	 * Which runs each choice holds to four eyes.
	 *
	 * @return array<string, array{0: string, 1: bool, 2: bool}>
	 */
	public static function choices(): array {
		return [
			'woo, Woo run'       => ['woo', true, true],
			'woo, other run'     => ['woo', false, false],
			'always, Woo run'    => ['always', true, true],
			'always, other run'  => ['always', false, true],
			'off, Woo run'       => ['off', true, false],
			'off, other run'     => ['off', false, false],
		];
	}//end choices()

	/**
	 * The admin's choice and the run's Woo flag decide whether four eyes apply.
	 *
	 * @param string $mode    The choice.
	 * @param bool   $wooRun  Whether the run is a Woo run.
	 * @param bool   $applies Whether four eyes are required.
	 *
	 * @return void
	 */
	#[DataProvider('choices')]
	public function testTheChoiceDecidesWhichRunsNeedFourEyes(string $mode, bool $wooRun, bool $applies): void {
		$this->assertSame($applies, $this->policy(mode: $mode)->appliesTo(wooRun: $wooRun));
	}//end testTheChoiceDecidesWhichRunsNeedFourEyes()

	/**
	 * A run is a Woo run when its caller says so or when it runs with dossier scope.
	 *
	 * @return void
	 */
	public function testAWooRunIsFlaggedOrDossierScoped(): void {
		$plain = new AnonymizeOptions();

		$this->assertFalse($plain->runFor(fileId: 1, entities: [])->isWooRun());
		$this->assertTrue($plain->withWooCase(wooCase: true)->runFor(fileId: 1, entities: [])->isWooRun());
		$this->assertTrue($plain->withScope(scope: 'dossier', dossierKey: null)->runFor(fileId: 1, entities: [])->isWooRun());
		$this->assertFalse($plain->withScope(scope: 'document', dossierKey: null)->runFor(fileId: 1, entities: [])->isWooRun());
	}//end testAWooRunIsFlaggedOrDossierScoped()

	/**
	 * The gate names the reason and asks for a colleague, or for a name.
	 *
	 * @return void
	 */
	public function testTheGateRefusesTheCheckerAndAnUnknownAnonymiser(): void {
		$gate = new RedactionReviewGate();
		$mark = ['checkedBy' => ' fenna '];

		$same = $gate->refuseFourEyes(mark: $mark, actor: 'fenna');
		$this->assertSame(RedactionReviewGate::SAME_PERSON, $same['reason']);
		$this->assertStringContainsString('a colleague must anonymise it', $same['message']);
		$this->assertSame('A colleague must anonymise this document', $same['instruction']);

		$unknown = $gate->refuseFourEyes(mark: $mark, actor: '  ');
		$this->assertSame(RedactionReviewGate::ANONYMISER_UNKNOWN, $unknown['reason']);
		$this->assertSame('Sign in to anonymise this document', $unknown['instruction']);

		$this->assertNull($gate->refuseFourEyes(mark: $mark, actor: 'daan'));
	}//end testTheGateRefusesTheCheckerAndAnUnknownAnonymiser()

	/**
	 * Under `woo`, Fenna may not anonymise a Woo document she checked herself.
	 *
	 * @return void
	 */
	public function testTheCheckerIsRefusedOnAWooRun(): void {
		try {
			$this->guardCheckedByFenna(mode: 'woo')->assertMayWrite(fileId: 42, entities: self::FOUND, actor: 'fenna', wooRun: true);
			$this->fail('The checker anonymised her own Woo document.');
		} catch (RedactionNotReviewedException $e) {
			$this->assertSame(RedactionReviewGate::SAME_PERSON, $e->getReason());
		}
	}//end testTheCheckerIsRefusedOnAWooRun()

	/**
	 * A colleague may anonymise, and outside a Woo run under `woo` so may the checker.
	 *
	 * @return void
	 */
	public function testAColleagueOrANonWooRunIsWritten(): void {
		$guard = $this->guardCheckedByFenna(mode: 'woo');

		$guard->assertMayWrite(fileId: 42, entities: self::FOUND, actor: 'daan', wooRun: true);
		$guard->assertMayWrite(fileId: 42, entities: self::FOUND, actor: 'fenna', wooRun: false);
		$this->guardCheckedByFenna(mode: 'off')->assertMayWrite(fileId: 42, entities: self::FOUND, actor: 'fenna', wooRun: true);
		$this->addToAssertionCount(3);
	}//end testAColleagueOrANonWooRunIsWritten()

	/**
	 * Under `always` the checker is refused outside a Woo run too, and a run without a name is refused.
	 *
	 * @return void
	 */
	public function testAlwaysRefusesTheCheckerAndANamelessRun(): void {
		$guard = $this->guardCheckedByFenna(mode: 'always');

		$reasons = [];
		foreach (['fenna', ''] as $actor) {
			try {
				$guard->assertMayWrite(fileId: 42, entities: self::FOUND, actor: $actor, wooRun: false);
				$reasons[] = 'written';
			} catch (RedactionNotReviewedException $e) {
				$reasons[] = $e->getReason();
			}
		}

		$this->assertSame([RedactionReviewGate::SAME_PERSON, RedactionReviewGate::ANONYMISER_UNKNOWN], $reasons);
	}//end testAlwaysRefusesTheCheckerAndANamelessRun()

	/**
	 * The screen asks the same question with each document's actor and Woo flag.
	 *
	 * @return void
	 */
	public function testTheScreenAppliesTheRulePerDocument(): void {
		$result = $this->guardCheckedByFenna(mode: 'woo')->screen(
			documents: [
				['fileId' => 42, 'entities' => self::FOUND, 'actor' => 'fenna', 'wooRun' => true],
				['fileId' => 42, 'entities' => self::FOUND, 'actor' => 'daan', 'wooRun' => true],
			]
		);

		$this->assertSame([42], $result['allowed']);
		$this->assertSame(RedactionReviewGate::SAME_PERSON, $result['refused'][0]['reason']);
	}//end testTheScreenAppliesTheRulePerDocument()
}//end class

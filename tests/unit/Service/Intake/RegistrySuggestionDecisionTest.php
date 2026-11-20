<?php

/**
 * A registrar accepts, corrects or dismisses one registry suggestion: an
 * accepted one is written through the party decision record and marked
 * written, a dismissed one is marked dismissed, both name the session user,
 * and every refusal says why.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Intake
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use DateTime;
use DateTimeZone;
use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCA\Filinq\Service\Intake\RegistrySuggestionDecision;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Intake\RegistrySuggestionDecision
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 */
class RegistrySuggestionDecisionTest extends TestCase {

	/**
	 * The documents saved, with their uuid.
	 *
	 * @var array<int, array{0: array<string, mixed>, 1: string|null}>
	 */
	private array $saved = [];

	/**
	 * The party decisions recorded.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $recorded = [];

	/**
	 * The waiting document with one KvK suggestion.
	 *
	 * @return array<string, mixed> The document.
	 */
	private static function document(): array {
		return [
			'uuid' => 'intake-1',
			'sender' => 'info@voorbeeld-bv.nl',
			'status' => 'received',
			'registrySuggestion' => [
				[
					'source' => 'kvk',
					'identifier' => '12345678',
					'fields' => ['name' => 'Voorbeeld B.V.', 'address' => 'Voorbeeldstraat 1, 1234AB Voorbeeldstad', 'kvkNumber' => '12345678'],
					'retrievedAt' => '2026-09-19T10:06:00+00:00',
					'written' => false,
				],
			],
		];

	}//end document()

	/**
	 * The decision over a repository holding the document.
	 *
	 * @param array<string, mixed>|null $document The stored document, or null when there is none.
	 * @param bool                      $mayWrite Whether the gate allows writing.
	 * @param string|null               $user     The session user, or null for none.
	 *
	 * @return RegistrySuggestionDecision The service under test.
	 */
	private function decision(?array $document, bool $mayWrite = true, ?string $user = 'anna'): RegistrySuggestionDecision {
		$repository = $this->createMock(IntakeRepositoryInterface::class);
		$repository->method('findByUuid')->willReturn($document);
		$repository->method('save')->willReturnCallback(
			function (array $saved, ?string $uuid = null): array {
				$this->saved[] = [$saved, $uuid];

				return $saved;
			}
		);

		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayWrite')->willReturn($mayWrite);

		$parties = $this->createMock(PartySuggestionServiceInterface::class);
		$parties->method('recordDecision')->willReturnCallback(
			function (string $sender, string $decision, array $suggested, array $accepted = [], string $intakeDocument = ''): array {
				$this->recorded[] = compact('sender', 'decision', 'suggested', 'accepted', 'intakeDocument');

				return [];
			}
		);

		$session = $this->createMock(IUserSession::class);
		if ($user !== null) {
			$person = $this->createMock(IUser::class);
			$person->method('getUID')->willReturn($user);
			$session->method('getUser')->willReturn($person);
		}

		$clock = $this->createMock(ITimeFactory::class);
		$clock->method('getDateTime')->willReturnCallback(
			static fn (): DateTime => new DateTime('2026-09-19T12:00:00', new DateTimeZone('UTC'))
		);

		return new RegistrySuggestionDecision($repository, $gate, $parties, $session, $clock);

	}//end decision()

	/**
	 * Accepting as offered records an accepted party and marks the suggestion written.
	 *
	 * @return void
	 */
	public function testAcceptingAsOfferedWritesTheParty(): void {
		$saved = $this->decision(document: self::document())->decide(uuid: 'intake-1', index: 0, decision: 'accepted');

		$offered = self::document()['registrySuggestion'][0]['fields'];
		$this->assertSame(
			[['sender' => 'info@voorbeeld-bv.nl', 'decision' => 'accepted', 'suggested' => $offered, 'accepted' => $offered, 'intakeDocument' => 'intake-1']],
			$this->recorded
		);
		$suggestion = $saved['registrySuggestion'][0];
		$this->assertTrue($suggestion['written']);
		$this->assertSame($offered, $suggestion['accepted']);
		$this->assertSame('anna', $suggestion['decidedBy']);
		$this->assertSame('2026-09-19T12:00:00+00:00', $suggestion['decidedAt']);
		$this->assertSame('intake-1', $this->saved[0][1]);

	}//end testAcceptingAsOfferedWritesTheParty()

	/**
	 * A corrected house number is accepted as edited; fields outside name, address and number are ignored.
	 *
	 * @return void
	 */
	public function testACorrectedAddressIsAcceptedAsEdited(): void {
		$saved = $this->decision(document: self::document())->decide(
			uuid: 'intake-1',
			index: 0,
			decision: 'accepted',
			fields: ['address' => ' Voorbeeldstraat 3, 1234AB Voorbeeldstad ', 'bsn' => '111222333', 'name' => '']
		);

		$this->assertSame('edited', $this->recorded[0]['decision']);
		$this->assertSame(
			['name' => 'Voorbeeld B.V.', 'address' => 'Voorbeeldstraat 3, 1234AB Voorbeeldstad', 'kvkNumber' => '12345678'],
			$saved['registrySuggestion'][0]['accepted']
		);

	}//end testACorrectedAddressIsAcceptedAsEdited()

	/**
	 * Dismissing marks the suggestion dismissed and writes no party.
	 *
	 * @return void
	 */
	public function testDismissingWritesNoParty(): void {
		$saved = $this->decision(document: self::document())->decide(uuid: 'intake-1', index: 0, decision: 'dismissed');

		$this->assertSame([], $this->recorded);
		$this->assertTrue($saved['registrySuggestion'][0]['dismissed']);
		$this->assertFalse($saved['registrySuggestion'][0]['written']);
		$this->assertSame('anna', $saved['registrySuggestion'][0]['decidedBy']);

	}//end testDismissingWritesNoParty()

	/**
	 * Every refusal carries its status and saves nothing.
	 *
	 * @return void
	 */
	public function testRefusals(): void {
		$decided = self::document();
		$decided['registrySuggestion'][0]['written'] = true;

		$cases = [
			'unknown decision' => [fn () => $this->decision(document: self::document())->decide(uuid: 'intake-1', index: 0, decision: 'maybe'), 400],
			'no session user' => [fn () => $this->decision(document: self::document(), user: null)->decide(uuid: 'intake-1', index: 0, decision: 'accepted'), 401],
			'no document' => [fn () => $this->decision(document: null)->decide(uuid: 'intake-1', index: 0, decision: 'accepted'), 404],
			'no write right' => [fn () => $this->decision(document: self::document(), mayWrite: false)->decide(uuid: 'intake-1', index: 0, decision: 'accepted'), 403],
			'no such suggestion' => [fn () => $this->decision(document: self::document())->decide(uuid: 'intake-1', index: 3, decision: 'accepted'), 404],
			'already decided' => [fn () => $this->decision(document: $decided)->decide(uuid: 'intake-1', index: 0, decision: 'dismissed'), 409],
		];

		foreach ($cases as $name => [$call, $status]) {
			try {
				$call();
				$this->fail($name . ' was not refused');
			} catch (IntakeRefusedException $e) {
				$this->assertSame($status, $e->getStatus(), $name);
			}
		}

		$this->assertSame([], $this->saved);
		$this->assertSame([], $this->recorded);

	}//end testRefusals()
}//end class

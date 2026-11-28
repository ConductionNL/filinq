<?php

/**
 * Intake Arrival Follow-Up Listener: acknowledges a saved intake document under its rule.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use DateTimeImmutable;
use OCA\Filinq\Service\Intake\AnsweringRegistration;
use OCA\Filinq\Service\IntakeAcknowledgementService;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\ReceivedStampService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs after an intake document is saved (openregister's object created
 * event), never inside receive(): the arrival is already stored and nothing
 * here can refuse or undo it (design D2). The outcome is written to the
 * document's `acknowledgement`.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp
 */
class IntakeArrivalFollowUpListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface           $container       Resolves openregister's schema mapper.
	 * @param IntakeDefaultRuleService     $rules           The rule the document names.
	 * @param IntakeAcknowledgementService $acknowledgement Tells the sender.
	 * @param IntakeRepository             $repository      Writes the outcome.
	 * @param LoggerInterface              $logger          Records a follow-up that failed.
	 * @param ReceivedStampService|null    $stamp           Makes the stamped copy; absent, none is made.
	 * @param AnsweringRegistration|null   $registrations   Finds the inbound registration; absent, the date only.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IntakeDefaultRuleService $rules,
		private readonly IntakeAcknowledgementService $acknowledgement,
		private readonly IntakeRepository $repository,
		private readonly LoggerInterface $logger,
		private readonly ReceivedStampService|null $stamp = null,
		private readonly AnsweringRegistration|null $registrations = null,
	) {
	}//end __construct()

	/**
	 * Acknowledge a created intake document; ignore everything else.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public function handle(Event $event): void {
		if (self::isObjectCreated(event: $event) === false) {
			return;
		}

		try {
			$object = $this->intakeObject(event: $event);
			if ($object !== null) {
				$this->followUp(uuid: (string)$object->getUuid(), document: (array)$object->getObject());
			}
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeArrivalFollowUpListener] the arrival follow-up failed; the document is kept as it arrived',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
		}
	}//end handle()

	/**
	 * Whether the event is an object creation.
	 *
	 * @param Event $event The event.
	 *
	 * @return bool
	 */
	private static function isObjectCreated(Event $event): bool {
		return is_a($event, 'OCA\\OpenRegister\\Event\\ObjectCreatedEvent') === true;
	}//end isObjectCreated()

	/**
	 * The created object when it is an intake document.
	 *
	 * @param Event $event An object creation event.
	 *
	 * @return object|null The object, or null when it is not an intake document.
	 */
	private function intakeObject(Event $event): object|null {
		if (method_exists($event, 'getObject') === false) {
			return null;
		}

		$object = $event->getObject();
		if (is_object($object) === false) {
			return null;
		}

		if ($this->isIntakeDocument(schema: (string)$object->getSchema()) === false) {
			return null;
		}

		return $object;
	}//end intakeObject()

	/**
	 * Acknowledge under the document's rule and write the outcome.
	 *
	 * @param string               $uuid     The document's uuid.
	 * @param array<string, mixed> $document The document as saved.
	 *
	 * @return void
	 */
	private function followUp(string $uuid, array $document): void {
		$rule = $this->rules->findRule(uuid: (string)($document['defaultRule'] ?? ''));
		if ($uuid === '' || $rule === null) {
			return;
		}

		$inbound = $this->registrations?->inboundFor(documentUuid: $uuid);

		$outcome = $this->acknowledgementOutcome(uuid: $uuid, document: $document, rule: $rule, inbound: $inbound)
			+ $this->stampOutcome(document: $document, rule: $rule, number: (string)($inbound['registrationNumber'] ?? ''));
		if ($outcome === []) {
			return;
		}

		$this->repository->save(document: array_merge($document, $outcome), uuid: $uuid);
	}//end followUp()

	/**
	 * The acknowledgement under the rule, as the fields to write.
	 *
	 * @param string                    $uuid     The document's uuid.
	 * @param array<string, mixed>      $document The document as saved.
	 * @param array<string, mixed>      $rule     The rule it names.
	 * @param array<string, mixed>|null $inbound  The inbound registration, or null.
	 *
	 * @return array<string, mixed> `acknowledgement` when there is one, else empty.
	 */
	private function acknowledgementOutcome(string $uuid, array $document, array $rule, array|null $inbound): array {
		try {
			$acknowledgement = $this->acknowledgement->acknowledge(document: ['uuid' => $uuid] + $document, rule: $rule, inbound: $inbound);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeArrivalFollowUpListener] the acknowledgement failed; the document is kept as it arrived',
				context: ['uuid' => $uuid, 'error' => $e->getMessage()]
			);
			return [];
		}

		if ($acknowledgement === null) {
			return [];
		}

		return ['acknowledgement' => $acknowledgement];
	}//end acknowledgementOutcome()

	/**
	 * The stamped copy, as the fields to write.
	 *
	 * @param array<string, mixed> $document The document as saved.
	 * @param array<string, mixed> $rule     The rule it names.
	 * @param string               $number   The inbound registration number, or empty.
	 *
	 * @return array<string, int> `stampedRendition` when a copy was made, else empty.
	 */
	private function stampOutcome(array $document, array $rule, string $number): array {
		$stamped = $this->stampedCopy(document: $document, rule: $rule, number: $number);
		if ($stamped === null) {
			return [];
		}

		return ['stampedRendition' => $stamped];
	}//end stampOutcome()

	/**
	 * Whether the rule asks for a stamped copy and there is a file to stamp.
	 *
	 * @param array<string, mixed> $rule   The rule.
	 * @param mixed                $fileId The document's `file`.
	 *
	 * @return bool
	 */
	private function wantsStamp(array $rule, mixed $fileId): bool {
		return $this->stamp !== null && ($rule['stampReceived'] ?? false) === true && is_int($fileId) === true && $fileId > 0;
	}//end wantsStamp()

	/**
	 * The stamped copy of the arrived PDF, when the rule asks for one
	 * (intake-acknowledge-and-stamp D5). The stamp carries the received date
	 * and the inbound registration number when there is one (D4); the arrived
	 * file is never changed.
	 *
	 * @param array<string, mixed> $document The document as saved.
	 * @param array<string, mixed> $rule     The rule it names.
	 * @param string               $number   The inbound registration number, or empty.
	 *
	 * @return int|null The copy's file id.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-received-stamp-goes-on-a-copy-never-on-what-arrived-req-ias-003
	 */
	private function stampedCopy(array $document, array $rule, string $number): int|null {
		$fileId = $document['file'] ?? null;
		if ($this->wantsStamp(rule: $rule, fileId: $fileId) === false) {
			return null;
		}

		try {
			return $this->stamp->stampFile(fileId: $fileId, date: self::receivedDate(receivedAt: (string)($document['receivedAt'] ?? '')), number: $number);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeArrivalFollowUpListener] the received stamp failed; the document is kept as it arrived',
				context: ['fileId' => $fileId, 'error' => $e->getMessage()]
			);
			return null;
		}
	}//end stampedCopy()

	/**
	 * The received date as the stamp shows it: day-month-year, or today.
	 *
	 * @param string $receivedAt The document's receivedAt.
	 *
	 * @return string
	 */
	private static function receivedDate(string $receivedAt): string {
		try {
			return (new DateTimeImmutable($receivedAt))->format('d-m-Y');
		} catch (Throwable) {
			return (new DateTimeImmutable())->format('d-m-Y');
		}
	}//end receivedDate()

	/**
	 * Whether a schema id or slug is the intake document schema.
	 *
	 * @param string $schema The object's schema.
	 *
	 * @return bool
	 */
	private function isIntakeDocument(string $schema): bool {
		if ($schema === IntakeRepository::SCHEMA) {
			return true;
		}

		if ($schema === '') {
			return false;
		}

		try {
			return $this->container->get('OCA\\OpenRegister\\Db\\SchemaMapper')->find($schema)->getSlug() === IntakeRepository::SCHEMA;
		} catch (Throwable) {
			return false;
		}
	}//end isIntakeDocument()
}//end class

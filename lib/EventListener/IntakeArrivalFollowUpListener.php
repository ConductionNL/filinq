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
 * @spec openspec/changes/intake-acknowledge-and-stamp
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\IntakeAcknowledgementService;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\Filinq\Service\IntakeRepository;
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
 * @spec openspec/changes/intake-acknowledge-and-stamp
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
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IntakeDefaultRuleService $rules,
		private readonly IntakeAcknowledgementService $acknowledgement,
		private readonly IntakeRepository $repository,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Acknowledge a created intake document; ignore everything else.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp/specs/intake-acknowledgement/spec.md#requirement-a-matching-rule-acknowledges-receipt-to-the-sender-req-ias-001
	 */
	public function handle(Event $event): void {
		if (is_a($event, 'OCA\\OpenRegister\\Event\\ObjectCreatedEvent') === false || method_exists($event, 'getObject') === false) {
			return;
		}

		try {
			$object = $event->getObject();
			if (is_object($object) === false || $this->isIntakeDocument(schema: (string)$object->getSchema()) === false) {
				return;
			}

			$this->followUp(uuid: (string)$object->getUuid(), document: (array)$object->getObject());
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[IntakeArrivalFollowUpListener] the arrival follow-up failed; the document is kept as it arrived',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
		}
	}//end handle()

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

		$acknowledgement = $this->acknowledgement->acknowledge(document: ['uuid' => $uuid] + $document, rule: $rule);
		if ($acknowledgement === null) {
			return;
		}

		$this->repository->save(document: array_merge($document, ['acknowledgement' => $acknowledgement]), uuid: $uuid);
	}//end followUp()

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

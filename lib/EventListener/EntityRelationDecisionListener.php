<?php
/**
 * Creates a publication-consent request when an operator decides not to anonymise an entity.
 *
 * @category  Listener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\ConsentCrudService;
use OCA\Filinq\Service\ConsentService;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Subscribes to OpenRegister's `EntityRelationDecisionUpdatedEvent`.
 *
 * WHAT THIS CLOSES. OpenRegister has dispatched this event from
 * `EntityRelationMapper` for some time and this app never subscribed to it —
 * measured 2026-08-25, zero references outside `openspec/` and no commit in the
 * repository's history that ever added one. So every decision to publish an
 * entity unredacted was dropped on the floor, and the consent record that
 * decision is supposed to create was never created.
 *
 * That failure was invisible from outside for a reason worth stating: three of
 * the four scenarios in the spec are NEGATIVE ("no consent record is created"),
 * and they were all satisfied — by nothing happening, rather than by the guards
 * working. THE ABSENCE OF A BUG AND THE ABSENCE OF THE FEATURE LOOK IDENTICAL.
 * See ConductionNL/filinq#805.
 *
 * WHAT IT DELIBERATELY DOES NOT DO. It never throws. Nextcloud dispatches events
 * synchronously inside the request that changed the relation, so an exception
 * here would fail the operator's PATCH — turning a consent-bookkeeping problem
 * into "you cannot save this decision". Every failure is logged and swallowed,
 * which is the same posture `FilinqEventListener` takes.
 *
 * @spec openspec/specs/consent-management/spec.md
 */
class EntityRelationDecisionListener implements IEventListener {

	/**
	 * The OpenRegister event this listener answers to.
	 *
	 * A CONSTANT, NOT A LITERAL AT THE CALL SITE. Passing the name inline to
	 * `is_a()` reads as a type assertion to static analysis, so psalm tries to
	 * resolve a class this app does not depend on and reports UndefinedClass.
	 * Behind a constant the runtime behaviour is identical and the analysis
	 * stays honest: nothing here claims the class is loadable.
	 *
	 * It is also the one place the coupling is written down, so a rename on
	 * OpenRegister's side has a single site to fix rather than three.
	 *
	 * @var string
	 */
	private const OR_EVENT = 'OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent';

	/**
	 * Constructor.
	 *
	 * @param ConsentService     $consentService Creates the consent request.
	 * @param ConsentCrudService $consentCrud    Resolves the configured register/schema.
	 * @param IAppManager        $appManager     Tells us whether OpenRegister is installed.
	 * @param ContainerInterface $container      Resolves OpenRegister classes by name.
	 * @param LoggerInterface    $logger         Records every path that declines to act.
	 */
	public function __construct(
		private readonly ConsentService $consentService,
		private readonly ConsentCrudService $consentCrud,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Handle the event.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 */
	public function handle(Event $event): void {
		// TYPE-CHECKED BY NAME, NOT BY `instanceof`. This app must load and
		// function with OpenRegister absent — an `instanceof` against a class
		// that is not installed is a fatal, not a false.
		if (is_a($event, self::OR_EVENT) === false) {
			return;
		}

		// HELD AS `mixed` ON PURPOSE, and this is not a style choice.
		//
		// `is_a()` with a literal class name is a type assertion to static
		// analysis, so psalm narrows `$event` to a class it cannot load — this
		// app does not depend on OpenRegister — and every subsequent property
		// access becomes an UndefinedClass error. Widening to `mixed` here says
		// what is actually true: the shape is guaranteed by the runtime check
		// above, not by anything psalm can see. It is the same posture
		// `CustomDictionaryDetectionRunner::getOpenRegisterService()` takes,
		// which returns `mixed` for exactly this reason.
		$decision = $this->asDecision(event: $event);

		try {
			// THE WHOLE TRIGGER, AND IT BELONGS TO THE EVENT. The event's own
			// `isSkipAnonymizationActivated()` returns true ONLY for a
			// false -> true transition. That single call is what makes the
			// three negative scenarios correct rather than accidental:
			//
			//   * a bases-only edit leaves `skipAnonymization` out of
			//     changedFields entirely -> false
			//   * a reversal (true -> false) matches the key but not the
			//     direction -> false
			//
			// Reimplementing this check here would be a second copy of a
			// grammar that already exists, and the two would drift.
			if ($decision->isSkipAnonymizationActivated() === false) {
				return;
			}

			$config = $this->consentCrud->getConsentConfig();
			if ($config === null) {
				$this->logger->warning(
					'Publication consent register/schema not configured; the decision to publish '
					. 'an entity unredacted was recorded in OpenRegister but no consent request '
					. 'was created.'
				);
				return;
			}

			$relation = $decision->getRelation();
			$documentId = (string)($relation->getFileId() ?? '');
			if ($documentId === '') {
				$this->logger->warning(
					'EntityRelation {relation} activated skipAnonymization but carries no fileId, so there is no document to attach a consent record to.',
					['relation' => (string)($relation->getId() ?? 'unknown')]
				);
				return;
			}

			$entity = $this->resolveEntity(entityId: $relation->getEntityId());
			if ($entity === null) {
				$this->logger->warning(
					'EntityRelation {relation} activated skipAnonymization but its entity {entity} could not be resolved; no consent request created.',
					[
						'relation' => (string)($relation->getId() ?? 'unknown'),
						'entity' => (string)($relation->getEntityId() ?? 'unknown'),
					]
				);
				return;
			}

			// `entityKey` is what makes `createConsentRequest` idempotent on
			// (documentId, entityKey) — see the requirement of that name in the
			// spec. Without it a second decision on the same entity would
			// create a duplicate record rather than update the first, and the
			// workflow state on the original (notificationStatus, objection
			// dates) would be stranded on a row nothing reads.
			$extra = ['entityKey' => (string)($entity->getUuid() ?? '')];

			$this->consentService->createConsentRequest(
				documentId: $documentId,
				entityType: (string)($entity->getType() ?? ''),
				entityText: (string)($entity->getValue() ?? ''),
				register: $config['register'],
				schema: $config['schema'],
				extra: $extra
			);
		} catch (Throwable $e) {
			// SWALLOWED ON PURPOSE — see the class docblock. The operator's
			// PATCH has already been persisted by OpenRegister at this point;
			// re-throwing would surface a consent-bookkeeping failure as a
			// failure to save their decision.
			$this->logger->error(
				'Failed to create a consent request from an EntityRelation decision: {message}',
				['message' => $e->getMessage(), 'exception' => $e]
			);
		}
	}//end handle()

	/**
	 * Widen a verified event to `mixed` so its OpenRegister accessors are callable.
	 *
	 * THIS EXISTS TO SATISFY TWO TOOLS THAT DISAGREE, and the disagreement is
	 * worth recording rather than suppressing:
	 *
	 *   * psalm narrows `$event` through the `is_a()` check above, then reports
	 *     UndefinedMethod on every accessor, because the class it narrowed to
	 *     belongs to an app this one does not depend on.
	 *   * phpcs forbids the inline `/** @var * /` annotation that would widen it
	 *     back at the call site.
	 *
	 * A method with a real docblock is the one form both accept. The runtime
	 * behaviour is a plain assignment; the shape is guaranteed by the `is_a()`
	 * check in `handle()`, not by anything either tool can see.
	 *
	 * @param Event $event An event already verified to be the OpenRegister one.
	 *
	 * @return mixed The same object, untyped.
	 */
	private function asDecision(Event $event): mixed {
		return $event;
	}//end asDecision()

	/**
	 * Resolve the GDPR entity a relation points at.
	 *
	 * Looked up through the container by class NAME rather than injected, for
	 * the same reason `handle()` avoids `instanceof`: OpenRegister is an
	 * optional peer, and a constructor type-hint on one of its classes would
	 * make this app unbootable without it.
	 *
	 * @param int|null $entityId The relation's entity id.
	 *
	 * @return mixed The entity, or null when it cannot be resolved.
	 */
	private function resolveEntity(?int $entityId): mixed {
		if ($entityId === null) {
			return null;
		}

		if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
			return null;
		}

		try {
			$mapper = $this->container->get('OCA\OpenRegister\Db\GdprEntityMapper');
			return $mapper->find($entityId);
		} catch (Throwable $e) {
			// A LOOKUP FAILURE MUST NOT WEAR THE SAME WORDS AS "NO SUCH ENTITY".
			// Returning null here is correct, but the caller logs it as
			// "could not be resolved" rather than "does not exist", and this
			// line records the actual reason so the two stay distinguishable.
			$this->logger->debug(
				'GdprEntity {entity} lookup failed: {message}',
				['entity' => $entityId, 'message' => $e->getMessage()]
			);
			return null;
		}
	}//end resolveEntity()
}//end class

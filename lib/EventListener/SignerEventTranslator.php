<?php

/**
 * Filinq Signer Event Translator
 *
 * Translates task-sequence transitions — already reduced to scalars by
 * `SigningTaskListener` — into Filinq's own typed signer events, and
 * notifies the configured signing provider when a position becomes enabled.
 * The listener keeps the OR event surface and the ownership filter; this
 * class is pure filinq: scalars in, `Signer*Event`s out. That split is what
 * keeps every OpenRegister type out of this file, so it loads with OR
 * older, newer or absent (openregister#3302, flow-approval-consolidation).
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\SignerChainCompletedEvent;
use OCA\Filinq\Event\SignerStepApprovedEvent;
use OCA\Filinq\Event\SignerStepPendingEvent;
use OCA\Filinq\Event\SignerStepRejectedEvent;
use OCA\Filinq\Service\Signing\SigningProviderFactory;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Re-emits task-sequence transitions as Filinq signer events.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerEventTranslator {
	/**
	 * Constructor.
	 *
	 * @param SigningProviderFactory $providerFactory Provider factory for invoking
	 *                                                the configured provider on
	 *                                                position-enabled transitions.
	 * @param IEventDispatcher $dispatcher Dispatcher used to re-emit
	 *                                     typed filinq-side events.
	 * @param LoggerInterface $logger Logger.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function __construct(
		private readonly SigningProviderFactory $providerFactory,
		private readonly IEventDispatcher $dispatcher,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Handle a sequence position becoming enabled (a signer's turn).
	 *
	 * Re-emits the typed pending event and invokes the configured provider.
	 * Both the first position (sequence provisioned) and every next position
	 * (previous position approved) arrive here: OR enables the next position
	 * in the same request as the approving decision, so no separate
	 * "next step" payload exists.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param string $taskUuid UUID of the now-enabled task.
	 * @param int $position Ordinal of the position (1-based).
	 * @param string|null $role The position's signer group, when one is set.
	 * @param string $objectUuid Signing-request UUID.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function onPositionEnabled(
		string $sequenceUuid,
		string $taskUuid,
		int $position,
		?string $role,
		string $objectUuid,
	): void {
		$this->dispatcher->dispatchTyped(
			new SignerStepPendingEvent(
				sequenceUuid: $sequenceUuid,
				taskUuid: $taskUuid,
				position: $position,
				role: $role,
				objectUuid: $objectUuid
			)
		);

		$this->invokeProviderForEnabledPosition(
			objectUuid: $objectUuid,
			position: $position
		);

	}//end onPositionEnabled()

	/**
	 * Handle a sequence task completing with a decision.
	 *
	 * Approving outcomes re-emit the typed approved event; rejecting
	 * outcomes the typed rejected event. When an approval was not the final
	 * position, the next position's own enabled transition follows through
	 * {@see onPositionEnabled()}; the final approval additionally arrives
	 * through {@see onSequenceCompleted()}.
	 *
	 * @param string $sequenceUuid UUID of the OR task sequence.
	 * @param string $taskUuid UUID of the completed task.
	 * @param int $position Ordinal of the position (1-based).
	 * @param string|null $userId The completing identity.
	 * @param string|null $comment The completion comment, when one was given.
	 * @param string $objectUuid Signing-request UUID.
	 * @param bool $isRejecting TRUE when the outcome is in the rejecting
	 *                          vocabulary.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function onTaskDecided(
		string $sequenceUuid,
		string $taskUuid,
		int $position,
		?string $userId,
		?string $comment,
		string $objectUuid,
		bool $isRejecting,
	): void {
		if ($isRejecting === true) {
			$this->dispatcher->dispatchTyped(
				new SignerStepRejectedEvent(
					sequenceUuid: $sequenceUuid,
					taskUuid: $taskUuid,
					position: $position,
					userId: $userId,
					comment: $comment,
					objectUuid: $objectUuid
				)
			);
			return;
		}

		$this->dispatcher->dispatchTyped(
			new SignerStepApprovedEvent(
				sequenceUuid: $sequenceUuid,
				taskUuid: $taskUuid,
				position: $position,
				userId: $userId,
				comment: $comment,
				objectUuid: $objectUuid
			)
		);

	}//end onTaskDecided()

	/**
	 * Handle a sequence completing (final position approved).
	 *
	 * @param string $sequenceUuid UUID of the completed OR task sequence.
	 * @param string $finalTaskUuid UUID of the final position's task.
	 * @param string|null $userId The identity that decided the final position.
	 * @param string $statusOnApprove The resolved approving status.
	 * @param string $objectUuid Signing-request UUID.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
	 */
	public function onSequenceCompleted(
		string $sequenceUuid,
		string $finalTaskUuid,
		?string $userId,
		string $statusOnApprove,
		string $objectUuid,
	): void {
		$this->dispatcher->dispatchTyped(
			new SignerChainCompletedEvent(
				sequenceUuid: $sequenceUuid,
				finalTaskUuid: $finalTaskUuid,
				userId: $userId,
				statusOnApprove: $statusOnApprove,
				objectUuid: $objectUuid
			)
		);

	}//end onSequenceCompleted()

	/**
	 * Resolve the active provider and ask it to handle an enabled position.
	 *
	 * The `NativeSigningProvider` is a no-op for this call today: it waits
	 * for the filinq UI signer-action endpoint, whose reply path (once the
	 * deferred write-path rewrite lands) is `TaskService::complete()` with
	 * an approving or rejecting outcome. External providers
	 * (`ValidSignProvider` and future plugins) may use this hook to push a
	 * signing-request to the external service or send the signer email.
	 *
	 * @param string $objectUuid Signing-request UUID.
	 * @param int $position Position ordinal (1-based).
	 *
	 * @return void
	 */
	private function invokeProviderForEnabledPosition(string $objectUuid, int $position): void {
		try {
			$provider = $this->providerFactory->getActiveProvider();
			$this->logger->debug(
				'SigningTaskListener: provider ' . $provider->getIdentifier()
				. ' notified that position ' . $position . ' is enabled for sign-request ' . $objectUuid
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'SigningTaskListener: failed to resolve signing provider for ' . $objectUuid . ': ' . $e->getMessage(),
				['exception' => $e]
			);
		}

	}//end invokeProviderForEnabledPosition()
}//end class

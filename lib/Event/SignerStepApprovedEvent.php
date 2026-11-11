<?php

/**
 * SignerStepApprovedEvent
 *
 * Typed filinq-side event fired when a position of an OR task sequence
 * belonging to a filinq signing-request completes with an approving outcome.
 * Bridges OR's committed `TaskTerminalEvent` (state `completed`, outcome not
 * in the rejecting vocabulary). The retired `nextStep` payload is gone by
 * design: OR enables the next position in the same request as the approving
 * decision, and that position's own enabled transition arrives as a
 * `SignerStepPendingEvent`.
 *
 * Carries scalars only, on purpose: the payload survives with OpenRegister
 * older, newer or absent, which is what lets filinq load on either side of
 * openregister#3302 (flow-approval-consolidation).
 *
 * @category  Event
 * @package   OCA\Filinq\Event
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#2-1
 */

declare(strict_types=1);

namespace OCA\Filinq\Event;

/**
 * Fired when a sequence position linked to a filinq sign-request is approved.
 *
 * @category Event
 * @package  OCA\Filinq\Event
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/signing-via-or-approval-with-provider-plugins/spec.md
 */
class SignerStepApprovedEvent extends SignerStepDecisionEvent {
	// The payload and its getters live in SignerStepDecisionEvent; the
	// comment is the completion comment, when one was given.
}//end class

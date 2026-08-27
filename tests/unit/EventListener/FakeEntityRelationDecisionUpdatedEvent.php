<?php
/**
 * A stand-in carrying OpenRegister's event class name.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCP\EventDispatcher\Event;

/**
 * WHY THIS EXISTS RATHER THAN A PHPUNIT MOCK.
 *
 * The listener identifies its event with `is_a($event, 'OCA\OpenRegister\Event\
 * EntityRelationDecisionUpdatedEvent')` — by NAME, because OpenRegister is an
 * optional peer and `instanceof` against a class that is not installed is a
 * fatal error rather than a false.
 *
 * That means the double must genuinely answer to that name. This unit suite
 * runs without OpenRegister on the autoloader, so the class cannot be mocked
 * directly; instead this local fake is aliased onto the real name below, which
 * makes `is_a()` true for exactly the reason it would be true in production.
 *
 * The alias is guarded: where OpenRegister IS loadable (in-container runs), the
 * real class wins and this file changes nothing.
 */
class FakeEntityRelationDecisionUpdatedEvent extends Event {

	/**
	 * Constructor.
	 *
	 * @param bool  $activated What `isSkipAnonymizationActivated()` reports.
	 * @param mixed $relation  The relation carried by the event.
	 */
	public function __construct(
		private readonly bool $activated,
		private readonly mixed $relation,
	) {
		parent::__construct();
	}//end __construct()

	/**
	 * Whether skipAnonymization went false -> true in this change.
	 *
	 * @return bool
	 */
	public function isSkipAnonymizationActivated(): bool {
		return $this->activated;
	}//end isSkipAnonymizationActivated()

	/**
	 * The relation in its post-update state.
	 *
	 * @return mixed
	 */
	public function getRelation(): mixed {
		return $this->relation;
	}//end getRelation()
}//end class

if (class_exists('OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent') === false) {
	class_alias(
		FakeEntityRelationDecisionUpdatedEvent::class,
		'OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent'
	);
}

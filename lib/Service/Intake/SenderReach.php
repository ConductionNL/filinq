<?php

/**
 * Sender Reach: the two ways openregister reaches the sender of an arriving document.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Intake
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

namespace OCA\Filinq\Service\Intake;

use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Reaches a sender through openregister's send unit, which owns the
 * refuse-send indicators and the opt-out gate (intake-acknowledge-and-stamp
 * D3). Classes are named by string: openregister is an optional peer, and
 * without it nothing is reached and the caller says so.
 *
 * @spec openspec/changes/intake-acknowledge-and-stamp
 */
class SenderReach {

	/**
	 * The party notification service: parties linked to an object, by role.
	 */
	private const PARTY_SERVICE = 'OCA\\OpenRegister\\Notifications\\Service\\Party\\PartyNotificationService';

	/**
	 * The message a party notification carries.
	 */
	private const PARTY_MESSAGE = 'OCA\\OpenRegister\\Notifications\\Service\\Party\\PartyMessage';

	/**
	 * The mail sender for an address without an account.
	 */
	private const EMAIL_SENDER = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\EmailSenderInterface';

	/**
	 * An address and the name to address it to.
	 */
	private const MAIL_ADDRESSEE = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\MailAddressee';

	/**
	 * A subject and a body.
	 */
	private const MAIL_CONTENT = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\MailContent';

	/**
	 * The outcome when openregister cannot be reached.
	 */
	public const OUTCOME_UNAVAILABLE = 'openregister-unavailable';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container Resolves openregister's services.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
	) {
	}//end __construct()

	/**
	 * Notify the parties linked to an object in the sender role.
	 *
	 * @param string $objectUuid The intake document.
	 * @param string $subject    The subject line.
	 * @param string $body       The body.
	 *
	 * @return array<int, array<string, mixed>> One outcome per party (party, address, outcome); empty when none is linked.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp
	 */
	public function notifySenderParties(string $objectUuid, string $subject, string $body): array {
		if (class_exists(self::PARTY_MESSAGE) === false) {
			return [];
		}

		$class = self::PARTY_MESSAGE;
		$outcomes = $this->container->get(self::PARTY_SERVICE)->notifyParties(
			new $class($objectUuid, $subject, $body),
			'sender'
		);

		return array_values(array_filter((array)$outcomes, 'is_array'));
	}//end notifySenderParties()

	/**
	 * Mail a bare address.
	 *
	 * @param string $address The address.
	 * @param string $subject The subject line.
	 * @param string $body    The body.
	 *
	 * @return string The send unit's outcome, or OUTCOME_UNAVAILABLE.
	 *
	 * @spec openspec/changes/intake-acknowledge-and-stamp
	 */
	public function mailAddress(string $address, string $subject, string $body): string {
		if (class_exists(self::MAIL_ADDRESSEE) === false || class_exists(self::MAIL_CONTENT) === false) {
			return self::OUTCOME_UNAVAILABLE;
		}

		try {
			$sender = $this->container->get(self::EMAIL_SENDER);
		} catch (Throwable $e) {
			return self::OUTCOME_UNAVAILABLE;
		}

		$addressee = self::MAIL_ADDRESSEE;
		$content = self::MAIL_CONTENT;

		return (string)$sender->sendMailTo(new $addressee($address), new $content($subject, $body));
	}//end mailAddress()
}//end class

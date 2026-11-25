<?php

/**
 * Object Bound Mail
 *
 * Sends one e-mail through OpenRegister's notification subsystem, bound to the
 * OpenRegister object it is about, so OpenRegister records the sent mail as an
 * outbound link on that object and shows it on the object's Emails tab.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\IUserSession;
use OCP\Mail\IMailer;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Mails an address or a Nextcloud user on behalf of an OpenRegister object.
 *
 * OpenRegister's mail sender sends through Nextcloud's mailer and, when the
 * mail names the object it is sent from, writes an `outbound` e-mail link with
 * the minted `Message-ID` on that object. So filinq keeps no state of its own
 * about the mail: the link is the record. OpenRegister is an optional peer, so
 * its classes are named by string. Without them a mail to an address still
 * goes, through Nextcloud's mailer and unlinked; a mail to a user answers
 * {@see OUTCOME_UNAVAILABLE}, as there is no address to send it to.
 *
 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
 */
class ObjectBoundMail {

	/**
	 * OpenRegister's mail sender.
	 */
	private const EMAIL_SENDER = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\EmailSenderInterface';

	/**
	 * An address and the name to address it to.
	 */
	private const MAIL_ADDRESSEE = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\MailAddressee';

	/**
	 * A subject, a body and the object the mail is bound to.
	 */
	private const MAIL_CONTENT = 'OCA\\OpenRegister\\Notifications\\Service\\Notification\\MailContent';

	/**
	 * The object a mail is bound to.
	 */
	private const MAIL_OBJECT_BINDING = 'OCA\\OpenRegister\\Kernel\\Contracts\\MailObjectBinding';

	/**
	 * Resolves a register by id or slug.
	 */
	private const REGISTER_MAPPER = 'OCA\\OpenRegister\\Db\\RegisterMapper';

	/**
	 * Resolves a schema by id or slug.
	 */
	private const SCHEMA_MAPPER = 'OCA\\OpenRegister\\Db\\SchemaMapper';

	/**
	 * The outcome OpenRegister answers for a mail that went.
	 */
	public const OUTCOME_DISPATCHED = 'dispatched';

	/**
	 * The outcome when OpenRegister's mail sender cannot be reached.
	 */
	public const OUTCOME_UNAVAILABLE = 'openregister-unavailable';

	/**
	 * Who a link is recorded for when no user is signed in (a background job).
	 */
	public const SYSTEM_ACTOR = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container   Resolves OpenRegister's services.
	 * @param IUserSession       $userSession The acting user, recorded on the link.
	 * @param IMailer            $mailer      Sends a mail to an address when OpenRegister cannot.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IUserSession $userSession,
		private readonly IMailer $mailer,
	) {
	}//end __construct()

	/**
	 * Mail a bare address, bound to an object.
	 *
	 * @param string             $address     The recipient address.
	 * @param string             $displayName The name to address it to, '' for none.
	 * @param ObjectBoundMessage $message     The subject, the body and the object.
	 *
	 * @return string OpenRegister's outcome (`dispatched`, `failed`, `no-address`, `kill-switch`);
	 *                `dispatched` when Nextcloud's mailer sent it without OpenRegister.
	 *
	 * @throws \Throwable When Nextcloud's mailer refuses the mail sent without OpenRegister.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-2-consent-notification-via-the-email-leaf-drive-notificationstatus-m
	 */
	public function toAddress(string $address, string $displayName, ObjectBoundMessage $message): string {
		$sender = $this->sender();
		if ($sender === null) {
			$mail = $this->mailer->createMessage();
			$mail->setTo([$address]);
			$mail->setSubject($message->subject);
			$mail->setPlainBody($message->body);
			$this->mailer->send($mail);

			return self::OUTCOME_DISPATCHED;
		}

		$addressee = self::MAIL_ADDRESSEE;

		return (string)$sender->sendMailTo(new $addressee($address, $displayName), $this->content(message: $message));
	}//end toAddress()

	/**
	 * Mail a Nextcloud user at the address their account holds, bound to an object.
	 *
	 * @param string             $uid     The recipient's user id.
	 * @param ObjectBoundMessage $message The subject, the body and the object.
	 *
	 * @return string OpenRegister's outcome, or OUTCOME_UNAVAILABLE.
	 *
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public function toUser(string $uid, ObjectBoundMessage $message): string {
		$sender = $this->sender();
		if ($sender === null) {
			return self::OUTCOME_UNAVAILABLE;
		}

		return (string)$sender->sendMail($uid, $this->content(message: $message));
	}//end toUser()

	/**
	 * OpenRegister's mail sender, or null when it or its message classes are missing.
	 *
	 * @return object|null The sender.
	 */
	private function sender(): ?object {
		if (class_exists(self::MAIL_ADDRESSEE) === false || class_exists(self::MAIL_CONTENT) === false) {
			return null;
		}

		try {
			$sender = $this->container->get(self::EMAIL_SENDER);
		} catch (Throwable $e) {
			return null;
		}

		if (is_object($sender) === false) {
			return null;
		}

		return $sender;
	}//end sender()

	/**
	 * The mail content, bound to its object when the object can be named.
	 *
	 * An OpenRegister without the binding, or an object whose register cannot
	 * be resolved, still sends the mail; it is then not linked.
	 *
	 * @param ObjectBoundMessage $message The subject, the body and the object.
	 *
	 * @return object The MailContent.
	 */
	private function content(ObjectBoundMessage $message): object {
		$content = self::MAIL_CONTENT;

		return new $content($message->subject, $message->body, null, $this->binding(object: $message->object));
	}//end content()

	/**
	 * The binding OpenRegister links the sent mail by, or null when it cannot be made.
	 *
	 * @param ObjectBoundMailTarget $object The object.
	 *
	 * @return object|null The MailObjectBinding.
	 */
	private function binding(ObjectBoundMailTarget $object): ?object {
		if (class_exists(self::MAIL_OBJECT_BINDING) === false || $object->uuid === '') {
			return null;
		}

		$registerId = $this->idOf(mapper: self::REGISTER_MAPPER, reference: $object->register);
		if ($registerId === null) {
			return null;
		}

		$binding = self::MAIL_OBJECT_BINDING;

		return new $binding($object->uuid, $registerId, $this->idOf(mapper: self::SCHEMA_MAPPER, reference: $object->schema), $this->actor());
	}//end binding()

	/**
	 * The numeric id of a register or schema named by id or slug.
	 *
	 * @param string $mapper    The mapper's class name.
	 * @param string $reference The id or slug.
	 *
	 * @return integer|null The id, or null when it cannot be resolved.
	 */
	private function idOf(string $mapper, string $reference): ?int {
		$reference = trim($reference);
		if (preg_match('/^[1-9]\d*$/', $reference) === 1) {
			return (int)$reference;
		}

		if ($reference === '') {
			return null;
		}

		try {
			$id = $this->container->get($mapper)->find($reference)->getId();
		} catch (Throwable $e) {
			return null;
		}

		if (is_numeric($id) === false || (int)$id <= 0) {
			return null;
		}

		return (int)$id;
	}//end idOf()

	/**
	 * Who the link is recorded for: the signed-in user, else filinq itself.
	 *
	 * @return string The user id.
	 */
	private function actor(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return self::SYSTEM_ACTOR;
		}

		return $user->getUID();
	}//end actor()
}//end class

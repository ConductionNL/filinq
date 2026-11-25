<?php

/**
 * Stubs of OpenRegister's notification-subsystem mail classes, for unit tests
 * that run without OpenRegister. Each is declared only when OpenRegister's own
 * class is not loaded, and mirrors its constructor.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Notifications\Service\Notification {

	if (interface_exists(EmailSenderInterface::class) === false) {
		interface EmailSenderInterface {
			public const OUTCOME_DISPATCHED = 'dispatched';

			/**
			 * Mail a user.
			 *
			 * @param string $uid     The user.
			 * @param string $subject The subject.
			 * @param string $body    The body.
			 *
			 * @return string The outcome.
			 */
			public function send(string $uid, string $subject, string $body): string;

			/**
			 * Mail a user.
			 *
			 * @param string      $uid     The user.
			 * @param MailContent $content The content.
			 *
			 * @return string The outcome.
			 */
			public function sendMail(string $uid, MailContent $content): string;

			/**
			 * Mail an address.
			 *
			 * @param MailAddressee $addressee The address.
			 * @param MailContent   $content   The content.
			 *
			 * @return string The outcome.
			 */
			public function sendMailTo(MailAddressee $addressee, MailContent $content): string;
		}
	}

	if (class_exists(MailAddressee::class) === false) {
		final class MailAddressee {
			/**
			 * Constructor.
			 *
			 * @param string $address     The address.
			 * @param string $displayName The name.
			 */
			public function __construct(
				public readonly string $address,
				public readonly string $displayName = '',
			) {
			}
		}
	}

	if (class_exists(MailContent::class) === false) {
		final class MailContent {
			/**
			 * Constructor.
			 *
			 * @param string                 $subject     The subject.
			 * @param string                 $body        The body.
			 * @param array|null             $unsubscribe Unsubscribe material.
			 * @param MailObjectBinding|null $object      The object.
			 */
			public function __construct(
				public readonly string $subject,
				public readonly string $body,
				public readonly ?array $unsubscribe = null,
				public readonly ?\OCA\OpenRegister\Kernel\Contracts\MailObjectBinding $object = null,
			) {
			}
		}
	}
}

namespace OCA\OpenRegister\Kernel\Contracts {

	if (class_exists(MailObjectBinding::class) === false) {
		final class MailObjectBinding {
			/**
			 * Constructor.
			 *
			 * @param string       $objectUuid The object.
			 * @param integer      $registerId Its register.
			 * @param integer|null $schemaId   Its schema.
			 * @param string       $linkedBy   Who links it.
			 */
			public function __construct(
				public readonly string $objectUuid,
				public readonly int $registerId,
				public readonly ?int $schemaId,
				public readonly string $linkedBy,
			) {
			}
		}
	}
}

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

			public function send(string $uid, string $subject, string $body): string;

			public function sendMail(string $uid, MailContent $content): string;

			public function sendMailTo(MailAddressee $addressee, MailContent $content): string;
		}
	}

	if (class_exists(MailAddressee::class) === false) {
		final class MailAddressee {
			public function __construct(
				public readonly string $address,
				public readonly string $displayName = '',
			) {
			}
		}
	}

	if (class_exists(MailContent::class) === false) {
		final class MailContent {
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

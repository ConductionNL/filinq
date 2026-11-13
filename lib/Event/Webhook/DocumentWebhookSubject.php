<?php

/**
 * Filinq DocumentWebhookSubject
 *
 * The document and the record a document event is about: a reference, never
 * the content.
 *
 * @category  Event
 * @package   OCA\Filinq\Event\Webhook
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Event\Webhook;

/**
 * Value object naming a document and its record.
 *
 * @category Event
 * @package  OCA\Filinq\Event\Webhook
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
 */
final class DocumentWebhookSubject {

	/**
	 * Construct the subject.
	 *
	 * @param int|string|null $fileId       The document's file id or reference, as the record holds it.
	 * @param string|null     $name         The document's name.
	 * @param string|null     $register     The record's register.
	 * @param string|null     $schema       The record's schema.
	 * @param string|null     $id           The record's id.
	 * @param string|null     $organisation The record's organisation.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/connect-document-webhooks/specs/document-webhooks/spec.md#requirement-the-event-names-the-document-and-never-carries-its-content-req-cdw-002
	 */
	public function __construct(
		public readonly int|string|null $fileId,
		public readonly ?string $name,
		public readonly ?string $register,
		public readonly ?string $schema,
		public readonly ?string $id,
		public readonly ?string $organisation = null,
	) {

	}//end __construct()
}//end class

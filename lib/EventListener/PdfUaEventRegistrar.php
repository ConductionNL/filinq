<?php

/**
 * Filinq PdfUaEventRegistrar
 *
 * Registers the listener that queues a PDF/UA check for a PDF attached to an
 * OpenRegister object, on both a new file and a new version of one.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;

/**
 * The PDF/UA attach listener's registration.
 */
class PdfUaEventRegistrar {

	/**
	 * Register the listener for both file events.
	 *
	 * @param IRegistrationContext $context The registration context.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
	 */
	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(event: NodeCreatedEvent::class, listener: PdfUaAttachListener::class);
		$context->registerEventListener(event: NodeWrittenEvent::class, listener: PdfUaAttachListener::class);

	}//end register()
}//end class

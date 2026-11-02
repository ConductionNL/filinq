<?php

/**
 * Filinq RegisterDocumentIntakeLeafListener.
 *
 * Contributes the `filinq-document-intake` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a case page in
 * dossiq or any other app lists the documents waiting in filinq's intake inbox
 * and lets a clerk assign one to that record. The leaf assigns through filinq's
 * own route and invokes nothing in the host app (ADR-066 decision 2).
 *
 * The JS half lives under the same id in
 * `src/integrations/registerDocumentIntakeLeaf.js`.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;

/**
 * Registers the document intake leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
 */
class RegisterDocumentIntakeLeafListener extends MountedLeafListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-document-intake';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'InboxArrowDown';

	/**
	 * The surfaces, written out so both halves can be compared.
	 *
	 * @var string[]
	 */
	public const SURFACES = [
		'detail-page',
		'single-entity',
	];

	/**
	 * The descriptor this listener contributes, written out in full so the
	 * integration-parity gate can compare it with the JS half.
	 *
	 * @return LeafDescriptor The leaf.
	 *
	 * @spec openspec/changes/document-intake-inbox/tasks.md#task-3.2
	 */
	protected function descriptor(): LeafDescriptor {
		return new LeafDescriptor(
			id: self::LEAF_ID,
			label: $this->l10n->t('Documents waiting to be filed'),
			icon: self::ICON,
			kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
			requiredApp: AppIdentity::APP_ID,
			group: 'documents',
			surfaces: self::SURFACES,
			referenceType: self::LEAF_ID,
			renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
		);
	}//end descriptor()
}//end class

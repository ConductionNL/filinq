<?php

/**
 * Filinq RegisterGenerateDocumentLeafListener.
 *
 * Contributes the `filinq-generate-document` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a handler on an
 * object in another app sees the templates that apply to it and generates a
 * document from one with that object as its data.
 *
 * A render surface: the widget lists the scoped templates and asks Filinq's
 * own `POST /api/documents/generate`. It invokes nothing in the host app.
 *
 * The JS half lives under the same id in
 * `src/integrations/registerGenerateDocumentLeaf.js`.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\OpenRegister\Service\Integration\LeafAvailability;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;
use OCA\OpenRegister\Service\Integration\LeafIdentity;
use OCA\OpenRegister\Service\Integration\LeafRendering;

/**
 * Registers the `filinq-generate-document` render-surface leaf.
 *
 * @spec openspec/changes/template-library-leaf/specs/template-management/spec.md
 */
class RegisterGenerateDocumentLeafListener extends MountedLeafListener {

	/**
	 * The leaf id, shared with the JS half.
	 */
	public const LEAF_ID = 'filinq-generate-document';

	/**
	 * The icon, shared with the JS half.
	 */
	public const ICON = 'FileDocumentEditOutline';

	/**
	 * Where the leaf renders: the widget on a detail page, the tab on one entity.
	 */
	public const SURFACES = [
		'detail-page',
		'single-entity',
	];

	/**
	 * The descriptor of the leaf.
	 *
	 * @return LeafDescriptor
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
	 */
	protected function descriptor(): LeafDescriptor {
		return new LeafDescriptor(
			identity: new LeafIdentity(
				id: self::LEAF_ID,
				label: $this->l10n->t('Generate document'),
				icon: self::ICON,
				group: 'documents',
			),
			kinds: [LeafDescriptor::KIND_RENDER_SURFACE],
			availability: new LeafAvailability(requiredApp: AppIdentity::APP_ID),
			rendering: new LeafRendering(
				surfaces: self::SURFACES,
				referenceType: self::LEAF_ID,
				renderMode: LeafDescriptor::RENDER_MODE_MOUNT,
			),
		);
	}//end descriptor()
}//end class

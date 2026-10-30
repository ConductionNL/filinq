<?php

/**
 * Filinq RegisterMergeToPdfLeafListener.
 *
 * Contributes the `filinq-merge-to-pdf` leaf to OpenRegister's cross-app leaf
 * catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a handler on a case in
 * another app can pick that case's documents, put them in order and get one
 * PDF back in the case folder.
 *
 * A RENDER SURFACE, NOT A BULK ACTION. The shared integration registry offers
 * the surfaces user-dashboard, app-dashboard, detail-page and single-entity,
 * and no bulk-action slot on a host's file list. The leaf therefore carries its
 * own selection and dialog. It invokes nothing in the host app: the result
 * reaches the host as a file beside the first document (ADR-066 decision 2).
 *
 * The JS half lives under the same id in
 * `src/integrations/registerMergeToPdfLeaf.js`.
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
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\OpenRegister\Service\Integration\LeafDescriptor;

/**
 * Registers the merge-to-PDF leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 */
class RegisterMergeToPdfLeafListener extends MountedLeafListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-merge-to-pdf';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FilePdfBox';

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
	 * @spec openspec/specs/document-merge/spec.md
	 */
	protected function descriptor(): LeafDescriptor {
		return new LeafDescriptor(
			id: self::LEAF_ID,
			label: $this->l10n->t('Merge to PDF'),
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

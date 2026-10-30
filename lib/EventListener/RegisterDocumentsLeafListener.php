<?php

/**
 * Filinq RegisterDocumentsLeafListener.
 *
 * Contributes the `filinq-documents` leaf to OpenRegister's cross-app leaf
 * catalogue (`RegisterLeafProvidersEvent`, ADR-066), so the documents Filinq
 * holds for an object are visible on that object wherever it is shown, and not
 * only on Filinq's own pages.
 *
 * WHY THIS EXISTS. Filinq declared no leaf at all. Its flat case-document list
 * has always been reachable at `GET /api/case-documents/files?register=&schema=
 * &id=`, which takes exactly the object context a leaf is handed, and nothing
 * outside Filinq could reach it. Declaring the schemas as leaf HOSTS
 * (`configuration.linkedTypes`, this change's other half) makes other apps'
 * leaves land on Filinq records; this listener is the opposite direction.
 *
 * RENDER-AND-READ ONLY. The descriptor carries no verb and no provider: the
 * leaf reads the list through Filinq's own authenticated route, under the same
 * access control that route already applies. It is a render surface, so it
 * passes `null` where an `IntegrationProvider` would go.
 *
 * BOTH HALVES OR NEITHER. The render half lives in Filinq's own JS under the
 * SAME id (`src/integrations/registerDocumentsLeaf.js`, shipped as the
 * `filinq-leaves` bundle). `SURFACES` below is written out verbatim rather than
 * left to the host's default, because a half that declares its surfaces by
 * omission is how two registrations of one leaf drift apart without any gate
 * noticing.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

/**
 * Contributes the `filinq-documents` render leaf to OpenRegister.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */
class RegisterDocumentsLeafListener extends MountedLeafListener {

	/**
	 * The shared leaf id, equal to the JS `register()` id.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-documents';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FileDocumentMultipleOutline';

	/**
	 * The render surfaces this leaf targets — the SAME set, in the same order,
	 * as `src/integrations/registerDocumentsLeaf.js` declares.
	 *
	 * Both dashboard surfaces are deliberately absent: the leaf renders the
	 * documents of ONE object and has nothing to say without one, so offering
	 * it as a dashboard widget would advertise a surface that can only render
	 * empty.
	 *
	 * @var array<int, string>
	 */
	public const SURFACES = [
		'detail-page',
		'single-entity',
	];

	/**
	 * The catalogue group the leaf is listed under.
	 *
	 * @var string
	 */
	public const GROUP = 'documents';

	/**
	 * The label the host shows.
	 *
	 * @return string The translated label.
	 *
	 * @spec openspec/changes/leaf-integrations/tasks.md#task-2-1
	 */
	protected function label(): string {
		return $this->l10n->t('Documents');
	}//end label()
}//end class

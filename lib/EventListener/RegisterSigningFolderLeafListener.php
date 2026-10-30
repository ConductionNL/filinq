<?php

/**
 * Filinq RegisterSigningFolderLeafListener.
 *
 * Contributes the `filinq-signing-folder` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so dossiq, decidiq
 * and any other app place the same signing folder on a dashboard: everything
 * still waiting for the signer's signature, across every record. The folder is
 * per signer, not per record, so it targets the dashboard surfaces only.
 *
 * The JS half lives under the same id in
 * `src/integrations/registerSigningFolderLeaf.js`.
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
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

/**
 * Registers the signing folder leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
 */
class RegisterSigningFolderLeafListener extends MountedLeafListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-signing-folder';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FileSign';

	/**
	 * The surfaces, written out so both halves can be compared.
	 *
	 * @var string[]
	 */
	public const SURFACES = [
		'user-dashboard',
		'app-dashboard',
	];

	/**
	 * The catalogue group the leaf is listed under.
	 *
	 * @var string
	 */
	public const GROUP = 'signing';

	/**
	 * The label the host shows.
	 *
	 * @return string The translated label.
	 *
	 * @spec openspec/changes/archive/2026-10-09-signing-folder-across-cases/tasks.md#task-1.2
	 */
	protected function label(): string {
		return $this->l10n->t('Waiting for your signature');
	}//end label()
}//end class

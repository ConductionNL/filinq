<?php

/**
 * Filinq RegisterDownloadAllFilesLeafListener.
 *
 * Contributes the `filinq-download-all-files` leaf to OpenRegister's cross-app
 * leaf catalogue (`RegisterLeafProvidersEvent`, ADR-066), so a handler on an
 * object in another app can download every file filinq holds for it as one
 * archive with a manifest, and is warned first when the files exceed the
 * administered ceiling.
 *
 * The JS half lives under the same id in
 * `src/integrations/registerDownloadAllFilesLeaf.js`.
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

/**
 * Registers the download-all-files leaf.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 */
class RegisterDownloadAllFilesLeafListener extends MountedLeafListener {

	/**
	 * The leaf id, shared with the JS half.
	 *
	 * @var string
	 */
	public const LEAF_ID = 'filinq-download-all-files';

	/**
	 * The icon, shared with the JS half.
	 *
	 * @var string
	 */
	public const ICON = 'FolderZipOutline';

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
	 * @spec openspec/specs/document-creatie-sjablonen/spec.md
	 */
	protected function label(): string {
		return $this->l10n->t('Download all files');
	}//end label()
}//end class

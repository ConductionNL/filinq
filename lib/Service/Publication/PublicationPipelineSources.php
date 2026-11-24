<?php

/**
 * Publication Pipeline Sources
 *
 * Where the publication pipeline reads what it does not decide itself: the
 * default publisher from the app settings, and a handed-off document's
 * destruction date from the retention surface.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Service\RetentionSurfaceService;
use OCP\IAppConfig;

/**
 * The app settings and the retention surface, either absent in a narrow test.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-destruction-dates-propagate-to-the-publication-pipeline-with-source-precedence-req-ddare-007
 */
final class PublicationPipelineSources {

	/**
	 * Constructor.
	 *
	 * @param IAppConfig|null              $config    App settings; the default publisher is read from them.
	 * @param RetentionSurfaceService|null $retention Where a handed-off document's destruction date is read from.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly ?IAppConfig $config = null,
		public readonly ?RetentionSurfaceService $retention = null,
	) {
	}//end __construct()
}//end class

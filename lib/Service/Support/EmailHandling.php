<?php

/**
 * Email handling
 *
 * Reading an ingested email and filing it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/email-ingestion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\EmailIngestion\EmailFiling;
use OCA\Filinq\Service\EmailIngestion\EmailMessageReader;

/**
 * Reading an ingested email and filing it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/email-ingestion/spec.md
 */
final class EmailHandling {

	/**
	 * Constructor.
	 *
	 * @param EmailMessageReader $reader Reads the message
	 * @param EmailFiling        $filing Files the message and its attachments
	 *
	 * @return void
	 *
	 * @spec openspec/specs/email-ingestion/spec.md
	 */
	public function __construct(
		public readonly EmailMessageReader $reader,
		public readonly EmailFiling $filing,
	) {

	}//end __construct()
}//end class

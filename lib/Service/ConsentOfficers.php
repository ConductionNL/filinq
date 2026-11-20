<?php

/**
 * Filinq Consent Officers
 *
 * The Nextcloud groups whose members record the outcome of a consent
 * request, beside administrators. One list, so the in-app update route and
 * the agent tool (hermiq-ai-tooling REQ-DDHAT-002) can never disagree on who
 * may.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/hermiq-ai-tooling/tasks.md#2-the-consent-decision-tool-code
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The consent officer groups.
 *
 * @spec openspec/changes/hermiq-ai-tooling/specs/filinq-mcp-surface/spec.md#requirement-an-approval-gated-consent-decision-tool-exists-req-ddhat-002
 */
final class ConsentOfficers {

	/**
	 * The groups whose members are consent officers.
	 */
	public const GROUPS = ['filinq-woo-officers', 'filinq-policy-admins'];
}//end class

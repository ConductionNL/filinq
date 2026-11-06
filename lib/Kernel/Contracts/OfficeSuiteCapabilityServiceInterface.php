<?php

/**
 * Contract of OfficeSuiteCapabilityService
 *
 * The public surface of the OfficeSuiteCapabilityService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

/**
 * Contract of OfficeSuiteCapabilityService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OfficeSuiteCapabilityServiceInterface {
	/**
	 * Probe a WOPI host's DISCOVERY endpoint.
	 *
	 * ADR-087 §3 says "a successful `CheckFileInfo` is the probe". That is not
	 * achievable at capability-resolution time and the ADR was wrong to require it:
	 * `CheckFileInfo` is a PER-FILE call authenticated by a short-lived access token
	 * the host mints when a user opens a specific document. There is no file and no
	 * token when a capability is being resolved.
	 *
	 * WOPI **discovery** is the endpoint that answers "is there a usable WOPI host
	 * here" without a file: it returns XML listing the actions the host supports. A
	 * suite with WOPI switched off does not serve it — measured on ONLYOFFICE
	 * 2026-08-16, `/hosting/discovery` returned 404 until `WOPI_ENABLED=true`, so
	 * discovery separates "installed" from "usable" exactly as intended.
	 *
	 * This method was originally written to validate a `CheckFileInfo` JSON body and
	 * was then pointed at a discovery URL, so it reported every working suite as
	 * absent for "missing the required field BaseFileName" — a probe failing on the
	 * shape of a response it was never given.
	 *
	 * @param string $discoveryUrl Absolute WOPI discovery URL to probe.
	 *
	 * @return array{available:bool, reason:string, suite:string|null} The verdict.
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function probeDiscovery(string $discoveryUrl): array;

	/**
	 * Probe a WOPI host's CheckFileInfo for a specific file.
	 *
	 * Only usable where a real file and access token exist — i.e. inside a session,
	 * not at capability-resolution time. See {@see probeDiscovery()}.
	 *
	 * @param string $checkFileInfoUrl Absolute CheckFileInfo URL to probe.
	 *
	 * @return array{available:bool, reason:string, suite:string|null} The verdict.
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function probe(string $checkFileInfoUrl): array;
}//end interface

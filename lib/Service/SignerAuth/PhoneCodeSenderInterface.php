<?php

/**
 * The text message gateway a phone code is sent through
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SignerAuth
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

namespace OCA\Filinq\Service\SignerAuth;

use RuntimeException;

/**
 * Sends one text message to one phone number.
 *
 * Filinq ships no gateway: an instance binds this interface to the gateway it
 * contracts with. Until one is bound, {@see PhoneCodeProvider} refuses to start.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SignerAuth
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
 */
interface PhoneCodeSenderInterface {

	/**
	 * Send a text message.
	 *
	 * @param string $phoneNumber The number, as recorded for the signer
	 * @param string $message     The text
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the gateway did not accept it.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/document-signing/spec.md#requirement-a-signer-can-be-verified-with-a-code-sent-to-their-phone-req-scr-004
	 */
	public function send(string $phoneNumber, string $message): void;
}//end interface

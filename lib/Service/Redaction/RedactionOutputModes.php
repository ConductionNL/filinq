<?php

/**
 * Every mode filinq writes a redacted copy in, and the guard that none escapes.
 *
 * 🔴 A NEW OUTPUT MODE IS THE WAY THIS VERIFICATION GETS BYPASSED. The verifier
 * runs where somebody wired it. Add PDF/A next quarter for an archival hand-off,
 * wire the conversion, forget the verification, and that mode writes redacted
 * copies nobody checks. Nothing fails. The other modes stay green and the new
 * one is silent, which reads exactly like working.
 *
 * So the modes are a declared list, every mode names whether it is verified,
 * and a test asserts the two agree. Adding a mode without a verification entry
 * fails the suite, which is the only moment anybody would notice.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

/**
 * The output modes, and which of them are verified.
 */
final class RedactionOutputModes {

	/**
	 * The mode written in the document's own format.
	 *
	 * @var string
	 */
	public const NATIVE = 'native';

	/**
	 * Converted to PDF beside the native intermediate.
	 *
	 * @var string
	 */
	public const PDF = 'pdf';

	/**
	 * Converted to PDF, with the native intermediate deleted.
	 *
	 * @var string
	 */
	public const PDF_ONLY = 'pdf-only';

	/**
	 * The archival profile.
	 *
	 * @var string
	 */
	public const PDF_A = 'pdf/a';

	/**
	 * The accessibility profile.
	 *
	 * @var string
	 */
	public const PDF_UA = 'pdf/ua';

	/**
	 * Every mode a redacted copy can leave the building in.
	 *
	 * @var string[]
	 */
	public const ALL = [
		self::NATIVE,
		self::PDF,
		self::PDF_ONLY,
		self::PDF_A,
		self::PDF_UA,
	];

	/**
	 * The modes the verifier is wired into.
	 *
	 * 🔴 THIS IS NOT A COPY OF ALL, AND IT MUST NOT BECOME ONE BY HABIT. It is
	 * a separate statement so that adding a mode to `ALL` and forgetting this
	 * list is a test failure rather than a silent gap. Somebody adding a mode
	 * has to make a deliberate claim that it is verified.
	 *
	 * @var string[]
	 */
	public const VERIFIED = [
		self::NATIVE,
		self::PDF,
		self::PDF_ONLY,
		self::PDF_A,
		self::PDF_UA,
	];

	/**
	 * Modes that write a copy and are not verified.
	 *
	 * @return string[] The gaps, empty when every mode is covered.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public static function unverified(): array {
		return array_values(array_diff(self::ALL, self::VERIFIED));
	}//end unverified()

	/**
	 * Whether this mode is verified before its output may be published.
	 *
	 * An unknown mode answers false rather than true. A mode nobody declared is
	 * a mode nobody verified, and the safe reading of "I have not heard of
	 * this" is not "it is fine".
	 *
	 * @param string $mode The output mode.
	 *
	 * @return bool True when the verifier covers it.
	 */
	public static function isVerified(string $mode): bool {
		return in_array($mode, self::VERIFIED, true);
	}//end isVerified()
}//end class

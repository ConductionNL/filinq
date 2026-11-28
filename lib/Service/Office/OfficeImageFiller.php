<?php

/**
 * Office Image Filler
 *
 * Fills the `${image:key}` placeholders of an office (DOCX) template with the
 * Nextcloud file the generation context names in `images.key`. The file is
 * read through TemplateImageResolver, as the generating user, with the same
 * rules as the Twig `nc_image()` function.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Office
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/tasks.md#task-1.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Office;

use OCA\Filinq\Service\Charts\TemplateImageResolver;
use OCP\IL10N;
use OCP\ITempManager;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

/**
 * Turns `${image:key}` placeholders into images.
 *
 * 🔴 THE ONLY READ IS THE RESOLVER'S, AS THE SIGNED-IN USER. The context names
 * a file id and nothing else that could reach storage; a file the user cannot
 * read becomes the `[image unavailable: …]` marker plus a warning, never a
 * blank and never another user's file.
 *
 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-008
 */
class OfficeImageFiller {

	/**
	 * The placeholder family prefix, as it appears inside `${…}`.
	 */
	public const PREFIX = 'image:';

	/**
	 * Formats PhpWord can place in a DOCX. The resolver also accepts WebP,
	 * which Word documents written by PhpWord cannot carry.
	 */
	private const PLACEABLE_MIMES = ['image/png', 'image/jpeg', 'image/gif'];

	/**
	 * Constructor for OfficeImageFiller.
	 *
	 * @param TemplateImageResolver $resolver    Reads the file as the generating user.
	 * @param ITempManager          $tempManager Gives the temp file PhpWord reads the image from.
	 * @param IL10N|null            $l10n        Translator for the marker text.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateImageResolver $resolver,
		private readonly ITempManager $tempManager,
		private readonly ?IL10N $l10n = null,
	) {

	}//end __construct()

	/**
	 * Fill every `${image:key}` placeholder of the template.
	 *
	 * An `images.key` entry is a file id, or `{fileId, width, height}` with the
	 * declared dimensions (PhpWord units, e.g. `120` or `"3cm"`).
	 *
	 * @param TemplateProcessor $processor The office template being filled.
	 * @param array             $images    The `images` context entry, keyed by placeholder key.
	 *
	 * @return string[] The generation warnings, one per placeholder that became a marker.
	 *
	 * @spec openspec/changes/archive/2026-11-24-office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-008
	 */
	public function fill(TemplateProcessor $processor, array $images): array {
		$warnings = [];
		$index    = 0;
		foreach ($this->imageVariables(processor: $processor) as $variable) {
			$key   = substr($variable, strlen(self::PREFIX));
			$entry = $images[$key] ?? null;

			$fileId = $entry;
			if (is_array($entry) === true) {
				$fileId = ($entry['fileId'] ?? null);
			}

			$result = $this->resolver->resolve(fileId: $fileId);
			$path   = null;
			if ($result['src'] !== null) {
				$path = $this->writeTemp(dataUri: $result['src']);
				if ($path === null) {
					$result = ['src' => null, 'reason' => 'not a raster image', 'parameters' => []];
				}
			}

			if ($path !== null && $this->place(processor: $processor, variable: $variable, path: $path, entry: $entry, index: $index) === true) {
				$index++;
				continue;
			}

			$reason  = $result['reason'] ?? 'the image could not be placed';
			$message = $this->translate(
				text: 'image unavailable: %s',
				parameters: [$this->translate(text: (string)$reason, parameters: $result['parameters'] ?? [])]
			);
			$processor->setValue($variable, '[' . $message . ']');
			$warnings[] = $message;
		}

		return $warnings;

	}//end fill()

	/**
	 * The `${image:…}` variables of the template, in document order.
	 *
	 * @param TemplateProcessor $processor The office template.
	 *
	 * @return string[] The variable names without the macro characters.
	 */
	private function imageVariables(TemplateProcessor $processor): array {
		$variables = [];
		foreach ($processor->getVariables() as $variable) {
			if (str_starts_with($variable, self::PREFIX) === true && strlen($variable) > strlen(self::PREFIX)) {
				$variables[] = $variable;
			}
		}

		return $variables;

	}//end imageVariables()

	/**
	 * Place one image at its placeholder.
	 *
	 * PhpWord reads `${name:a:b}` as inline size arguments, so `${image:logo}`
	 * would be read as width "logo". The placeholder is first renamed to a
	 * colon-free one and the image set on that.
	 *
	 * @param TemplateProcessor $processor The office template.
	 * @param string            $variable  The `image:key` variable.
	 * @param string            $path      The temp file holding the image.
	 * @param mixed             $entry     The context entry, for the declared dimensions.
	 * @param int               $index     A per-document counter for the renamed placeholder.
	 *
	 * @return bool Whether the image was placed.
	 */
	private function place(TemplateProcessor $processor, string $variable, string $path, mixed $entry, int $index): bool {
		$replace = ['path' => $path];
		if (is_array($entry) === true) {
			foreach (['width', 'height'] as $dimension) {
				$value = $entry[$dimension] ?? null;
				if (is_int($value) === true || (is_string($value) === true && $value !== '')) {
					$replace[$dimension] = $value;
				}
			}
		}

		$placeholder = 'filinqImage' . $index;
		try {
			$processor->setValue($variable, '${' . $placeholder . '}');
			$processor->setImageValue($placeholder, $replace);
		} catch (Throwable) {
			$processor->setValue($placeholder, '${' . $variable . '}');
			return false;
		}

		return true;

	}//end place()

	/**
	 * Write a resolved data URI to a temp file PhpWord can read.
	 *
	 * @param string $dataUri The `data:<mime>;base64,…` URI from the resolver.
	 *
	 * @return string|null The temp path, or null when the format cannot be placed.
	 */
	private function writeTemp(string $dataUri): ?string {
		if (preg_match('#^data:([a-z/]+);base64,(.*)$#s', $dataUri, $matches) !== 1) {
			return null;
		}

		if (in_array($matches[1], self::PLACEABLE_MIMES, true) === false) {
			return null;
		}

		$bytes = base64_decode($matches[2], true);
		$path  = $this->tempManager->getTemporaryFile();
		if ($bytes === false || is_string($path) === false || file_put_contents($path, $bytes) === false) {
			return null;
		}

		return $path;

	}//end writeTemp()

	/**
	 * Translate an English source string with `%s` parameters.
	 *
	 * @param string $text       The source string.
	 * @param array  $parameters The `%s` values.
	 *
	 * @return string The translated text.
	 */
	private function translate(string $text, array $parameters=[]): string {
		if ($this->l10n !== null) {
			return $this->l10n->t($text, $parameters);
		}

		if ($parameters === []) {
			return $text;
		}

		return vsprintf($text, $parameters);

	}//end translate()
}//end class

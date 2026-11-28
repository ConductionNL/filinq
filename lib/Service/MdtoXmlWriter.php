<?php

/**
 * MDTO XML Writer
 *
 * The MDTO element primitives the sidecar is written with.
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
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DOMDocument;
use DOMElement;

/**
 * Elements in the MDTO namespace, and the begrip, verwijzing and
 * identificatie shapes exactly as OpenRegister's MdtoDocumentWriter writes
 * them: a begrip is label, optional code and begrippenlijst; a verwijzing is
 * a name with an identificatie only when both kenmerk and bron are known.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */
final class MdtoXmlWriter {

	/**
	 * OpenRegister's MDTO namespace constant, reused when OpenRegister is loaded.
	 */
	private const OR_NAMESPACE = 'OCA\\OpenRegister\\Service\\TmloService::MDTO_NAMESPACE';

	/**
	 * The MDTO namespace, for when OpenRegister is not loaded.
	 */
	private const NAMESPACE_FALLBACK = 'https://www.nationaalarchief.nl/mdto';

	/**
	 * The namespace prefix OpenRegister writes.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public const PREFIX = 'mdto';

	/**
	 * The MDTO namespace: OpenRegister's constant when loaded.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public static function mdtoNamespace(): string {
		if (defined(self::OR_NAMESPACE) === true) {
			return (string) constant(self::OR_NAMESPACE);
		}

		return self::NAMESPACE_FALLBACK;

	}//end mdtoNamespace()

	/**
	 * The `mdto:MDTO` root of a document, with its schema location.
	 *
	 * @param DOMDocument $dom            The document.
	 * @param string      $schemaLocation The MDTO-XML schema.
	 *
	 * @return DOMElement
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function root(DOMDocument $dom, string $schemaLocation): DOMElement {
		$root = $dom->createElementNS(self::mdtoNamespace(), self::PREFIX . ':MDTO');
		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', self::mdtoNamespace() . ' ' . $schemaLocation);
		$dom->appendChild($root);

		return $root;

	}//end root()

	/**
	 * A child element in the MDTO namespace.
	 *
	 * @param DOMElement $parent The parent.
	 * @param string     $name   The local name.
	 *
	 * @return DOMElement
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function element(DOMElement $parent, string $name): DOMElement {
		$element = $parent->ownerDocument->createElementNS(self::mdtoNamespace(), self::PREFIX . ':' . $name);
		$parent->appendChild($element);

		return $element;

	}//end element()

	/**
	 * A child element holding text.
	 *
	 * @param DOMElement $parent  The parent.
	 * @param string     $name    The local name.
	 * @param string     $content The text.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function text(DOMElement $parent, string $name, string $content): void {
		$this->element(parent: $parent, name: $name)->textContent = $content;

	}//end text()

	/**
	 * A text element, only when there is text.
	 *
	 * @param DOMElement  $parent  The parent.
	 * @param string      $name    The local name.
	 * @param string|null $content The text or null.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function optionalText(DOMElement $parent, string $name, ?string $content): void {
		if ($content !== null) {
			$this->text(parent: $parent, name: $name, content: $content);
		}

	}//end optionalText()

	/**
	 * A begrip: label, optional code, and the list it comes from.
	 *
	 * @param DOMElement $parent The parent.
	 * @param string     $name   The local name.
	 * @param string     $label  The label.
	 * @param string     $list   The begrippenlijst.
	 * @param string     $code   The code, '' for none.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function begrip(DOMElement $parent, string $name, string $label, string $list, string $code=''): void {
		$element = $this->element(parent: $parent, name: $name);
		$this->text(parent: $element, name: 'begripLabel', content: $label);
		if ($code !== '') {
			$this->text(parent: $element, name: 'begripCode', content: $code);
		}

		$this->text(parent: $this->element(parent: $element, name: 'begripBegrippenlijst'), name: 'verwijzingNaam', content: $list);

	}//end begrip()

	/**
	 * A verwijzing: a name and, when both are known, an identificatie.
	 *
	 * @param DOMElement $parent  The parent.
	 * @param string     $name    The local name.
	 * @param string     $naam    The name referred to.
	 * @param string     $kenmerk The identifier, '' for none.
	 * @param string     $bron    The identifier's source, '' for none.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function verwijzing(DOMElement $parent, string $name, string $naam, string $kenmerk, string $bron): void {
		$element = $this->element(parent: $parent, name: $name);
		$this->text(parent: $element, name: 'verwijzingNaam', content: $naam);
		if ($kenmerk !== '' && $bron !== '') {
			$this->identificatie(parent: $element, name: 'verwijzingIdentificatie', kenmerk: $kenmerk, bron: $bron);
		}

	}//end verwijzing()

	/**
	 * An identificatie: kenmerk and bron.
	 *
	 * @param DOMElement $parent  The parent.
	 * @param string     $name    The local name.
	 * @param string     $kenmerk The identifier.
	 * @param string     $bron    Its source.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function identificatie(DOMElement $parent, string $name, string $kenmerk, string $bron): void {
		$element = $this->element(parent: $parent, name: $name);
		$this->text(parent: $element, name: 'identificatieKenmerk', content: $kenmerk);
		$this->text(parent: $element, name: 'identificatieBron', content: $bron);

	}//end identificatie()
}//end class

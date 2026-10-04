<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Pdfa3MetadataAssembler.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Service\Pdfa3MetadataAssembler
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-c23e683b2cb6181827f35457ed4ca935e6827955415e69b43c48ca13dd40d9cf',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Service/Pdfa3MetadataAssembler.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Service',
    'name' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
    'shortName' => 'Pdfa3MetadataAssembler',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Serialises MDTO/archival metadata into a PDF/A-3 document\'s XMP packet
 * and embedded-attachment set.
 *
 * @category  Service
 * @package   OCA\\Filinq\\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 52,
    'endLine' => 340,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'APP_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'name' => 'APP_ID',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq\'',
          'attributes' => 
          array (
            'startLine' => 57,
            'endLine' => 57,
            'startTokenPos' => 55,
            'startFilePos' => 1745,
            'endTokenPos' => 55,
            'endFilePos' => 1752,
          ),
        ),
        'docComment' => '/**
 * App identifier used for IAppConfig reads.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 57,
        'endLine' => 57,
        'startColumn' => 2,
        'endColumn' => 33,
      ),
      'CFG_MAX_ATTACHMENT_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'name' => 'CFG_MAX_ATTACHMENT_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq.pdfa3.max_attachment_bytes\'',
          'attributes' => 
          array (
            'startLine' => 62,
            'endLine' => 62,
            'startTokenPos' => 68,
            'startFilePos' => 1884,
            'endTokenPos' => 68,
            'endFilePos' => 1918,
          ),
        ),
        'docComment' => '/**
 * App config key: maximum size of a single embedded attachment, in bytes.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 62,
        'endLine' => 62,
        'startColumn' => 2,
        'endColumn' => 78,
      ),
      'DEFAULT_MAX_ATTACHMENT_BYTES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'name' => 'DEFAULT_MAX_ATTACHMENT_BYTES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '20971520',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 81,
            'startFilePos' => 2018,
            'endTokenPos' => 81,
            'endFilePos' => 2025,
          ),
        ),
        'docComment' => '/**
 * Default cap: 20 MiB per attachment.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 2,
        'endColumn' => 55,
      ),
      'STANDARD_METADATA_KEYS' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'name' => 'STANDARD_METADATA_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'title\', \'author\', \'creator\', \'subject\', \'keywords\']',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 82,
            'startTokenPos' => 94,
            'startFilePos' => 2303,
            'endTokenPos' => 111,
            'endFilePos' => 2369,
          ),
        ),
        'docComment' => '/**
 * Metadata keys handled by dedicated mPDF setters; every other key
 * in the caller-supplied metadata array is folded into the MDTO
 * XMP sidecar block instead of being silently dropped.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 82,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'appConfig' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'name' => 'appConfig',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IAppConfig',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 3,
        'endColumn' => 40,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'appConfig' => 
          array (
            'name' => 'appConfig',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IAppConfig',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 90,
            'endLine' => 90,
            'startColumn' => 3,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Constructor.
 *
 * @param IAppConfig $appConfig Tenant configuration provider.
 */',
        'startLine' => 89,
        'endLine' => 93,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'applyMetadata' => 
      array (
        'name' => 'applyMetadata',
        'parameters' => 
        array (
          'mpdf' => 
          array (
            'name' => 'mpdf',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Mpdf\\Mpdf',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 32,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 44,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'defaultTitle' => 
          array (
            'name' => 'defaultTitle',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 61,
            'endColumn' => 80,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Apply title/author/subject/keywords via mPDF\'s dedicated setters
 * and fold every other metadata key into an MDTO XMP sidecar block
 * via SetAdditionalXmpRdf() so archival fields (identifier,
 * caseReference, archiefvormer, aggregatieniveau, ...) are
 * genuinely part of the PDF/A-3\'s XMP packet, not just crammed
 * into /Keywords.
 *
 * @param Mpdf $mpdf Target document.
 * @param array<string,mixed> $metadata Caller-supplied metadata.
 * @param string $defaultTitle Fallback title.
 *
 * @return void
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 111,
        'endLine' => 139,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'buildAssociatedFiles' => 
      array (
        'name' => 'buildAssociatedFiles',
        'parameters' => 
        array (
          'attachments' => 
          array (
            'name' => 'attachments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 39,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 156,
            'endLine' => 156,
            'startColumn' => 59,
            'endColumn' => 73,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate and normalise caller-supplied attachments, and append an
 * auto-generated MDTO metadata sidecar (XML) when metadata was
 * given and the caller did not already supply one — this is the
 * concrete realisation of PDF/A-3\'s embedded-attachment feature.
 *
 * @param array<int,array<string,mixed>> $attachments Caller-supplied attachments.
 * @param array<string,mixed> $metadata MDTO/archival metadata.
 *
 * @return array<int,array<string,mixed>> mPDF-shaped associated-file records.
 *
 * @throws Pdfa3ConversionException REASON_ATTACHMENT_TOO_LARGE.
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */',
        'startLine' => 156,
        'endLine' => 205,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'buildMdtoXmpRdf' => 
      array (
        'name' => 'buildMdtoXmpRdf',
        'parameters' => 
        array (
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 227,
            'endLine' => 227,
            'startColumn' => 35,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Serialise every non-standard metadata key into a custom XMP RDF
 * description block under the `docudesk` namespace. Values are
 * HTML/XML-escaped; keys are sanitised to valid XML local names.
 *
 * ⚠️ THE XMP NAMESPACE URI AND PREFIX STAY `docudesk` ACROSS THE FILINQ
 * RENAME. An XML namespace URI is an IDENTIFIER, not an address: it is
 * baked into the XMP packet of every PDF/A-3 this app has already produced,
 * and an MDTO/archival consumer matches archival fields BY that URI.
 * Changing it does not rename the vocabulary — it declares a DIFFERENT one,
 * so files produced before and after the rename stop being readable by the
 * same rule, and an e-depot keyed on the old URI silently sees no archival
 * metadata at all on new deliveries. Nothing in this repo reads the packet
 * back, so no test would catch it. Moving the vocabulary is a versioned
 * MDTO-profile decision, not part of a rebrand.
 *
 * @param array<string,mixed> $metadata Caller-supplied metadata.
 *
 * @return string RDF/XML fragment, or \'\' when no archival fields were given.
 */',
        'startLine' => 227,
        'endLine' => 254,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'buildMetadataSidecarXml' => 
      array (
        'name' => 'buildMetadataSidecarXml',
        'parameters' => 
        array (
          'metadata' => 
          array (
            'name' => 'metadata',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 43,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Serialise the MDTO/archival metadata array into a small XML
 * document for embedding as a PDF/A-3 attachment (the "source/XML
 * alongside" pattern the A-3 conformance level exists for).
 *
 * @param array<string,mixed> $metadata Caller-supplied metadata.
 *
 * @return string UTF-8 XML document.
 */',
        'startLine' => 265,
        'endLine' => 299,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'sanitiseXmlLocalName' => 
      array (
        'name' => 'sanitiseXmlLocalName',
        'parameters' => 
        array (
          'name' => 
          array (
            'name' => 'name',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 309,
            'endLine' => 309,
            'startColumn' => 40,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Sanitise a metadata key into a valid XML local name (letters,
 * digits, underscore, hyphen; must not start with a digit).
 *
 * @param string $name Raw metadata key.
 *
 * @return string Valid XML local name, or \'\' when nothing usable remains.
 */',
        'startLine' => 309,
        'endLine' => 320,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
      'resolveMaxAttachmentBytes' => 
      array (
        'name' => 'resolveMaxAttachmentBytes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Read the max-attachment-bytes tenant config. Defaults to 20 MiB.
 *
 * @return int Positive byte cap.
 */',
        'startLine' => 327,
        'endLine' => 339,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'OCA\\Filinq\\Service',
        'declaringClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'implementingClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'currentClassName' => 'OCA\\Filinq\\Service\\Pdfa3MetadataAssembler',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));
<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Mcp/FilinqScannableServices.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Mcp\FilinqScannableServices
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-7a14379ee4afa9a690583845a94f41387a14a4b33405684276f8809adcb51f57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Mcp\\FilinqScannableServices',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Mcp/FilinqScannableServices.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Mcp',
    'name' => 'OCA\\Filinq\\Mcp\\FilinqScannableServices',
    'shortName' => 'FilinqScannableServices',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Lists Filinq\'s `#[McpTool]`-attributed service classes.
 *
 * @category Mcp
 * @package  OCA\\Filinq\\Mcp
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-filinq-exposes-one-curated-document-generation-tool
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-signing-is-never-agent-writable
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 58,
    'endLine' => 78,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCA\\OpenRegister\\Mcp\\IMcpScannableServices',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'getScannableServiceClasses' => 
      array (
        'name' => 'getScannableServiceClasses',
        'parameters' => 
        array (
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
 * The service classes OpenRegister may reflect for `#[McpTool]` methods.
 *
 * `CorrespondenceService` carries `generateCorrespondence`;
 * `DocumentAgentService` carries `readDocument`, `editDocument` and
 * `convertDocumentToPdf`.
 *
 * @return list<class-string> The scannable classes.
 *
 * @spec openspec/specs/filinq-mcp-surface/spec.md#requirement-filinq-exposes-one-curated-document-generation-tool
 */',
        'startLine' => 71,
        'endLine' => 77,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\Mcp',
        'declaringClassName' => 'OCA\\Filinq\\Mcp\\FilinqScannableServices',
        'implementingClassName' => 'OCA\\Filinq\\Mcp\\FilinqScannableServices',
        'currentClassName' => 'OCA\\Filinq\\Mcp\\FilinqScannableServices',
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
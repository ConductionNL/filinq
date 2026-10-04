<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/RegisterDocumentsLeafListener.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\EventListener\RegisterDocumentsLeafListener
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-3dc9c1767eddb5d350f452aa2d99307e77356910166eb9cd4ac18b476f6c4e31',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/EventListener/RegisterDocumentsLeafListener.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\EventListener',
    'name' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
    'shortName' => 'RegisterDocumentsLeafListener',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Contributes the `filinq-documents` render leaf to OpenRegister.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 65,
    'endLine' => 143,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'OCP\\EventDispatcher\\IEventListener',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'LEAF_ID' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'name' => 'LEAF_ID',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'filinq-documents\'',
          'attributes' => 
          array (
            'startLine' => 72,
            'endLine' => 72,
            'startTokenPos' => 79,
            'startFilePos' => 2683,
            'endTokenPos' => 79,
            'endFilePos' => 2700,
          ),
        ),
        'docComment' => '/**
 * The shared leaf id, equal to the JS `register()` id.
 *
 * @var string
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 72,
        'endLine' => 72,
        'startColumn' => 2,
        'endColumn' => 43,
      ),
      'SURFACES' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'name' => 'SURFACES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'detail-page\', \'single-entity\']',
          'attributes' => 
          array (
            'startLine' => 85,
            'endLine' => 88,
            'startTokenPos' => 92,
            'startFilePos' => 3152,
            'endTokenPos' => 100,
            'endFilePos' => 3191,
          ),
        ),
        'docComment' => '/**
 * The render surfaces this leaf targets — the SAME set, in the same order,
 * as `src/integrations/registerDocumentsLeaf.js` declares.
 *
 * Both dashboard surfaces are deliberately absent: the leaf renders the
 * documents of ONE object and has nothing to say without one, so offering
 * it as a dashboard widget would advertise a surface that can only render
 * empty.
 *
 * @var array<int, string>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 85,
        'endLine' => 88,
        'startColumn' => 2,
        'endColumn' => 3,
      ),
    ),
    'immediateProperties' => 
    array (
      'l10n' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'name' => 'l10n',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'OCP\\IL10N',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 97,
        'endLine' => 97,
        'startColumn' => 3,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'logger' => 
      array (
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'name' => 'logger',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Psr\\Log\\LoggerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 98,
        'endLine' => 98,
        'startColumn' => 3,
        'endColumn' => 42,
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
          'l10n' => 
          array (
            'name' => 'l10n',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\IL10N',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 3,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'logger' => 
          array (
            'name' => 'logger',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Psr\\Log\\LoggerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 98,
            'endLine' => 98,
            'startColumn' => 3,
            'endColumn' => 42,
            'parameterIndex' => 1,
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
 * @param IL10N           $l10n   Localisation for the human-readable label.
 * @param LoggerInterface $logger PSR-3 logger; a throwing listener costs its own leaf only.
 */',
        'startLine' => 96,
        'endLine' => 100,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'event' => 
          array (
            'name' => 'event',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'OCP\\EventDispatcher\\Event',
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
            'startColumn' => 25,
            'endColumn' => 36,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Contribute the `filinq-documents` leaf descriptor.
 *
 * @param Event $event The dispatched event.
 *
 * @return void
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#task-2-1
 */',
        'startLine' => 111,
        'endLine' => 142,
        'startColumn' => 2,
        'endColumn' => 2,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'OCA\\Filinq\\EventListener',
        'declaringClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'implementingClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
        'currentClassName' => 'OCA\\Filinq\\EventListener\\RegisterDocumentsLeafListener',
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
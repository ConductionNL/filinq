<?php declare(strict_types = 1);

// osfsl-/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../setasign/fpdi/src/FpdfTplTrait.php-PHPStan\BetterReflection\Reflection\ReflectionClass-setasign\Fpdi\FpdfTplTrait
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-9b323088286e7efa41eae10c727ab1422e90bc651f924712d0ce73cba4df75ed-8.3-6.70.0.6',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'setasign\\Fpdi\\FpdfTplTrait',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/vendor/composer/../setasign/fpdi/src/FpdfTplTrait.php',
      ),
    ),
    'namespace' => 'setasign\\Fpdi',
    'name' => 'setasign\\Fpdi\\FpdfTplTrait',
    'shortName' => 'FpdfTplTrait',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Trait FpdfTplTrait
 *
 * This trait adds a templating feature to FPDF and tFPDF.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 473,
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
    ),
    'immediateProperties' => 
    array (
      'templates' => 
      array (
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'name' => 'templates',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 25,
            'startFilePos' => 477,
            'endTokenPos' => 26,
            'endFilePos' => 478,
          ),
        ),
        'docComment' => '/**
 * Data of all created templates.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'currentTemplateId' => 
      array (
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'name' => 'currentTemplateId',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The template id for the currently created template.
 *
 * @var null|int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'templateId' => 
      array (
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'name' => 'templateId',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 44,
            'startFilePos' => 722,
            'endTokenPos' => 44,
            'endFilePos' => 722,
          ),
        ),
        'docComment' => '/**
 * A counter for template ids.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 30,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'setPageFormat' => 
      array (
        'name' => 'setPageFormat',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 35,
            'endColumn' => 39,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'orientation' => 
          array (
            'name' => 'orientation',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 48,
            'endLine' => 48,
            'startColumn' => 42,
            'endColumn' => 53,
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
 * Set the page format of the current page.
 *
 * @param array $size An array with two values defining the size.
 * @param string $orientation "L" for landscape, "P" for portrait.
 * @throws \\BadMethodCallException
 */',
        'startLine' => 48,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'useTemplate' => 
      array (
        'name' => 'useTemplate',
        'parameters' => 
        array (
          'tpl' => 
          array (
            'name' => 'tpl',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 33,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'x' => 
          array (
            'name' => 'x',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 377,
                'startFilePos' => 3071,
                'endTokenPos' => 377,
                'endFilePos' => 3071,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 39,
            'endColumn' => 44,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'y' => 
          array (
            'name' => 'y',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 384,
                'startFilePos' => 3079,
                'endTokenPos' => 384,
                'endFilePos' => 3079,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 47,
            'endColumn' => 52,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'width' => 
          array (
            'name' => 'width',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 391,
                'startFilePos' => 3091,
                'endTokenPos' => 391,
                'endFilePos' => 3094,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 55,
            'endColumn' => 67,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'height' => 
          array (
            'name' => 'height',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 398,
                'startFilePos' => 3107,
                'endTokenPos' => 398,
                'endFilePos' => 3110,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 70,
            'endColumn' => 83,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'adjustPageSize' => 
          array (
            'name' => 'adjustPageSize',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 102,
                'endLine' => 102,
                'startTokenPos' => 405,
                'startFilePos' => 3131,
                'endTokenPos' => 405,
                'endFilePos' => 3135,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 102,
            'endLine' => 102,
            'startColumn' => 86,
            'endColumn' => 108,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Draws a template onto the page or another template.
 *
 * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
 * aspect ratio.
 *
 * @param mixed $tpl The template id
 * @param array|float|int $x The abscissa of upper-left corner. Alternatively you could use an assoc array
 *                           with the keys "x", "y", "width", "height", "adjustPageSize".
 * @param float|int $y The ordinate of upper-left corner.
 * @param float|int|null $width The width.
 * @param float|int|null $height The height.
 * @param bool $adjustPageSize
 * @return array The size
 * @see FpdfTplTrait::getTemplateSize()
 */',
        'startLine' => 102,
        'endLine' => 139,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'getTemplateSize' => 
      array (
        'name' => 'getTemplateSize',
        'parameters' => 
        array (
          'tpl' => 
          array (
            'name' => 'tpl',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 37,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'width' => 
          array (
            'name' => 'width',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 152,
                'endLine' => 152,
                'startTokenPos' => 668,
                'startFilePos' => 4861,
                'endTokenPos' => 668,
                'endFilePos' => 4864,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 43,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'height' => 
          array (
            'name' => 'height',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 152,
                'endLine' => 152,
                'startTokenPos' => 675,
                'startFilePos' => 4877,
                'endTokenPos' => 675,
                'endFilePos' => 4880,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 152,
            'endLine' => 152,
            'startColumn' => 58,
            'endColumn' => 71,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the size of a template.
 *
 * Give only one of the size parameters (width, height) to calculate the other one automatically in view to the
 * aspect ratio.
 *
 * @param mixed $tpl The template id
 * @param float|int|null $width The width.
 * @param float|int|null $height The height.
 * @return array|bool An array with following keys: width, height, 0 (=width), 1 (=height), orientation (L or P)
 */',
        'startLine' => 152,
        'endLine' => 180,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'beginTemplate' => 
      array (
        'name' => 'beginTemplate',
        'parameters' => 
        array (
          'width' => 
          array (
            'name' => 'width',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 945,
                'startFilePos' => 6254,
                'endTokenPos' => 945,
                'endFilePos' => 6257,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'height' => 
          array (
            'name' => 'height',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 952,
                'startFilePos' => 6270,
                'endTokenPos' => 952,
                'endFilePos' => 6273,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 50,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'groupXObject' => 
          array (
            'name' => 'groupXObject',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 959,
                'startFilePos' => 6292,
                'endTokenPos' => 959,
                'endFilePos' => 6296,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 66,
            'endColumn' => 86,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Begins a new template.
 *
 * @param float|int|null $width The width of the template. If null, the current page width is used.
 * @param float|int|null $height The height of the template. If null, the current page height is used.
 * @param bool $groupXObject Define the form XObject as a group XObject to support transparency (if used).
 * @return int A template identifier.
 */',
        'startLine' => 190,
        'endLine' => 264,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'endTemplate' => 
      array (
        'name' => 'endTemplate',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ends a template.
 *
 * @return bool|int|null A template identifier.
 */',
        'startLine' => 271,
        'endLine' => 313,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'getNextTemplateId' => 
      array (
        'name' => 'getNextTemplateId',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the next template id.
 *
 * @return int
 */',
        'startLine' => 320,
        'endLine' => 323,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'AddPage' => 
      array (
        'name' => 'AddPage',
        'parameters' => 
        array (
          'orientation' => 
          array (
            'name' => 'orientation',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 330,
                'endLine' => 330,
                'startTokenPos' => 1928,
                'startFilePos' => 10468,
                'endTokenPos' => 1928,
                'endFilePos' => 10469,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 29,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'size' => 
          array (
            'name' => 'size',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 330,
                'endLine' => 330,
                'startTokenPos' => 1935,
                'startFilePos' => 10480,
                'endTokenPos' => 1935,
                'endFilePos' => 10481,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 48,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'rotation' => 
          array (
            'name' => 'rotation',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 330,
                'endLine' => 330,
                'startTokenPos' => 1942,
                'startFilePos' => 10496,
                'endTokenPos' => 1942,
                'endFilePos' => 10496,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 330,
            'endLine' => 330,
            'startColumn' => 60,
            'endColumn' => 72,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 330,
        'endLine' => 336,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'Link' => 
      array (
        'name' => 'Link',
        'parameters' => 
        array (
          'x' => 
          array (
            'name' => 'x',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 26,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'y' => 
          array (
            'name' => 'y',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 30,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'w' => 
          array (
            'name' => 'w',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 34,
            'endColumn' => 35,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'h' => 
          array (
            'name' => 'h',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 38,
            'endColumn' => 39,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'link' => 
          array (
            'name' => 'link',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 341,
            'endLine' => 341,
            'startColumn' => 42,
            'endColumn' => 46,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 341,
        'endLine' => 347,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetLink' => 
      array (
        'name' => 'SetLink',
        'parameters' => 
        array (
          'link' => 
          array (
            'name' => 'link',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 29,
            'endColumn' => 33,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'y' => 
          array (
            'name' => 'y',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 352,
                'endLine' => 352,
                'startTokenPos' => 2077,
                'startFilePos' => 11101,
                'endTokenPos' => 2077,
                'endFilePos' => 11101,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 36,
            'endColumn' => 41,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'page' => 
          array (
            'name' => 'page',
            'default' => 
            array (
              'code' => '-1',
              'attributes' => 
              array (
                'startLine' => 352,
                'endLine' => 352,
                'startTokenPos' => 2084,
                'startFilePos' => 11112,
                'endTokenPos' => 2085,
                'endFilePos' => 11113,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 352,
            'endLine' => 352,
            'startColumn' => 44,
            'endColumn' => 53,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 352,
        'endLine' => 358,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetDrawColor' => 
      array (
        'name' => 'SetDrawColor',
        'parameters' => 
        array (
          'r' => 
          array (
            'name' => 'r',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 34,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'g' => 
          array (
            'name' => 'g',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 2149,
                'startFilePos' => 11413,
                'endTokenPos' => 2149,
                'endFilePos' => 11416,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 38,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'b' => 
          array (
            'name' => 'b',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 363,
                'endLine' => 363,
                'startTokenPos' => 2156,
                'startFilePos' => 11424,
                'endTokenPos' => 2156,
                'endFilePos' => 11427,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 363,
            'endLine' => 363,
            'startColumn' => 49,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 363,
        'endLine' => 369,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetFillColor' => 
      array (
        'name' => 'SetFillColor',
        'parameters' => 
        array (
          'r' => 
          array (
            'name' => 'r',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 34,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'g' => 
          array (
            'name' => 'g',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 374,
                'endLine' => 374,
                'startTokenPos' => 2228,
                'startFilePos' => 11685,
                'endTokenPos' => 2228,
                'endFilePos' => 11688,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 38,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'b' => 
          array (
            'name' => 'b',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 374,
                'endLine' => 374,
                'startTokenPos' => 2235,
                'startFilePos' => 11696,
                'endTokenPos' => 2235,
                'endFilePos' => 11699,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 374,
            'endLine' => 374,
            'startColumn' => 49,
            'endColumn' => 57,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 374,
        'endLine' => 380,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetLineWidth' => 
      array (
        'name' => 'SetLineWidth',
        'parameters' => 
        array (
          'width' => 
          array (
            'name' => 'width',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 385,
            'endLine' => 385,
            'startColumn' => 34,
            'endColumn' => 39,
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
 * @inheritdoc
 */',
        'startLine' => 385,
        'endLine' => 391,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetFont' => 
      array (
        'name' => 'SetFont',
        'parameters' => 
        array (
          'family' => 
          array (
            'name' => 'family',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 29,
            'endColumn' => 35,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'style' => 
          array (
            'name' => 'style',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 396,
                'endLine' => 396,
                'startTokenPos' => 2376,
                'startFilePos' => 12232,
                'endTokenPos' => 2376,
                'endFilePos' => 12233,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 38,
            'endColumn' => 48,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'size' => 
          array (
            'name' => 'size',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 396,
                'endLine' => 396,
                'startTokenPos' => 2383,
                'startFilePos' => 12244,
                'endTokenPos' => 2383,
                'endFilePos' => 12244,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 396,
            'endLine' => 396,
            'startColumn' => 51,
            'endColumn' => 59,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 396,
        'endLine' => 402,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      'SetFontSize' => 
      array (
        'name' => 'SetFontSize',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 407,
            'endLine' => 407,
            'startColumn' => 33,
            'endColumn' => 37,
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
 * @inheritdoc
 */',
        'startLine' => 407,
        'endLine' => 413,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      '_putimages' => 
      array (
        'name' => '_putimages',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 415,
        'endLine' => 448,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      '_putxobjectdict' => 
      array (
        'name' => '_putxobjectdict',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @inheritdoc
 */',
        'startLine' => 453,
        'endLine' => 460,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'aliasName' => NULL,
      ),
      '_out' => 
      array (
        'name' => '_out',
        'parameters' => 
        array (
          's' => 
          array (
            'name' => 's',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 465,
            'endLine' => 465,
            'startColumn' => 26,
            'endColumn' => 27,
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
 * @inheritdoc
 */',
        'startLine' => 465,
        'endLine' => 472,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'setasign\\Fpdi',
        'declaringClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'implementingClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
        'currentClassName' => 'setasign\\Fpdi\\FpdfTplTrait',
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
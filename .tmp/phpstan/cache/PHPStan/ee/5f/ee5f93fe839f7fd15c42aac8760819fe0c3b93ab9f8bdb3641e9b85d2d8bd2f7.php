<?php declare(strict_types = 1);

// odsl-/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/MergeJobStoreUnreadableException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-OCA\Filinq\Exception\MergeJobStoreUnreadableException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.6-8.3-5c6e206bb9147c4641aa70eb422504e41ea46be7dd8ba894efd67c66dc4079e8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'OCA\\Filinq\\Exception\\MergeJobStoreUnreadableException',
        'filename' => '/home/rubenlinde/memcap-work/lq-lanes/fq/lib/Exception/MergeJobStoreUnreadableException.php',
      ),
    ),
    'namespace' => 'OCA\\Filinq\\Exception',
    'name' => 'OCA\\Filinq\\Exception\\MergeJobStoreUnreadableException',
    'shortName' => 'MergeJobStoreUnreadableException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * A read of the merge job store that failed, rather than one that found nothing.
 *
 * 🔴 THIS EXCEPTION EXISTS BECAUSE THE TWO ANSWERS LOOKED THE SAME. The store
 * swallowed a read failure into `null`, and `null` means "there is no merge
 * with that id". So a register that was down, or a schema that did not
 * resolve, reached the polling caller as a 404: the person watching a queued
 * merge was told their merge did not exist, while it sat in the queue. The
 * client then stops polling, because a merge that is gone is not coming back.
 *
 * 🔑 IT IS NOT THROWN FOR A GENUINE ABSENCE. An id nobody ever queued still
 * reads as `null`, and still answers 404, because that is a true answer.
 *
 * The queue read raises for the same reason from the other side: an empty
 * queue and an unreadable queue both made MergeDocumentsJob report a clean
 * run over a queue it never saw.
 *
 * @category Exception
 * @package  OCA\\Filinq\\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/merge-documents-to-pdf/specs/document-merge/spec.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 50,
    'endLine' => 52,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
    ),
    'immediateMethods' => 
    array (
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
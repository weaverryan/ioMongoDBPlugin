<?php

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * Plugin configuration class for the ioMongoDBPlugin
 *
 * @package ioMongoDBPlugin
 * @subpackage config
 * @author Ryan Weaver <ryan.weaver@iostudio.com>
 */
class ioMongoDBPluginConfiguration extends sfPluginConfiguration
{
  /**
   * @var Doctrine\ODM\MongoDB\DocumentManager
   */
  protected $documentManager;

  /**
   * Initializes the plugin
   */
  public function initialize()
  {
    // extend the actions class
    $actionObject = new ioMongoDBActions($this);
    $this->dispatcher->connect('component.method_not_found', array($actionObject, 'listenComponentMethodNotFound'));
  }

  /**
   * @return Doctrine\ODM\MongoDB\DocumentManager
   */
  public function getDocumentManager()
  {
    if ($this->documentManager === null)
    {
      $this->setupDoctrineODM();
    }

    return $this->documentManager;
  }

  /**
   * Sets up the Doctrine MongoDB ODM and makes the document manager available
   *
   * @return void
   */
  protected function setupDoctrineODM()
  {
    $mongoPath = sfConfig::get('mongodb_odm_dir', sfConfig::get('sf_root_dir').'/lib/vendor/mongodb_odm');

    if (!file_exists($mongoPath))
    {
      throw new sfException(sprintf(
        'Doctrine\'s MongoDB ODM not found at "%s". Place the library at '.
        'that location or configure the location via sfConfig::set(\'mongodb_odm_dir\');',
        $mongoPath
      ));
    }

    require_once $mongoPath.'/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

    // ODM Classes
    $classLoader = new ClassLoader('Doctrine\ODM', $mongoPath.'/lib');
    $classLoader->register();

    // Common Classes
    $classLoader = new ClassLoader('Doctrine\Common', $mongoPath.'/lib/vendor/doctrine-common/lib');
    $classLoader->register();

    $config = new Configuration();
    // @TODO this will likely need to be changed
    $config->setProxyDir(sfConfig::get('sf_lib_dir').'/mongo');
    $config->setProxyNamespace('Proxies');

    // setup the annotation reader
    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

    // throw an event to allow for the config to be modified
    $this->dispatcher->notify(new sfEvent($config, 'io_mongo_db.configure_odm'));

    $this->documentManager = DocumentManager::create(new Mongo(), $config);
  }
}
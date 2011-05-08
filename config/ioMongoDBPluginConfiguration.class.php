<?php

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\MongoDB\Connection,
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
    $mongoODMPath = sfConfig::get('mongodb_odm_dir', sfConfig::get('sf_root_dir').'/lib/vendor/mongodb_odm');
    if (!file_exists($mongoODMPath))
    {
      throw new sfException(sprintf(
        'Doctrine\'s MongoDB not found at "%s". Place the library at '.
        'that location or configure the location via sfConfig::set(\'mongodb_odm_dir\');',
        $mongoODMPath
      ));
    }

    $mongoPath = sfConfig::get('mongodb_dir', sfConfig::get('mongodb_odm_dir').'/lib/vendor/doctrine-mongodb');
    if (!file_exists($mongoPath))
    {
      throw new sfException(sprintf(
        'Doctrine\'s MongoDB ODM not found at "%s". Place the library at '.
        'that location or configure the location via sfConfig::set(\'mongodb_dir\');',
        $mongoPath
      ));
    }

    $doctrineCommonPath = sfConfig::get('doctrine_common_dir', $mongoODMPath.'/lib/vendor/doctrine-common');

    // setup all the class loader stuff
    $this->setupODMClassLoader($mongoPath, $mongoODMPath, $doctrineCommonPath);

    $config = new Configuration();

    $proxyDir = sfConfig::get('sf_cache_dir').'/mongo/proxies';
    if (!file_exists($proxyDir))
    {
      mkdir($proxyDir, 0777, true);
    }
    $config->setProxyDir($proxyDir);
    $config->setProxyNamespace('Proxies');

    $hydratorDir = sfConfig::get('sf_cache_dir').'/mongo/hydrators';
    if (!file_exists($hydratorDir))
    {
      mkdir($hydratorDir, 0777, true);
    }
    $config->setHydratorDir($hydratorDir);
    $config->setHydratorNamespace('Hydrators');

    // setup the annotation reader
    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

    // set the database that should be used for the collection
    $config->setDefaultDB(sfConfig::get('app_io_mongo_db_default_database', 'doctrine'));

    // throw an event to allow for the config to be modified
    $this->dispatcher->notify(new sfEvent($config, 'io_mongo_db.configure_odm'));

    $this->documentManager = DocumentManager::create(new Connection('mongodb://localhost'), $config);
  }

  /**
   * Configures the doctrine odm class loader
   *
   * @param  string $mongoPath The full path to the doctrine odm
   * @return void
   */
  protected function setupODMClassLoader($mongoPath, $mongoODMPath, $doctrineCommonPath)
  {
    $classLoader = $doctrineCommonPath.'/lib/Doctrine/Common/ClassLoader.php';
    if (!file_exists($classLoader))
    {
      throw new InvalidArgumentException(sprintf('Cannot find Doctrine Class Loader at "%s"', $classLoader));
    }

    require_once $classLoader;

    // MongoDB Classes
    $classLoader = new ClassLoader('Doctrine\\MongoDB', $mongoPath.'/lib');
    $classLoader->register();

    // ODM Classes
    $classLoader = new ClassLoader('Doctrine\\ODM\\MongoDB', $mongoODMPath.'/lib');
    $classLoader->register();

    // Common Classes
    $classLoader = new ClassLoader('Doctrine\\Common', $doctrineCommonPath.'/lib');
    $classLoader->register();
  }
}
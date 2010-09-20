<?php

/**
 * This class acts as an extension of sfComponent
 * 
 * @package     ioMongoDBPlugin
 * @subpackage  action
 * @author      Ryan Weaver <ryan@thatsquality.com>
 * @since       2010-09-17
 */
class ioMongoDBActions
{
  /**
   * The action instance that triggered the component.method_not_found event
   */
  protected $action;

  /**
   * @var ioMongoDBPluginConfiguration
   */
  protected $configuration;

  /**
   * Class constructor
   *
   * @param sfThemeController The theme controller instance
   */
  public function __construct(ioMongoDBPluginConfiguration $configuration)
  {
    $this->configuration = $configuration;
  }

  /**
   * Listens to the component.method_not_found event to effectively
   * extend the actions class
   */
  public function listenComponentMethodNotFound(sfEvent $event)
  {
    $this->action = $event->getSubject();
    $method = $event['method'];
    $arguments = $event['arguments'];

    if (method_exists($this, $method))
    {
      $result = call_user_func_array(array($this, $method), $arguments);

      $event->setReturnValue($result);

      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * @return Doctrine\ODM\MongoDB\DocumentManager
   */
  public function getDocumentManager()
  {
    return $this->configuration->getDocumentManager();
  }
}
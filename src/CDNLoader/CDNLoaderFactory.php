<?php
/**
 * Class CDNLoaderFactory
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\DI\Container;

class CDNLoaderFactory
{
  /** @var \Nette\DI\Container */
  private $container;


  /**
   * @param Container $container
   */
  public function __construct(Container $container)
  {
    $this->container = $container;
  }


  /**
   * @return CDNLoader
   */
  public function create()
  {
    $compiler = $this->container->getService('cdnloader.compiler');
    $httpRequest = $this->container->getService('httpRequest');
    $loader = new CDNLoader($compiler, $httpRequest);

    return $loader;
  }

}

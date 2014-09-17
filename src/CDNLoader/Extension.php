<?php
/**
 * Class Extension
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\DI\CompilerExtension;
use Nette\DI\Compiler;
use Nette\Configurator;

class Extension extends CompilerExtension
{

  const DEFAULT_OUTPUT_DIR = 'cdn';
  const EXTENSION_NAME = 'cdnloader';


  public function loadConfiguration()
  {
    $builder = $this->getContainerBuilder();

    $defaults = array(
      'outputDir' => self::DEFAULT_OUTPUT_DIR,
      'libraries' => array(),
    );
    $config = $this->getConfig($defaults);

    $builder->addDefinition($this->prefix('compiler'))
      ->setClass('CDNLoader\Compiler')
      ->addSetup('setOutputDir', array($config['outputDir']))
      ->addSetup('setLibraries', array($config['libraries']));

    $builder->addDefinition($this->prefix('factory'))
      ->setClass('CDNLoader\CDNLoaderFactory');
  }


  public function install(Configurator $configurator)
  {
    $self = $this;
    $configurator->onCompile[] = function($configurator, Compiler $compiler) use ($self) {
      $compiler->addExtension($self::EXTENSION_NAME, $self);
    };
  }

}

<?php
/**
 * Class CDNLoader
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\Utils\Html;

class CDNLoader extends \Nette\Application\UI\Control
{
  /** @var \CDNLoader\Compiler */
  private $compiler;


  /**
   * @param Compiler $compiler
   */
  public function __construct(Compiler $compiler)
  {
    parent::__construct();

    $this->compiler = $compiler;
  }


  /**
   * @param string $source
   * @return mixed
   * @throws InvalidArgumentException
   */
  public function getElement($source)
  {
    if (!is_string($source)) {
      $msg = sprintf('Source must be string. Given "%s".', gettype($source));
      throw new InvalidArgumentException($msg);
    }

    if ($source === '') {
      throw new InvalidArgumentException('Source cannot be empty.');
    }

    $type = substr(strrchr($source, '.'), 1);

    switch ($type) {
      case 'js':
        $element = Html::el('script')
          ->type('text/javascript')
          ->src($source);
        break;
      case 'css':
        $element = Html::el('link')
          ->rel('stylesheet')
          ->type('text/css')
          ->href($source);
        break;
      default:
        $msg = sprintf('Type "%s" of source is unsupported.', $type);
        throw new InvalidArgumentException($msg);
    }

    return $element;
  }


  public function render()
  {
    $this->compiler->generate();

    $files = $this->compiler->getFiles();
    foreach ($files as $file) {
      echo $this->getElement($file), PHP_EOL;
    }
  }

}

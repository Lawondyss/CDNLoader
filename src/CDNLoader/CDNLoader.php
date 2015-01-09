<?php
/**
 * Class CDNLoader
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\Utils\Html;
use Nette\Http\IRequest;

class CDNLoader extends \Nette\Application\UI\Control
{
  /** @var \CDNLoader\Compiler */
  private $compiler;

  /** @var \Nette\Http\IRequest */
  private $httpRequest;


  /**
   * @param Compiler $compiler
   * @param IRequest $httpRequest
   */
  public function __construct(Compiler $compiler, IRequest $httpRequest)
  {
    parent::__construct();

    $this->compiler = $compiler;
    $this->httpRequest = $httpRequest;
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
    $source = $this->getBasePath() . $source;

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


  public function getBasePath()
  {
    $baseUrl = $this->httpRequest->getUrl()->basePath;
    return $baseUrl;
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

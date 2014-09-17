<?php
/**
 * Class Compiler
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\Utils\Finder;

class Compiler
{
  /** @var string */
  private $cdnUrl = 'https://cdnjs.cloudflare.com/ajax/libs/{{NAME}}/{{VERSION}}/{{FILE}}';

  /** @var string */
  private $outputDir;

  /** @var string */
  private $filename = 'cdn-libraries';

  /** @var string */
  private $hashFile = 'hash';

  /** @var array */
  private $libraries = array();

  /** @var array */
  private $files = array();


  /**
   * @param string $path
   * @param bool $autoMake
   * @return $this
   * @throws \CDNLoader\DirectoryNotCreateException
   * @throws \CDNLoader\InvalidArgumentException
   */
  public function setOutputDir($path, $autoMake = true)
  {
    if (!is_string($path)) {
      $msg = sprintf('Output directory must be type of string, "%s" given.', gettype($path));
      throw new InvalidArgumentException($msg);
    }
    
    if ($path === '') {
      throw new InvalidArgumentException('Name of output directory cannot be empty.');
    }

    $outputDir = File::normalizePath($path);

    if ($autoMake && !file_exists($outputDir)) {
      $result = @mkdir($outputDir, 0777, true);
      if (!$result) {
        $msg = sprintf('Output directory "%s" cannot be created. Maybe permissions prevent creating the directory', $outputDir);
        throw new DirectoryNotCreateException($msg);
      }
    }

    $this->outputDir = $outputDir;

    return $this;
  }


  /**
   * @return string
   */
  public function getOutputDir()
  {
    return $this->outputDir;
  }


  /**
   * @param array $libraries
   * @return self
   */
  public function setLibraries(array $libraries)
  {
    foreach ($libraries as $library) {
      $this->setLibrary($library);
    }

    return $this;
  }


  /**
   * @param array $options
   * @return self
   * @throws \CDNLoader\InvalidArgumentException
   */
  public function setLibrary(array $options)
  {
    $libraryUrl = $this->cdnUrl;

    if (!isset($options['name'])) {
      throw new InvalidArgumentException('Options has not key "name".');
    }
    $libraryUrl = str_replace('{{NAME}}', $options['name'], $libraryUrl);

    if (!isset($options['version'])) {
      throw new InvalidArgumentException('Options has not key "version".');
    }
    $libraryUrl = str_replace('{{VERSION}}', $options['version'], $libraryUrl);

    // if key "files" not exists, then create from name of library and from keys "min" and "type"
    if (!isset($options['files'])) {
      $file = $options['name'];
      $file .= isset($options['min']) ? '.min' : '';
      $file .= isset($options['type']) ? '.' . strtolower($options['type']) : '.js';

      $options['files'] = array($file);
    }

    if (!is_array($options['files'])) {
      $options['files'] = (array)$options['files'];
    }

    foreach ($options['files'] as $file) {
      $url = str_replace('{{FILE}}', $file, $libraryUrl);
      $this->libraries[] = $url;
    }

    return $this;
  }


  /**
   * @return array
   */
  public function getLibraries()
  {
    return $this->libraries;
  }


  /**
   * @return array
   */
  public function getFiles()
  {
    return $this->files;
  }


  /**
   * Load and save content of libraries to files.
   */
  public function generate()
  {
    if (!$this->isModified()) {
      $files = Finder::findFiles('*.js', '*.css')->in($this->getOutputDir());

      foreach ($files as $file => $fileInfo) {
        $this->files[] = $file;
      }

      return;
    }

    File::remove($this->outputDir, true);

    $this->writeHash();

    foreach ($this->libraries as $library) {
      $content = $this->getContent($library) . PHP_EOL;
      $suffix = strrchr($library, '.');
      $path = $this->getOutputDir() . File::DS . $this->filename . $suffix;

      File::write($path, $content, true);

      if (!in_array($path, $this->files)) {
        $this->files[] = $path;
      }
    }
  }


  /**
   * @return bool
   */
  private function isModified()
  {
    $hashFile = $this->getHashFile();

    if (!file_exists($hashFile)) {
      return true;
    }

    $hash = File::read($hashFile);
    return $hash !== $this->getCheckHash();
  }


  /**
   * @return string
   */
  private function getHashFile()
  {
    $path = $this->getOutputDir() . File::DS . $this->hashFile;
    return $path;
  }


  private function writeHash()
  {
    $path = $this->getHashFile();
    $hash = $this->getCheckHash();
    File::write($path, $hash);
  }


  /**
   * @return string
   */
  private function getCheckHash()
  {
    $serialize = serialize($this->libraries);
    $hash = md5($serialize);

    return $hash;
  }


  /**
   * @param string $url
   * @return string
   * @throws LibraryNotFoundException
   */
  private function getContent($url)
  {
    try {
      $content = File::read($url, false);
      return $content;
    }
    catch (FileProcessException $e) {
      $msg = sprintf('Library on URL "%s%" not found.', $url);
      throw new LibraryNotFoundException($msg);
    }
  }

}

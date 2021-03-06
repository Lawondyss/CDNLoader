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
  /** @var string Character ~ between version and file is intentionally to recognize the file! */
  private $cdnUrl = 'https://cdnjs.cloudflare.com/ajax/libs/{{NAME}}/{{VERSION}}~{{FILE}}';

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
      $this->loadFiles();
      return;
    }

    File::remove($this->outputDir, true);

    $this->writeHash();

    foreach ($this->libraries as $libraryUrl) {
      $this->saveFile($libraryUrl);
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
   * Specifies that file is linkable for html.
   *
   * @param string $file
   * @return bool
   */
  private function isLinkable($file)
  {
    $suffix = $this->getSuffix($file);
    return in_array($suffix, array('.js', '.css'));
  }


  /**
   * @param string $file
   * @return string
   */
  private function getSuffix($file)
  {
    $suffix = strrchr($file, '.');
    return strtolower($suffix);
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
   * Load existing JS and CSS files to property.
   */
  private function loadFiles()
  {
    $files = Finder::findFiles('*.js', '*.css')->from($this->getOutputDir());

    foreach ($files as $file => $fileInfo) {
      $this->files[] = $file;
    }
  }


  /**
   * Save content from remote library to local.
   *
   * @param string $url
   */
  private function saveFile($url)
  {
    // get name of library with possible directory
    list(,$name) = explode('~', $url);

    $content = $this->getContent($url) . PHP_EOL;
    $suffix = $this->getSuffix($name);
    $directory = dirname($name) !== '.' ? dirname($name) . File::DS : '';

    // set filename to linkable file or original name
    $filename = $this->isLinkable($name) ? $this->filename . $suffix : basename($name);

    $path = $this->getOutputDir() . File::DS . $directory . $filename;

    // TODO: move auto create directory path to File::write
    if (!file_exists(dirname($path))) {
      mkdir(dirname($path), 0777, true);
    }
    File::write($path, $content, true);

    if ($this->isLinkable($name) && !in_array($path, $this->files)) {
      $this->files[] = $path;
    }
  }


  /**
   * @param string $url
   * @return string
   * @throws LibraryNotFoundException
   */
  private function getContent($url)
  {
    try {
      $url = str_replace('~', '/', $url);
      $content = File::read($url, false);
      return $content;
    }
    catch (FileProcessException $e) {
      $msg = sprintf('Library on URL "%s%" not found.', $url);
      throw new LibraryNotFoundException($msg);
    }
  }

}

<?php
/**
 * Class File
 * @package CDNLoader
 * @author Ladislav Vondráček
 */

namespace CDNLoader;

use Nette\Utils\Finder;

class File
{
  const DS = DIRECTORY_SEPARATOR;


  /**
   * Read content from file.
   *
   * @param string $filename
   * @param bool $local
   * @return string
   * @throws \CDNLoader\FileProcessException
   * @throws \CDNLoader\InvalidArgumentException
   */
  public static function read($filename, $local = true)
  {
    self::checkFilename($filename);

    if ($local) {
      $filename = self::normalizePath($filename);
    }

    if ($local && !file_exists($filename)) {
      $msg = sprintf('File "%s" cannot be found.', $filename);
      throw new InvalidArgumentException($msg);
    }

    if ($local && !is_readable($filename)) {
      $msg = sprintf('File "%s" is not readable.', $filename);
      throw new InvalidArgumentException($msg);
    }

    if ($local) {
      $filename = self::getSafePath($filename);
    }

    $content = @file_get_contents($filename);

    if ($content === false) {
      $msg = sprintf('Reading from file "%s" failed.', $filename);
      throw new FileProcessException($msg);
    }

    return $content;
  }


  /**
   * Write data to file.
   *
   * @param string $filename
   * @param string $data
   * @param bool $append
   * @param bool $local
   * @throws \CDNLoader\InvalidArgumentException
   * @throws \CDNLoader\FileProcessException
   */
  public static function write($filename, $data, $append = false, $local = true)
  {
    self::checkFilename($filename);

    if ($local) {
      $filename = self::normalizePath($filename);
    }

    if ($local && file_exists($filename) && !is_writable($filename)) {
      $msg = sprintf('File "%s" is not writable.', $filename);
      throw new InvalidArgumentException($msg);
    }

    if (!is_string($data)) {
      $msg = sprintf('Data must be string. Given "%s".', gettype($data));
      throw new InvalidArgumentException($msg);
    }

    if ($local) {
      $filename = self::getSafePath($filename);
    }

    $flag = $append ? FILE_APPEND : null;

    $result = @file_put_contents($filename, $data, $flag);

    if ($result === false) {
      $msg = sprintf('Writing to file "%s" failed.', $filename);
      throw new FileProcessException($msg);
    }
  }


  /**
   * Remove file or directory.
   *
   * @param string $filename
   * @param bool $onlyEmptyDir
   */
  public static function remove($filename, $onlyEmptyDir = false)
  {
    self::checkFilename($filename);

    $filename = self::normalizePath($filename);

    if (is_dir($filename)) {
      $files = Finder::find('*')->in($filename);
      foreach ($files as $file => $fileInfo) {
        self::remove($file);
      }
    }

    if (is_dir($filename) && !$onlyEmptyDir) {
      rmdir($filename);
    }
    elseif (!is_dir($filename)) {
      unlink($filename);
    }
  }


  /**
   * @param string $path
   * @return string
   */
  public static function normalizePath($path)
  {
    self::checkFilename($path);

    $path = str_replace(array('/', '\\'), self::DS, $path);
    $root = (strpos($path, self::DS) === 0) ? self::DS : '';
    $parts = explode(self::DS, $path);

    $absolutes = array();
    foreach ($parts as $part) {
      if ($part === '.' || $part === '') {
        continue;
      }

      if ($part === '..') {
        array_pop($absolutes);
      }
      else {
        $absolutes[] = $part;
      }
    }

    $absolutePath = implode(self::DS, $absolutes);

    return $root . $absolutePath;
  }


  /**
   * @param $filename
   * @return string
   */
  private static function getSafePath($filename)
  {
    if (in_array('safe', stream_get_wrappers()) && substr($filename, 0, 7) !== 'safe://') {
      $filename = 'safe://' . $filename;
    }

    return $filename;
  }


  /**
   * @param string $filename
   * @throws \CDNLoader\InvalidArgumentException
   */
  private static function checkFilename($filename)
  {
    if (!is_string($filename)) {
      $msg = sprintf('Filename must be string. Given "%s".', gettype($filename));
      throw new InvalidArgumentException($msg);
    }

    if ($filename === '') {
      throw new InvalidArgumentException('Filename cannot be empty.');
    }
  }
  
}

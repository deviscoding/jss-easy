<?php

namespace DevCoding\Jss\Easy\Helper;

use DevCoding\Command\Base\Traits\ShellTrait;

class DownloadHelper
{
  use ShellTrait;

  public function getRedirectUrl($url, $userAgent = 'MDM Helper')
  {
    $cmd = sprintf('/usr/bin/curl -A "%s" ', $userAgent);
    $cmd .= sprintf('"%s" ', $url);
    $cmd .= " -s -L -I -o /dev/null -w '%{url_effective}'";

    return $this->getShellExec($cmd);
  }

  /**
   * @param        $url
   * @param null   $default
   * @param null   $etag
   * @param string $userAgent
   *
   * @return array
   * @noinspection DuplicatedCode
   */
  public function getUrl($url, $default = null, $etag = null, $userAgent = 'MDM Easy')
  {
    $ch = curl_init($url);
    $rq = [];
    if ($etag)
    {
      $rq[] = sprintf('If-None-Match: %s', $etag);
    }

    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $rq);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

    $resp = curl_exec($ch);
    $len  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $head = substr($resp, 0, $len);
    $body = substr($resp, $len);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    $header = [];
    foreach (explode(PHP_EOL, $head) as $line)
    {
      $parts = explode(':', $line);

      if (count($parts) > 1)
      {
        $key = trim($parts[0]);
        $val = trim($parts[1]);

        $header[$key] = $val;
      }
    }

    curl_close($ch);

    if (304 == $code)
    {
      return ['headers' => $header, 'body' => $default, 'cached' => true];
    }
    else
    {
      return ['headers' => $header, 'body' => $body, 'cached' => false];
    }
  }

  public function getCachedFile($name)
  {
    $path = sprintf('%s/%s', $this->getCacheDir(), $name);

    return is_file($path) ? file_get_contents($path) : null;
  }

  public function setCachedFile($name, $contents)
  {
    $path = sprintf('%s/%s', $this->getCacheDir(), $name);

    return false !== file_put_contents($path, $contents);
  }

  protected function getCacheDir()
  {
    if (empty($this->_cacheDir))
    {
      if (is_dir('/Library/JSS'))
      {
        $this->_cacheDir = '/Library/JSS/HelperCache';
      }
      else
      {
        $this->_cacheDir = '/tmp/HelperCache';
      }
    }

    if (!is_dir($this->_cacheDir))
    {
      mkdir($this->_cacheDir, 0777, true);
    }

    return $this->_cacheDir;
  }
}

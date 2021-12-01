<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Abstract class with base methods for installers located on GitHub.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 * @package DevCoding\Jss\Easy\Object\Installer
 */
abstract class GitHubInstaller extends BaseInstaller
{
  /** @var SemanticVersion */
  protected $version;
  /** @var DownloadHelper */
  protected $DownloadHelper;

  /**
   * @return string
   */
  abstract protected function getRepo();

  /**
   * @return string
   */
  abstract protected function getFile();

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

  /**
   * @return string
   */
  public function getDownloadUrl()
  {
    /** @var SemanticVersion $ver */
    $ver = $this->getVersionFromRepo();

    return sprintf('https://github.com/%s/releases/download/%s/%s', $this->getRepo(), $ver->getRaw(), $this->getFile());
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  public function getCurrentVersion()
  {
    if (empty($this->version))
    {
      $this->version = $this->getVersionFromRepo();
    }

    return $this->version;
  }

  protected function getReleaseUrl()
  {
    return sprintf('https://api.github.com/repos/%s/releases/latest', $this->getRepo());
  }

  /**
   * @return SemanticVersion|null
   */
  protected function getVersionFromRepo()
  {
    $etag = $this->getCachedEtag();
    $body = $this->getCachedData();

    if (!empty($etag) && !empty($body))
    {
      $resp = $this->getUrl($this->getReleaseUrl(), $body, $etag);
    }
    else
    {
      $resp = $this->getUrl($this->getReleaseUrl());

      if (!empty($resp['headers']['etag']))
      {
        $this->setCachedFile($this->getFilename('etag'), $resp['headers']['etag']);
      }

      if (!empty($resp['body]']))
      {
        $this->setCachedFile($this->getFilename('ver'), $resp['body']);
      }
    }

    if (!empty($resp['body']))
    {
      $parts = json_decode(trim($resp['body']), true);

      return !empty($parts['tag_name']) ? new SemanticVersion($parts['tag_name']) : null;
    }

    return null;
  }

  // region //////////////////////////////////////////////// Caching Methods

  protected function getCachedEtag()
  {
    return $this->getCachedFile($this->getFilename('etag'));
  }

  protected function getCachedData()
  {
    return $this->getCachedFile($this->getFilename('ver'));
  }

  protected function getCachedFile($name)
  {
    return $this->getDownloadHelper()->getCachedFile($name);
  }

  protected function setCachedFile($name, $contents)
  {
    return $this->getDownloadHelper()->setCachedFile($name, $contents);
  }

  /**
   * @return DownloadHelper
   */
  protected function getDownloadHelper()
  {
    if (!isset($this->DownloadHelper))
    {
      $this->DownloadHelper = new DownloadHelper();
    }

    return $this->DownloadHelper;
  }

  /**
   * @param string $suffix
   *
   * @return string
   */
  protected function getFilename($suffix)
  {
    return sprintf('github.%s.%s', str_replace('/', '.', $this->getRepo()), $suffix);
  }

  /**
   * @param string      $url
   * @param string|null $default
   * @param string|null $etag
   *
   * @return array
   */
  protected function getUrl($url, $default = null, $etag = null)
  {
    return $this->getDownloadHelper()->getUrl($url, $default, $etag);
  }

  // endregion ///////////////////////////////////////////// End Caching Methods
}

<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Installer recipe class for Visual Studio Code.
 *
 * @see     https://code.visualstudio.com/
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class VisualStudioCode extends AbstractRecipe
{
  protected $version;

  public function getName()
  {
    return 'Visual Studio Code';
  }

  public function getPath()
  {
    return '/Applications/Visual Studio Code.app';
  }

  /**
   * Returns the proper download URL for the architecture.
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    if ($this->isAppleSilicon())
    {
      return 'https://code.visualstudio.com/sha/download?build=stable&os=darwin-arm64';
    }
    else
    {
      return 'https://code.visualstudio.com/sha/download?build=stable&os=darwin';
    }
  }

  /**
   * Follows the redirected URL to find the destination URL so that we have the proper file extension.
   *
   * @return string|null
   */
  public function getDestinationUrl()
  {
    return (new DownloadHelper())->getRedirectUrl($this->getDownloadUrl(), $this->getUserAgent());
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  /**
   * Parses the update page for a darwin download URL, and retrieves the version from the URL.
   *
   * @return string|null
   */
  public function getCurrentVersion()
  {
    if (!isset($this->version))
    {
      if ($notes = $this->getReleaseNotes())
      {
        $crawler = new Crawler($notes);
        $crawler = $crawler->filter('.body > p');
        if ($crawler->count() > 0)
        {
          $crawler = $crawler->filter('a' )->reduce(function (Crawler $node, $i) {
            $href = $node->attr('href');

            return false !== stripos($href, 'darwin');
          } );

          if ($link = $crawler->attr('href'))
          {
            $this->version = $this->getVersionFromUrl($link, '#([a-z.]+)/(?<version>[0-9.]+)/#');
          }
        }
      }
    }

    return $this->version;
  }

  /**
   * Grabs the HTML of the updates page.
   *
   * @return string|null
   */
  protected function getReleaseNotes()
  {
    $url = 'https://code.visualstudio.com/updates';
    $ua  = $this->getUserAgent();

    if ($resp = (new DownloadHelper())->getUrl($url, null, null, $ua))
    {
      return $resp['body'] ?? null;
    }

    return null;
  }
}

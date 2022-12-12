<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Installer recipe class for Docker Desktop for Mac.
 *
 * @see     https://www.docker.com/products/docker-desktop
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Docker extends AbstractRecipe
{
  /** @var string */
  protected $version;

  public function getName()
  {
    return 'Docker';
  }

  public function getPath()
  {
    return '/Applications/Docker.app';
  }

  public function getDownloadUrl()
  {
    $arch = $this->isAppleSilicon() ? 'arm64' : 'amd64';

    return sprintf('https://desktop.docker.com/mac/stable/%s/Docker.dmg', $arch);
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

  /**
   * Retrieves the current version from the Docker Desktop Release Notes page.
   *
   * @noinspection PhpUnusedParameterInspection
   * @return string
   */
  public function getCurrentVersion()
  {
    if (!isset($this->version))
    {
      if ($notes = $this->getReleaseNotes())
      {
        $crawler = new Crawler($notes);
        $crawler = $crawler->filter('.col-content > section');
        if ($crawler->count() > 0)
        {

          $crawler = $crawler->filter('h2' )->reduce(function (Crawler $node, $i) {
            $id = $node->attr('id');

            return false !== is_numeric($id);
          } );

          if ($full = $crawler->getNode(0)->textContent)
          {
            $this->version = trim(str_replace('Docker Desktop', '', $full));
          }
        }
      }
    }

    return $this->version;
  }

  /**
   * Returns the HTML contents of the Docker Desktop Release notes page.
   *
   * @return string|null
   */
  protected function getReleaseNotes()
  {
    $url = 'https://docs.docker.com/desktop/release-notes/';
    $ua  = $this->getUserAgent();

    if ($resp = (new DownloadHelper())->getUrl($url, null, null, $ua))
    {
      return $resp['body'] ?? null;
    }

    return null;
  }
}

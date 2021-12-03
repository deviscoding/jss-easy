<?php

namespace DevCoding\Jss\Easy\Object\Installer;

use DevCoding\Command\Base\Traits\ShellTrait;
use DevCoding\Jss\Easy\Helper\DownloadHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Installer configuration class for Docker Desktop for Mac.
 *
 * @see     https://www.docker.com/products/docker-desktop
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Docker extends BaseInstaller
{
  use ShellTrait;

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
    if ($this->getDevice()->isAppleChip())
    {
      return 'https://desktop.docker.com/mac/stable/arm64/Docker.dmg';
    }
    else
    {
      return 'https://desktop.docker.com/mac/stable/amd64/Docker.dmg';
    }
  }

  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

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

            return false !== stripos($id, 'docker-desktop');
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

  protected function getUserAgent()
  {
    $ver = str_replace('.', '_', (string) $this->getDevice()->getOs()->getVersion());

    return sprintf('Mozilla/5.0 (Macintosh; Intel Mac OS X %s) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2', $ver);
  }

  protected function getReleaseNotes()
  {
    $url = 'https://docs.docker.com/desktop/mac/release-notes/';
    $ua  = $this->getUserAgent();

    if ($resp = (new DownloadHelper())->getUrl($url, null, null, $ua))
    {
      return $resp['body'] ?? null;
    }

    return null;
  }
}

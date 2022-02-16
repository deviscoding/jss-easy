<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Installer recipe class for SourceTree.
 *
 * @see     https://sourcetreeapp.com
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class Sourcetree extends AbstractRecipe
{
  /** @var string download url */
  protected $download;

  /**
   * @return string
   */
  public function getName()
  {
    return 'Sourcetree';
  }

  /**
   * @return string
   */
  public function getPath()
  {
    return '/Applications/Sourcetree.app';
  }

  /**
   * Parses the download URL from the button link on the app's URL.
   *
   * @return string
   */
  public function getDownloadUrl()
  {
    if (!isset($this->download))
    {
      if ($html = $this->getDownloadPageHtml())
      {
        $crawler = new Crawler($html);
        $script  = $crawler->filter('[type="text/x-component"]');
        if ($script->count() > 0)
        {
          foreach ($script as $item)
          {
            if ($json = json_decode($item->textContent, true))
            {
              if (isset($json['params']['macURL']))
              {
                $this->download = $json['params']['macURL'];
              }
            }
          }
        }

        if (!isset($this->download))
        {
          $crawler = $crawler->filter('[data-label="Download for Mac OS X"]');
          if ($link = $crawler->attr('href'))
          {
            $this->download = $link;
          }
        }
      }
    }

    return $this->download;
  }

  /**
   * @return string
   */
  public function getDestinationUrl()
  {
    return $this->getDownloadUrl();
  }

  /**
   * @return string|null
   */
  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDownloadUrl());
  }

  /**
   * @return string|null
   */
  public function getCurrentVersion()
  {
    return $this->getVersionFromUrl($this->getDownloadUrl(), '#Sourcetree_(?<version>[0-9.]+)#');
  }

  /**
   * Grabs the HTML of the app's website page.
   *
   * @return string|null
   */
  protected function getDownloadPageHtml()
  {
    $url = 'https://sourcetreeapp.com';
    $ua  = $this->getUserAgent();

    if ($resp = (new DownloadHelper())->getUrl($url, null, null, $ua))
    {
      return $resp['body'] ?? null;
    }

    return null;
  }
}

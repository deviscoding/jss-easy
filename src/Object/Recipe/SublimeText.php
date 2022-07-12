<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Installer recipe class for Sublime Text 4
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class SublimeText extends AbstractRecipe
{
  protected $version;

  public function getName()
  {
    return 'Sublime Text';
  }

  public function getPath()
  {
    return '/Applications/Sublime Text.app';
  }

  public function getDownloadUrl()
  {
    $build = str_replace('0.0+', '', $this->getCurrentVersion());

    return sprintf('https://download.sublimetext.com/sublime_text_build_%s_mac.zip', $build);
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
        $crawler = $crawler->filter('#changelog');
        if ($crawler->count() > 0)
        {
          $crawler = $crawler->filter('article')->reduce(function (Crawler $node, $i) {
            $href = $node->attr('class');

            return false !== stripos($href, 'current');
          });

          if ($h3 = $crawler->filter('h3'))
          {
            $this->version = '0.0+'.str_replace('Build ', '', $h3->html());
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
    $url = 'https://www.sublimetext.com/download';
    $ua  = $this->getUserAgent();

    if ($resp = (new DownloadHelper())->getUrl($url, null, null, $ua))
    {
      return $resp['body'] ?? null;
    }

    return null;
  }
}

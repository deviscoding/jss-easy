<?php

namespace DevCoding\Jss\Easy\Object\Recipe;

use DevCoding\Jss\Easy\Helper\DownloadHelper;
use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\MacDevice;
use DevCoding\Mac\Objects\SemanticVersion;

class GenericRecipe extends AbstractRecipe
{
  /** @var string */
  protected $path;
  /** @var string */
  protected $download;
  /** @var string */
  protected $destination;
  /** @var string */
  protected $current;
  /** @var string|null */
  protected $installed;

  /**
   * @param MacDevice $device
   * @param string    $path
   * @param string    $download
   * @param string    $current
   * @param string    $installed
   */
  public function __construct(MacDevice $device, $path, $download, $current, $installed = null)
  {
    $this->path      = $path;
    $this->download  = $download;
    $this->current   = $current;
    $this->installed = $installed;

    parent::__construct($device);
  }

  public function getName()
  {
    return pathinfo($this->getPath(), PATHINFO_BASENAME);
  }

  public function getPath()
  {
    return $this->path;
  }

  public function getDownloadUrl()
  {
    return $this->download;
  }

  public function getDestinationUrl()
  {
    if (empty($this->destination))
    {
      $this->destination = (new DownloadHelper())->getRedirectUrl($this->getDownloadUrl());
    }

    return $this->destination;
  }

  public function getInstalledVersion()
  {
    if (!isset($this->installed))
    {
      $this->installed = parent::getInstalledVersion();
    }

    return $this->installed;
  }

  public function getInstallerType()
  {
    return $this->getInstallerTypeFromUrl($this->getDestinationUrl());
  }

  public function getCurrentVersion()
  {
    return $this->current;
  }

  public function isMatch(MacApplication $offered)
  {
    $oFilename = $offered->getFilename();
    $tFilename = pathinfo($this->getPath(), PATHINFO_BASENAME);

    if ($oFilename == $tFilename)
    {
      $ver = $this->getCurrentVersion();
      if (!$ver)
      {
        return true;
      }
      else
      {
        $cVer = new SemanticVersion($ver);
        $oVer = $offered->getShortVersion() ?? $offered->getVersion();

        if (0 == strpos($oVer->getRaw(), 'Build _'))
        {
          $oVer = new SemanticVersion('0.0+'.$oVer->getBuild());
        }

        if ($oVer->eq($cVer))
        {
          return true;
        }
        elseif ($oVer->getRaw() == $cVer->getRaw())
        {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * @param MacApplication $offered
   *
   * @return bool
   */
  public function isNewer(MacApplication $offered)
  {
    $oFilename = $offered->getFilename();
    $tFilename = pathinfo($this->getPath(), PATHINFO_BASENAME);

    if ($oFilename == $tFilename)
    {
      $cVer = new SemanticVersion($this->getCurrentVersion());
      $oVer = $offered->getShortVersion() ?? $offered->getVersion();

      return $oVer->gt($cVer);
    }

    return false;
  }
}

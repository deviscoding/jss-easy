<?php

namespace DevCoding\Jss\Helper\Command\Info;

use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\SemanticVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppCommand extends AbstractInfoConsole
{
  const NAME          = 'name';
  const IDENTIFIER    = 'identifier';
  const PATH          = 'path';
  const FILENAME      = 'filename';
  const COPYRIGHT     = 'copyright';
  const VERSION       = 'version';
  const FULL          = 'full';
  const SHORT_VERSION = 'short_version';
  const MAJOR         = 'major';
  const MINOR         = 'minor';
  const REVISION      = 'revision';
  const BUILD         = 'build';
  const PRERELEASE    = 'prerelease';
  const RAW           = 'raw';

  protected function configure()
  {
    $this->setName('app')
         ->addArgument('criteria', InputArgument::REQUIRED)
         ->addArgument('key', InputArgument::OPTIONAL)
         ->addOption('json', 'j', InputOption::VALUE_NONE)
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $theKey = $this->io()->getArgument('key');
    $theApp = $this->io()->getArgument('criteria');

    if ($this->isJson())
    {
      $this->io()->getOutput()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    if ($MacApp = $this->getApp($theApp))
    {
      $data = $this->getInfo($MacApp, $theKey);
    }
    else
    {
      $this->io()->errorblk('No Application Found');

      return self::EXIT_ERROR;
    }

    if ($this->isJson())
    {
      $this->io()->writeln(json_encode($data, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT), null, false, OutputInterface::VERBOSITY_QUIET);
    }
    elseif (is_array($data))
    {
      $this->renderOutput($data);
    }
    else
    {
      $this->io()->writeln($data);
    }

    return self::EXIT_SUCCESS;
  }

  // region //////////////////////////////////////////////// Information Methods

  protected function getApp($key)
  {
    if (is_dir($key) && is_file($key.'/Contents/Info.plist'))
    {
      return new MacApplication($key);
    }

    $MacApps = $this->getOs()->getApplications();

    foreach ($MacApps as $MacApp)
    {
      if ($MacApp->getFilename() == $key)
      {
        return $MacApp;
      }
      elseif ($MacApp->getPathname() == $key)
      {
        return $MacApp;
      }
      elseif ($MacApp->getIdentifier() == $key)
      {
        return $MacApp;
      }
      elseif ($MacApp->getName() == $key)
      {
        return $MacApp;
      }
    }

    return null;
  }

  /**
   * @param MacApplication $MacApp
   * @param string|null    $key
   *
   * @return string[]|string|int|null
   */
  protected function getInfo($MacApp, $key = null)
  {
    $subKeys = [self::NAME, self::VERSION, self::SHORT_VERSION, self::IDENTIFIER, self::PATH, self::FILENAME, self::COPYRIGHT];

    if (self::NAME === $key)
    {
      return $MacApp->getName();
    }
    elseif (self::IDENTIFIER === $key)
    {
      return $MacApp->getIdentifier();
    }
    elseif (self::PATH === $key)
    {
      return $MacApp->getPathname();
    }
    elseif (self::FILENAME === $key)
    {
      return $MacApp->getFilename();
    }
    elseif (self::COPYRIGHT === $key)
    {
      return $MacApp->getCopyright();
    }
    elseif (0 === strpos($key, self::VERSION))
    {
      $subKey  = $this->getSubkey($key);
      $version = $MacApp->getVersion();

      return !empty($subKey) ? $this->getVersion($version, $subKey) : $this->getVersion($version);
    }
    elseif (0 === strpos($key, self::SHORT_VERSION))
    {
      $subKey  = $this->getSubkey($key);
      $version = $MacApp->getShortVersion();

      return !empty($subKey) ? $this->getVersion($version, $subKey) : $this->getVersion($version);
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getInfo($MacApp, $subKey);
      }

      return $retval;
    }

    return null;
  }

  /**
   * @param SemanticVersion $SemVer
   * @param string|null     $key
   *
   * @return array|int|mixed|string|null
   */
  protected function getVersion($SemVer, $key = null)
  {
    $subKeys = [self::RAW, self::FULL, self::MAJOR, self::MINOR, self::REVISION, self::PRERELEASE, self::BUILD];

    if (self::RAW === $key)
    {
      return $SemVer->getRaw();
    }
    if (self::FULL === $key)
    {
      return (string) $SemVer;
    }
    elseif (self::MAJOR === $key)
    {
      return $SemVer->getMajor();
    }
    elseif (self::MINOR === $key)
    {
      return $SemVer->getMinor();
    }
    elseif (self::REVISION === $key)
    {
      return $SemVer->getRevision();
    }
    elseif (self::BUILD === $key)
    {
      return $SemVer->getBuild();
    }
    elseif (self::PRERELEASE === $key)
    {
      return $SemVer->getPreRelease();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getVersion($SemVer, $subKey);
      }

      return $retval;
    }

    return null;
  }
}

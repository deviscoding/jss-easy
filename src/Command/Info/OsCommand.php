<?php

namespace DevCoding\Jss\Helper\Command\Info;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OsCommand extends AbstractInfoConsole
{
  const CACHE        = 'cache';
  const SHARED       = 'shared';
  const PERSONAL     = 'personal';
  const SUS          = 'sus';
  const VERSION      = 'version';
  const NAME         = 'name';
  const MAJOR        = 'major';
  const MINOR        = 'minor';
  const REVISION     = 'revision';
  const BUILD        = 'build';
  const FULL         = 'full';
  const RAW          = 'raw';
  const CONSOLE      = 'console';
  const USER         = 'user';
  const USERID       = 'userid';
  const CONSOLE_OPEN = 'open';

  protected function configure()
  {
    $this
        ->setName('info:os')
        ->setAliases(['os'])
        ->addArgument('key', InputArgument::OPTIONAL)->addOption('json', 'j', InputOption::VALUE_NONE);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $theKey = $this->io()->getArgument('key');

    if ($this->isJson())
    {
      $this->io()->getOutput()->setVerbosity(OutputInterface::VERBOSITY_QUIET);
    }

    if ($theKey)
    {
      $subKey = $this->getSubkey($theKey);
      if (false !== strpos($theKey, self::VERSION))
      {
        $data = $this->getVersion($subKey);
      }
      elseif (false !== strpos($theKey, self::CACHE))
      {
        $data = $this->getCache($subKey);
      }
      elseif (false !== strpos($theKey, self::SUS))
      {
        $data = $this->getOs()->getSoftwareUpdateCatalogUrl();
      }
      elseif (false !== strpos($theKey, self::CONSOLE))
      {
        $data = $this->getConsole($subKey);
      }
      else
      {
        $this->io()->errorln('Unrecognized key.');

        return self::EXIT_ERROR;
      }
    }
    else
    {
      $data = $this->getSummary();
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

  protected function getSummary()
  {
    return [
        self::VERSION      => $this->getVersion(),
        self::CACHE        => $this->getCache(),
        self::SUS          => $this->getOs()->getSoftwareUpdateCatalogUrl(),
        self::CONSOLE      => $this->getConsole()
    ];
  }

  // region //////////////////////////////////////////////// Information Methods

  protected function getCache($key = null)
  {
    $subKeys = [self::SHARED, self::PERSONAL];

    if (self::SHARED === $key)
    {
      return $this->getOs()->getSharedCaches();
    }
    elseif (self::PERSONAL === $key)
    {
      return $this->getOs()->getPersonalCaches();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getCache($subKey);
      }

      return $retval;
    }

    return null;
  }

  protected function getConsole($key = null)
  {
    $subKeys = [self::USER, self::USERID, self::CONSOLE_OPEN];

    if (self::USER === $key)
    {
      return $this->getUser();
    }
    elseif (self::USERID === $key)
    {
      return $this->getLaunchUserId();
    }
    elseif (self::CONSOLE_OPEN === $key)
    {
      return $this->getOpenCommand();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getConsole($subKey);
      }

      return $retval;
    }

    return null;
  }

  protected function getVersion($key = null)
  {
    $subKeys = [self::FULL, self::RAW, self::MAJOR, self::MINOR, self::REVISION, self::NAME, self::BUILD];

    if (self::FULL === $key)
    {
      return (string) $this->getOs()->getVersion();
    }
    elseif (self::RAW === $key)
    {
      return $this->getOs()->getVersion()->getRaw();
    }
    elseif (self::MAJOR === $key)
    {
      return $this->getOs()->getVersion()->getMajor();
    }
    elseif (self::MINOR === $key)
    {
      return $this->getOs()->getVersion()->getMinor();
    }
    elseif (self::REVISION === $key)
    {
      return $this->getOs()->getVersion()->getRevision();
    }
    elseif (self::NAME === $key)
    {
      return $this->getOs()->getVersion()->getName();
    }
    elseif (self::BUILD === $key)
    {
      return $this->getOs()->getVersion()->getBuild();
    }
    elseif (is_null($key))
    {
      $retval = [];
      foreach ($subKeys as $subKey)
      {
        $retval[$subKey] = $this->getVersion($subKey);
      }

      return $retval;
    }

    return null;
  }


  protected function getOpenCommand()
  {
    $launchctl = $this->getBinaryPath('launchctl');
    $open      = $this->getBinaryPath('open');
    $id        = $this->getLaunchUserId();
    $method    = $this->getLaunchMethod();

    return sprintf('%s %s %s %s "%s" > /dev/null 2>&1 &', $launchctl, $method, $id, $open, '${TOPEN}');
  }
}

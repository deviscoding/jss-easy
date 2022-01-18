<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Jss\Easy\Object\Installer\BaseInstaller;
use DevCoding\Jss\Easy\Object\Installer\GenericInstaller;
use DevCoding\Mac\Command\AbstractMacConsole;
use DevCoding\Mac\Objects\MacApplication;
use DevCoding\Mac\Objects\SemanticVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractDownloadConsole extends AbstractMacConsole
{
  const CONTINUE = -1;

  /** @var string */
  protected $_cacheDir;
  protected $_downloadFile;
  /** @var SemanticVersion */
  protected $_target;
  /** @var GenericInstaller */
  protected $_installer;

  /**
   * @return bool
   */
  abstract protected function isTargetOption();

  abstract protected function getDownloadExtension();

  protected function isAllowUserOption()
  {
    return false;
  }

  protected function configure()
  {
    $this
        ->addArgument('destination', InputArgument::REQUIRED)
        ->addOption('installed', null, InputOption::VALUE_REQUIRED)
        ->addOption('overwrite', null, InputOption::VALUE_NONE)
        ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Timeout for installation processes.', 900)
    ;

    if ($this->isTargetOption())
    {
      $this->addOption('target', null, InputOption::VALUE_REQUIRED);
    }

    parent::configure();
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    if ($dest = $input->getArgument('destination'))
    {
      if (!$input->getOption('installed'))
      {
        if ($version = $this->getAppVersion($dest))
        {
          $input->setOption('installed', (string) $version);
        }
      }
    }
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   * @noinspection PhpUnusedParameterInspection
   */
  protected function executeUpgradeCheck(InputInterface $input, OutputInterface $output)
  {
    if (!$this->isOverwrite())
    {
    // Check Vs. Current if Provided
      $installer = $this->getInstaller();
      $installed = $installer->getInstalledVersion();
      $current   = $installer->getCurrentVersion();

    if ($installed && $current)
    {
        if ($installer->isInstalled())
        {
      $this->io()->msg('Is Update Needed?', 50);

          if ($installer->isCurrent())
      {
        $this->successbg($installed);

        return self::EXIT_SUCCESS;
      }
      else
      {
        $this->successbg($installed);
      }
    }
      }
    }

    return self::CONTINUE;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   * @noinspection PhpUnusedParameterInspection
   */
  protected function executeOverwriteCheck(InputInterface $input, OutputInterface $output)
  {
    // Check if already installed unless overwriting
    if (!$this->io()->getOption('overwrite'))
    {
      $this->io()->msg('Is Install Needed?', 50);
      if ($this->isInstalled())
      {
        $this->successbg('no');
        $this->io()->blankln();

        return self::EXIT_SUCCESS;
      }
      else
      {
        $this->successbg('yes');
      }
    }

    return self::CONTINUE;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   * @noinspection PhpUnusedParameterInspection
   */
  protected function executeDownload(InputInterface $input, OutputInterface $output)
  {
    $downFile = $this->getDownloadFile();
    if ($dUrl = $this->getDownloadUrl())
    {
      $this->io()->msg('Downloading File', 50);

      if ($this->getDownload($dUrl, $downFile))
      {
        $this->successbg('SUCCESS');
      }
      else
      {
        $this->errorbg('ERROR');
        $this->io()->blankln();

        return self::EXIT_ERROR;
      }
    }

    return self::CONTINUE;
  }

  // region //////////////////////////////////////////////// Information Methods

  /**
   * @param string $path
   *
   * @return SemanticVersion|null
   */
  protected function getAppVersion($path)
  {
    if ('app' == pathinfo($path, PATHINFO_EXTENSION) && is_dir($path))
    {
      return (new MacApplication($path))->getShortVersion();
    }

    return null;
  }

  /**
   * Determines if the destination is an application bundle.  If the destination isn't present, guesses based on
   * the extension.
   *
   * @return bool
   */
  protected function isApp()
  {
    if (file_exists($this->getDestination()))
    {
      // If present, check for Info.plist
      return $this->isAppBundle($this->getDestination());
    }
    else
    {
      $ext = pathinfo($this->getDestination(), PATHINFO_EXTENSION);

      return 'app' == $ext || 'plugin' == $ext || 'jdk' == $ext;
    }
  }

  /**
   * Determines if the given path is an application bundle.
   *
   * @param string $path
   *
   * @return bool
   */
  protected function isAppBundle($path)
  {
    return is_dir($path) && file_exists($path.'/Contents/Info.plist');
  }

  protected function isInstalled()
  {
    return ($this->isApp()) ? is_dir($this->getDestination()) : is_file($this->getDestination());
  }

  // endregion ///////////////////////////////////////////// End Information Methods

  // region //////////////////////////////////////////////// Version Methods

  /**
   * @return SemanticVersion|null
   */
  protected function getTargetVersion()
  {
    if (!isset($this->_target))
    {
      if ($ver = $this->io()->getOption('target'))
      {
        $this->_target = new SemanticVersion($ver);
      }
    }

    return $this->_target;
  }

  /**
   * @return SemanticVersion|null
   */
  protected function getInstalledVersion()
  {
    if ($ver = $this->io()->getOption('installed'))
    {
      return new SemanticVersion($ver);
    }
    elseif ($this->isAppBundle($this->getDestination()))
    {
      return (new MacApplication($this->getDestination()))->getShortVersion();
    }

    return null;
  }

  /**
   * @param SemanticVersion|MacApplication $app_or_ver
   *
   * @return bool
   */
  protected function isVersionMatch($app_or_ver)
  {
    // Compare with current version if given
    $new  = $this->getAppVersion($this->getDestination());
    $comp = ($app_or_ver instanceof MacApplication) ? $this->getAppVersion($app_or_ver) : $app_or_ver;

    return $new instanceof SemanticVersion && $comp instanceof SemanticVersion && $new->eq($comp);
  }

  /**
   * @param SemanticVersion|MacApplication $app_or_ver
   *
   * @return bool
   */
  protected function isVersionGreater($app_or_ver)
  {
    $installed = $this->getInstalledVersion();
    $compare   = ($app_or_ver instanceof MacApplication) ? $app_or_ver->getShortVersion() : $app_or_ver;

    return $installed instanceof SemanticVersion && $compare instanceof SemanticVersion && $compare->gt($installed);
  }

  /**
   * @param SemanticVersion|string $target
   *
   * @return $this
   */
  protected function setTargetVersion($target)
  {
    $this->_target = $target instanceof SemanticVersion ? $target : new SemanticVersion($target);

    return $this;
  }

  // endregion ///////////////////////////////////////////// End Version Methods

  // region //////////////////////////////////////////////// Input/Output Methods

  /**
   * @return BaseInstaller
   */
  protected function getInstaller()
  {
    if (!isset($this->_installer))
    {
      $this->_installer = new GenericInstaller(
          $this->getDevice(),
          $this->io()->getArgument('destination'),
          $this->io()->getArgument('url'),
          $this->io()->getOption('target') ?? false,
          $this->io()->getOption('installed') ?? null
      );
    }

    return $this->_installer;
  }

  /**
   * @return string
   */
  protected function getDestination()
  {
    return $this->io()->getArgument('destination');
  }

  /**
   * @return string
   */
  protected function getDownloadUrl()
  {
    return $this->io()->getArgument('url');
  }

  /**
   * @return bool
   */
  protected function isOverwrite()
  {
    return $this->io()->getOption('overwrite');
  }

  protected function successbg($msg)
  {
    $this->io()->write('[')->success(strtoupper($msg))->writeln(']');

    return $this;
  }

  protected function errorbg($msg)
  {
    $this->io()->write('[')->error(strtoupper($msg))->writeln(']');

    return $this;
  }

  // endregion ///////////////////////////////////////////// End Input/Output Methods

  // region //////////////////////////////////////////////// Download Methods

  /**
   * @return string
   */
  protected function getDownloadFile()
  {
    if (empty($this->_downloadFile))
    {
      $this->_downloadFile = $this->getTempFile($this->getDownloadExtension());
    }

    return $this->_downloadFile;
  }

  /**
   * @param $url
   * @param $file
   *
   * @noinspection DuplicatedCode
   * @return bool
   */
  protected function getDownload($url, $file)
  {
    $fp = fopen($file, 'w+');
    $ch = curl_init(str_replace(' ', '%20', $url));
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    fclose($fp);

    if (200 == $code)
    {
      chmod($file, 0755);

      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * @param      $url
   * @param null $default
   * @param null $etag
   *
   * @noinspection DuplicatedCode
   * @return array
   */
  protected function getUrl($url, $default = null, $etag = null)
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
    curl_setopt($ch, CURLOPT_USERAGENT, 'Jamf Fetcher');

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

  // endregion ///////////////////////////////////////////// End Download Methods

  // region //////////////////////////////////////////////// Caching Methods

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

  protected function getCachedFile($name)
  {
    $path = sprintf('%s/%s', $this->getCacheDir(), $name);

    return is_file($path) ? file_get_contents($path) : null;
  }

  protected function setCachedFile($name, $contents)
  {
    $path = sprintf('%s/%s', $this->getCacheDir(), $name);

    return false !== file_put_contents($path, $contents);
  }

  // endregion ///////////////////////////////////////////// End Caching Methods

  // region //////////////////////////////////////////////// Install Methods

  protected function getTempFile($ext)
  {
    $basename = pathinfo($this->getDestination(), PATHINFO_FILENAME);

    return @tempnam($this->getCacheDir(), $basename.'-').'.'.$ext;
  }

  protected function installPkgFile($pkgFile, &$error)
  {
    $cmd     = sprintf('installer -allowUntrusted -pkg "%s" -target / 1>/dev/null', $pkgFile);
    $Process = $this->getProcessFromShellCommandLine($cmd);
    $Process->run();

    if (!$Process->isSuccessful())
    {
      $error = $Process->getErrorOutput();
      if (empty($error))
      {
        $error = $Process->getOutput();
      }

      return false;
    }

    return true;
  }

  protected function installAppFile($appFile, &$error)
  {
    return $this->installFile($appFile, $error);
  }

  /**
   * @param string $file
   * @param string $error
   *
   * @return bool
   */
  protected function installFile($file, &$error)
  {
    $cmd     = sprintf('ditto -rsrc "%s" "%s"', $file, $this->getDestination());
    $Process = $this->getProcessFromShellCommandLine($cmd);
    $Process->run();

    if (!$Process->isSuccessful())
    {
      $error = $Process->getErrorOutput();
      if (empty($error))
      {
        $error = $Process->getOutput();
      }

      return false;
    }

    return true;
  }

  /**
   * Returns a process object by using Process::fromShellCommandline, then setting the timeout according to the input
   * option or it's default.
   *
   * @param string $cmd
   *
   * @return Process
   */
  protected function getProcessFromShellCommandLine($cmd)
  {
    $Process = Process::fromShellCommandline($cmd);
    if ($timeout = $this->io()->getOption('timeout'))
    {
      $Process->setTimeout($timeout)->setIdleTimeout($timeout);
    }

    return $Process;
  }

}

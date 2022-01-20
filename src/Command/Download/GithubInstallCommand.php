<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Mac\Objects\SemanticVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to install an application or binary file from a release in a GitHub repo. Verifies that the DMG should be
 * downloaded, then downloads, mounts/extracts, verifies source, installs, unmounts, and cleans up.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 *
 * @package DevCoding\Jss\Easy\Command\Download
 */
class GithubInstallCommand extends AbstractInstallConsole
{
  /**
   * {@inheritDoc}
   */
  protected function isTargetOption()
  {
    return false;
  }

  /**
   * {@inheritDoc}
   */
  protected function getDownloadExtension()
  {
    return null;
  }

  /**
   * {@inheritDoc}
   */
  protected function getDownloadFile()
  {
    if (empty($this->_downloadFile))
    {
      $filename = str_replace('/', '.', $this->getRepo()).'.'.$this->io()->getOption('file');

      $this->_downloadFile = tempnam($this->getCacheDir(), $filename);
    }

    return $this->_downloadFile;
  }

  /**
   * Sets the command name and adds source argument as well as the repo and file options.
   *
   * @return void
   */
  protected function configure()
  {
    parent::configure();

    $this->setName('install:github')
         ->addArgument('source', InputArgument::REQUIRED)
         ->addOption('repo', null, InputOption::VALUE_REQUIRED)
         ->addOption('file', null, InputOption::VALUE_REQUIRED)
    ;
  }

  /**
   * Automatically sets the repo and file options from the source argument, and vice versa.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return void
   */
  protected function interact(InputInterface $input, OutputInterface $output)
  {
    if ($src = $input->getArgument('source'))
    {
      $parts = explode('/', $src);
      if ($input->getOption('repo') && count($parts) > 1)
      {
        $input->setOption('repo', $parts[0].$parts[1]);
      }

      if ($input->getOption('file') && count($parts) > 2)
      {
        $input->setOption('file', $parts[2]);
      }
    }
    else
    {
      if (($repo = $input->getOption('repo')) && ($file = $input->getOption('file')))
      {
        $input->setArgument('source', $repo.'/'.$file);
      }
    }
  }

  /**
   * Performs a check to determine if the file should be downloaded & installed, downloads, extracts, verifies source,
   * installs, verifies the installed application, then cleans up.
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Get Current Version
    $this->io()->blankln()->msg('Checking Current Version', 50);

    if (!$curr = $this->getTargetVersion())
    {
      $this->errorbg('ERROR');
      $this->io()->blankln();

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg($curr);
    }

    // Check Vs. Current if Provided
    if ($installed = $this->getInstalledVersion())
    {
      $this->io()->msg('Is Update Needed?', 50);

      if ($installed->gte($curr))
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
    // Check if already installed unless overwriting
    elseif (!$this->io()->getOption('overwrite'))
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

    // Download & Install
    if ($dUrl = $this->getDownloadUrl($curr))
    {
      $this->io()->msg('Downloading File', 50);

      $tempFile = $this->getDownloadFile();

      if ($this->getDownload($dUrl, $tempFile))
      {
        $this->successbg('SUCCESS');

        $this->io()->msg('Installing File', 50);

        if (!rename($tempFile, $this->getDestination()))
        {
          $this->successbg('SUCCESS');
        }
        else
        {
          $this->errorbg('FAIL');
          $this->io()->blankln();

          unlink($tempFile);

          return self::EXIT_ERROR;
        }
      }
      else
      {
        $this->errorbg('ERROR');
        $this->io()->blankln();

        return self::EXIT_ERROR;
      }
    }

    return self::EXIT_SUCCESS;
  }

  /**
   * {@inheritDoc}
   */
  protected function getInstalledVersion()
  {
    if ($ver = $this->io()->getOption('installed'))
    {
      return new SemanticVersion($ver);
    }

    return null;
  }

  /**
   * {@inheritDoc}
   */
  protected function isInstalled()
  {
    return is_file($this->getDestination());
  }

  /**
   * Returns the target version by polling the GitHub repo for the most recent release.
   *
   * @return SemanticVersion|null
   */
  protected function getTargetVersion()
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

  /**
   * {@inheritDoc}
   */
  protected function getDestination()
  {
    return $this->io()->getArgument('destination');
  }

  /**
   * Returns the repo name from the 'repo' option, typically username/repo
   *
   * @return string
   */
  protected function getRepo()
  {
    return $this->io()->getOption('repo');
  }

  /**
   * Returns the release URL on GitHub for the source repo name given at runtime.
   *
   * @return string
   */
  protected function getReleaseUrl()
  {
    return sprintf('https://api.github.com/repos/%s/releases/latest', $this->getRepo());
  }

  /**
   * Returns the download URL on Github for the file & source repo name given at runtime.
   *
   * @return string
   */
  protected function getDownloadUrl()
  {
    /** @var SemanticVersion $ver */
    $ver = func_get_arg(0);

    return sprintf('https://github.com/%s/releases/download/%s/%s', $this->getRepo(), $ver->getRaw(), $this->io()->getOption('file'));
  }

  /**
   * Returns the etag from the last access of the release URL.
   *
   * @return false|string|null
   */
  protected function getCachedEtag()
  {
    return $this->getCachedFile($this->getFilename('etag'));
  }

  /**
   * Returns cached data from the last access of the release URL.
   *
   * @return false|string|null
   */
  protected function getCachedData()
  {
    return $this->getCachedFile($this->getFilename('ver'));
  }

  /**
   * Returns the expected filename in the cache for the given suffix.
   *
   * @param string $suffix
   *
   * @return string
   */
  protected function getFilename($suffix)
  {
    return sprintf('github.%s.%s', str_replace('/', '.', $this->getRepo()), $suffix);
  }
}

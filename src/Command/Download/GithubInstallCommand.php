<?php

namespace DevCoding\Jss\Easy\Command\Download;

use DevCoding\Mac\Objects\SemanticVersion;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GithubInstallCommand extends AbstractDownloadConsole
{
  protected function isTargetOption()
  {
    return false;
  }

  protected function getDownloadExtension()
  {
    return null;
  }

  protected function getDownloadFile()
  {
    if (empty($this->_downloadFile))
    {
      $filename = str_replace('/', '.', $this->getRepo()).'.'.$this->io()->getOption('file');

      $this->_downloadFile = tempnam($this->getCacheDir(), $filename);
    }

    return $this->_downloadFile;
  }

  protected function configure()
  {
    parent::configure();

    $this->setName('install:github')
         ->addArgument('source', InputArgument::REQUIRED)
         ->addOption('repo', null, InputOption::VALUE_REQUIRED)
         ->addOption('file', null, InputOption::VALUE_REQUIRED)
    ;
  }

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
      if ($repo = $input->getOption('repo') && $file = $input->getOption('file'))
      {
        $input->setArgument('source', $repo.'/'.$file);
      }
    }
  }

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
   * @return SemanticVersion|null
   */
  protected function getInstalledVersion()
  {
    if ($ver = $this->io()->getOption('installed'))
    {
      return new SemanticVersion($ver);
    }

    return null;
  }

  protected function isInstalled()
  {
    return is_file($this->getDestination());
  }

  /**
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

  protected function getDestination()
  {
    return $this->io()->getArgument('destination');
  }

  protected function getRepo()
  {
    return $this->io()->getOption('repo');
  }

  protected function getReleaseUrl()
  {
    return sprintf('https://api.github.com/repos/%s/releases/latest', $this->getRepo());
  }

  /**
   * @return string
   */
  protected function getDownloadUrl()
  {
    /** @var SemanticVersion $ver */
    $ver = func_get_arg(0);

    return sprintf('https://github.com/%s/releases/download/%s/%s', $this->getRepo(), $ver->getRaw(), $this->io()->getOption('file'));
  }

  protected function getCachedEtag()
  {
    return $this->getCachedFile($this->getFilename('etag'));
  }

  protected function getCachedData()
  {
    return $this->getCachedFile($this->getFilename('ver'));
  }

  protected function getFilename($suffix)
  {
    return sprintf('github.%s.%s', str_replace('/', '.', $this->getRepo()), $suffix);
  }
}

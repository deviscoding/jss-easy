<?php

namespace DevCoding\Jss\Helper\Command\Download;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PkgInstallCommand extends AbstractDownloadConsole
{
  protected function isTargetOption()
  {
    return true;
  }

  protected function getDownloadExtension()
  {
    return 'pkg';
  }


  protected function configure()
  {
    parent::configure();

    $this->setName('install:pkg')->addArgument('url', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Check Vs. Current if Provided
    $checks = $this->executeChecks($input, $output);
    if (self::CONTINUE !== $checks)
    {
      return $checks;
    }

    // Download & Install
    $download = $this->executeDownload($input, $output);
    if (self::CONTINUE !== $download)
    {
      return $download;
    }

    // Install the PKG
    $pkgFile = $this->getDownloadFile();
    $target  = $this->getTargetVersion();
    $this->io()->msg('Installing from PKG', 50);
    if (!$this->installPkgFile($pkgFile, $errors))
    {
      $this->errorbg('error');
      $aErrors = explode("\n", $errors);
      foreach ($aErrors as $error)
      {
        $this->io()->write('  '.$error);
      }

      return self::EXIT_ERROR;
    }
    elseif (!$this->isInstalled())
    {
      $this->errorbg('error');
      $this->io()->write('  Application not found at destination: '.$this->getDestination());

      return self::EXIT_ERROR;
    }
    elseif ($target && !$this->isVersionMatch($target))
    {
      $this->errorbg('error');
      if ($new = $this->getAppVersion($this->getDestination()))
      {
        $this->io()->write(sprintf('  New Version (%s) != Target Version (%s)!', $new, $target));
      }
      else
      {
        $this->io()->write('  Cannot read new version number!');
      }
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    // Clean Up
    $this->io()->msg('Cleaning Up');
    if (file_exists($pkgFile) && !unlink($pkgFile))
    {
      $this->errorbg('ERROR');

      return self::EXIT_ERROR;
    }
    else
    {
      $this->successbg('SUCCESS');
    }

    return self::EXIT_SUCCESS;
  }
}

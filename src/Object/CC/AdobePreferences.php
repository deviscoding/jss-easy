<?php

namespace DevCoding\Jss\Easy\Object\CC;

use DevCoding\Mac\Objects\AdobeApplication;
use DevCoding\Mac\Objects\SemanticVersion;

/**
 * Utility class to obtain the paths to the preferences to an Adobe Application.
 *
 * @author  AMJones <am@jonesiscoding.com>
 * @license https://github.com/deviscoding/jss-helper/blob/main/LICENSE
 */
class AdobePreferences
{
  const PATHS = [
      'photoshop' => [
          'Library/Preferences/Adobe {name} {year} Settings',
          'Library/Preferences/Adobe {name} {year} Paths',
          'Library/Preferences/Adobe/Photoshop/{baseVersion}',
      ],
      'indesign'    => ['Library/Preferences/Adobe InDesign/Version {baseVersion}'],
      'illustrator' => [
          'Library/Preferences/Adobe/Adobe Illustrator/{version}',
          'Library/Preferences/Adobe Illustrator {majorVersion} Settings',
      ],
      'bridge' => [
          'Library/Preferences/Adobe/Bridge/{version}',
          'Library/Preferences/com.adobe.bridge{majorVersion}.plist',
      ],
      'after-effects' => ['Library/Preferences/Adobe/After Effects/{baseVersion}'],
      'animate'       => [
          'Library/Preferences/Adobe/Animate/{year}',
          'Library/Preferences/Adobe/Animate Common/',
      ],
      'premiere-pro' => ['Documents/Adobe/Premiere Pro/{baseVersion}'],
      'xd'           => [
          'Library/Application Support/Adobe/Adobe {name}',
          'Library/Application Support/Adobe.{name}',
      ],
      'dimension' => ['Library/Application Support/Adobe {name}'],
  ];

  /**
   * @var AdobeApplication
   */
  protected $application;

  /**
   * @param AdobeApplication $application
   */
  public function __construct(AdobeApplication $application)
  {
    $this->application = $application;
  }

  /**
   * @return string[]
   */
  public function getPaths()
  {
    $paths = [];
    if ($templates = $this->getPathTemplates())
    {
      $find = ['{name}', '{year}', '{version}', '{baseVersion}', '{majorVersion}'];
      $repl = [$this->getName(), $this->getYear(), $this->getVersion(), $this->getBaseVersion(), $this->getMajorVersion()];

      foreach ($templates as $pathTemplate)
      {
        $paths[] = str_replace($find, $repl, $pathTemplate);
      }
    }

    return $paths;
  }

  /**
   * @return string
   */
  protected function getName()
  {
    return $this->application->getName();
  }

  protected function getPathTemplates()
  {
    $slug = $this->application->getSlug();

    return self::PATHS[$slug] ?? null;
  }

  /**
   * @return SemanticVersion|false
   */
  protected function getBaseVersion()
  {
    return $this->application->getBaseVersion();
  }

  protected function getMajorVersion()
  {
    return $this->application->getVersion()->getMajor();
  }

  /**
   * @return SemanticVersion|null
   */
  protected function getVersion()
  {
    return $this->application->getVersion();
  }

  protected function getYear()
  {
    return $this->application->getYear();
  }
}

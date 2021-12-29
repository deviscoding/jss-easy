# JSS Easy
In developing scripts to work with Jamf Pro to manage a fleet of Mac computers, I found that similar functionality was often needed in each script.  As Jamf does not offer any sort of shared script library, this often led to repeating blocks of code and updating many scripts when changes were needed. The JSS Helper application was created to streamline scripts by providing assistance with three types of functionality:

* Downloads & Installations
* Script Output
* Information Gathering

While it was designed for use with Jamf Pro, it may be used with other MDM systems, or without any MDM system.

## Installation, Dependencies, and Compatibility

For installation on client systems, it is recommended that you use a policy that runs a script, called via a trigger.  See the `/resources` directory for an example.  Once you have a policy created, you can use **jez** in your scripts by adding the following lines:

    ([ -z "$(which jez)" ] && jamf policy --trigger InstallJez) && ([ -z "$(which jez)" ] && echo "ERROR: JEZ Not Installed") && exit 1
    source "$(jez prep)"

At this time, JSS Easy has no dependencies when used in macOS High Sierra, Mojave, Catalina, or Big Sur.  

For testing, simply download the `jez.phar` to your utility path, and rename it, for example `/usr/local/bin/jez`.

## Script Output Helpers

During the course of running a script it can be helpful to provide some output to improve any logs kept by your MDM, as well as to ease troubleshooting.   As a script run by most MDM systems has no terminal type, there is additional complexity in adding output if one wishes to use color or spacing.  The output helpers allow for colored output when your terminal allows for it, and plain output when run in an automated fashion.  

There are three basic commands: `write`, `writeln`, and `badge`.

| Command  | Description |
|--|--|
| `write` | Writes a line of text.  Can provide consistent widths using the `--width` option. |
| `writeln` | Writes a line of text followed by a new line. |
| `badge` | Writes a small quantity of text, capitalized, between brackets. |

Each of these commands can be run with various aliases for the formats, detailed in the format chart further below. You can also use the various formats with the `--format` flag.

| Format | Aliases | Typical Color |
|--|--|--|   
| success | `success`, `successln`, `successbg` | Green |
| error | `error`, `errorln`, `errorbg` | Red |
| info | `info`, `infoln`, `infobg` | Blue |
| msg | `msg`, `msgln`, `msgbg` | Light Purple |
| comment | `comment`, `commentln`, `commentbg` | Yellow |


## Info Commands
These commands provide various information about the Mac system. All of this information can be found through various system utilities, however it can be handy to have it all in one place, pre-parsed and ready for use.

All commands can take a `--json` flag which will change the results to JSON.  Additionally, an argument can be given to limit the result to that key & subkeys.  For a full list of the keys available, run the command without any argument.

| Command | Description |
|--|--|
| info:os | Provides information about the version, name, and build of the OS.  Additionally, reports any shared and personal caches in use, as well as any custom SUS url. |
| info:app | Provides the version, name, path, identifier, copyright, filename, and path for the given application, or all applications if no argument is given.  Additional information is provided about Adobe Applications. |
| info:hardware | Provides info about the model, cpu, power attributes, and network of the device. |

## Install Commands
These commands help with the download and installation of executables and applications.  Which command is the most useful may depend on the source and format of the download.

### Configured Installer
Installs a pre-configured application, using the latest version available.  Only a limited number of applications are
available using this command, as detailed below.  Application names and details remain subject to the license of the
author, license issuing party, or copyright holder as is appropriate.

This command should only be used for applications where you do not need to control the version of the application that is installed.

For most applications, nothing is downloaded if the _current_ version of the application matches the _installed_ version.  Applications where the _current_ version cannot be obtained from the application's website or other public source are the exception.

Example usage:

    jez install:configured <slug>

| Application | Installer Slug |
|--|--|
| Adobe Reader DC | adobe-reader-dc |
| Basecamp3 | basecamp |
| Docker Desktop for Mac | docker |
| Mozilla Firefox | firefox |
| Mozilla Firefox Developer Edition | firefox-developer |
| Google Chrome | google-chrome |
| Hyper | hyper |
| iTerm2 | iterm2 |
| Jamf PPPC Utility | jamf-pppc-utility |
| Jetbrains PhpStorm | jetbrains-php-storm |
| Microsoft OneDrive | microsoft-one-drive |
| Microsoft Teams | microsoft-teams |
| OpenVPN Connect | open-vpn-connect | 
| Platypus | platypus |
| Rectangle | rectangle |
| Slack | slack |
| Sourcetree | sourcetree |
| Thunderbird | thunderbird |
| Visual Studio Code | visual-studio-code |

### Github Installer
Downloads a file from a GitHub Repo's release, and installs it in a given path.  Existing files are not overwritten unless the `--overwrite` flag or the `--installed` flag are given. When provided with the version currently installed using the `--installed` flag, this command will check the latest release to determine if an update is needed.

Example Usage:

    jez install:github <destination> --repo=<repo> --file=<file_in_release>
    jez install:github /usr/local/bin/jez --repo=deviscoding/jss-easy --file=jez.phar

The permissions of downloaded files are set to be executable (0755).

### PKG Installer
Downloads a PKG file, installs, and confirms the target file or macOS app bundle is present. When the `--target` flag is given, and the destination is a macOS app bundle, the version is compared _before_ the download to verify that an update is needed.  The version is also verified _after_ installation.

Example usage:

    jez install:pkg <destination> <url>

Existing files are not overwritten unless the one of the following is true:

* The `--target` flag is given, the destination is a macOS application bundle, and the target version is newer.
* The `--overwrite` flag is given.
* The `--target` and `--installed` flags are given, and the target version is newer than the installed version.

After the installation, the downloaded PKG file is removed.

### DMG Installer
Downloads a DMG file, mounts the DMG, then inspects the contents to determine what is needed, using the logic in the table below.

Example usage:

    jez install:dmg <destination> <url>

| DMG Contains | Action |
|--|--|
| macOS App Bundle (.app) | If filename matches the _destination_, the downloaded app version is compared to the _destination_ version if present. If the destination does not exist or is an older version, the new app is copied to the _destination_. |
| Single PKG File | PKG file is installed using the same criteria as the PKG installer command above. After installation, if a `--target` version is given & _destination_ is an app, the version is verified. |
| Other File Types | If a filename matches the _destination_, that file is copied to destination unless already present.  Files are overwritten only if the `--overwrite` flag is given. |
| Multiple PKG Files | No Action Taken |

After the installation, the volume is unmounted, and the DMG file is removed.

## Adobe Applications Functionality

The Adobe commands can back up preferences for Adobe Creative Cloud applications, or transfer preferences between different _years_ of the same Creative Cloud application.

For all of these commands, the application name should be provided in lowercase, using a dash as a separator, for example: _after-effects_.

#### adobe:backup _app_ _year_
Backs up the preferences of an Adobe Creative Cloud application.  While the application does not need to be installed, it can be helpful.  Preferences are backed up to the path below, and stored in time/date stamped zip files.

`/Users/<user>/Library/Preferences/MDM Easy/CC/<app>/<year>`

The year is optional for the applications that do not use it, such as XD, Dimension, and Lightroom.

#### adobe:transfer _app_
Copies preferences from one _year_ of a Creative Cloud application to another _year_.  This is intended for situations in which more than one _year_ of an application are installed side-by-side, such as both Photoshop 2020 and 2021.

_It should be noted that this is purely a  "copy" process; no changes are made to the files,  and no attempt is made to migrate changed or deprecated preferences.  This has worked well in testing, but may not work with all future preferences or applications.  As such, a backup of the existing preferences is always made during the copy process_.

|Flags  | Purpose |  
|--|--|  
| from | The source year, IE - 2019 |  
| to | The destination year, IE - 2020 |  
| user | This defaults to the current user, which makes it important to include this flag when running this tool as root. |

## Wait Command

This command will wait until a number of conditions are false, or until the number of seconds given has passed.  This is useful for scripts in which you do not want to run the action while one of the configured conditions is present, for instance any script which may require a reboot.

The command can test for High CPU load, console user, battery power, screen sleep prevention (video conferencing), and an in-progress FileVault encryption.  If no condition flags are given, all conditions are tested for.

Example usage:

    jez wait 30 --filevault --power --cpu

|Flags  | Purpose |  
|--|--|  
| user | Wait for there to be no console user present |  
| filevault | Wait until no FileVault encryption is in-progress |  
| power | Wait until the computer is on AC power |
| screen | Wait until screen sleep is not prevented |
| cpu | Wait until there is no high CPU load |
| json | Output results in JSON format |

## Ownership Command

This command set the ownership of the given file to the same owner & group as a user's home directory.  Note that symlinks are ignored by this command.

Example usage:

    jez chown:user /Users/bobm/Library/Preferences/com.apple.caramel.plist --user bobm

| Flag | Purpose |
|--|--|  
| --user | Specifies the user to set permissions from |
| --console | Uses the user currently logged into the GUI console |
| --recursive | Performs the command recursively |

## Menu Command
This command will add a menu to a user's menubar, and works with any of the .menu bundles located in

    /System/Library/CoreServices/Menu Extras

Example Usage:

    jez menu:add VPN --user kevins

|Flag  | Purpose |  
|--|--|  
| --user | The user to add the menu for.  This defaults to the current console user. |


## Can you add...
If there is functionality that you constantly repeat in your MDM scripting, it may be useful to add to this application.  Open an issue with your use case, and an example of the code! Requests will be evaluated on a case-by-case basis.


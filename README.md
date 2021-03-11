# JSS Helper
In developing scripts for a fleet of Mac computers using Jamf Pro.  I found that I often needed the same functionality in many scripts.  As Jamf does not offer any sort of shared script library, that often led to repeating blocks of code and updating many scripts if changes were needed. The JSS Helper application was created to streamline scripts by providing assistance with three types of functionality:

* Downloads & Installations
* Script Output
* Information Gathering

While it was designed for use with Jamf Pro, it may be used with or MDM systems, or without any MDM system.

## Installation, Dependencies, and Compatibility

For normal installation, simply download the  `jhelper.phar` to your utility path, and rename it, for example `/usr/local/bin/jhelper`.  For installation on client systems, it is recommended that you use a policy with a script.  See the `/resources` directory for an example.

At this time, JSS Helper has no dependencies when used in macOS High Sierra, Mojave, Catalina, or Big Sur

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
| info:app | Provides the version, name, path, identifier, copyright, filename, and path for the given application, or all applications if no argument is given. |
| info:hardware | Provides info about the model, cpu, and network of the device. |

## Install Commands
These commands help with the download and installation of executables and applications.  Which command is the most useful may depend on the source and format of the download.

### Github Installer
Downloads a file from a GitHub Repo's release, and installs it in a given path.  Existing files are not overwritten unless the `--overwrite` flag or the `--installed` flag are given. When provided with the version currently installed using the `--installed` flag, this command will check the latest release to determine if an update is needed.

    jhelper install:github <destination> --repo=<repo> --file=<file_in_release>
    jhelper install:github /usr/local/bin/jhelper --repo=deviscoding/jss-helper --file=jhelper.phar

The permissions of downloaded files are set to be executable (0755).

### PKG Installer
Downloads a PKG file, installs, and confirms the target file or macOS app bundle is present. When the `--target` flag is given, and the destination is a macOS app bundle, the version is compared _before_ the download to verify that an update is needed.  The version is also verified _after_ installation.

Existing files are not overwritten unless the one of the following is true:

* The `--target` flag is given, the destination is a macOS application bundle, and the target version is newer.
* The `--overwrite` flag is given.
* The `--target` and `--installed` flags are given, and the target version is newer than the installed version.

After the installation, the downloaded PKG file is removed.

### DMG Installer
Downloads a DMG file, mounts the DMG, then inspects the contents to determine what is needed, using the following logic

| DMG Contains | Action |
|--|--|
| macOS App Bundle (.app) | If filename matches the _destination_, the downloaded app version is compared to the _destination_ version if present. If the destination does not exist or is an older version, the new app is copied to the _destination_. |
| Single PKG File | PKG file is installed using the same criteria as the PKG installer command above. After installation, if a `--target` version is given & _destination_ is an app, the version is verified. |
| Other File Types | If a filename matches the _destination_, that file is copied to destination unless already present.  Files are overwritten only if the `--overwrite` flag is given. |
| Multiple PKG Files | No Action Taken |

    jhelper install:dmg <destination> <url>

After the installation, the volume is unmounted, and the DMG file is removed.

## Can you add...
If there is functionality that you constantly repeat in your MDM scripting, it may be useful to add to this application.  Open an issue with your use case, and an example of the code! Requests will be evaluated on a case-by-case basis.


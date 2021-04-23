#!/bin/bash

### region ############################################ DocBlock
#
# Installs the latest Google Chrome Enterprise version, using
# JHelper for the heavy lifting.
#
# Credit to Joe Farage for the concepts in the version function(s)
# which were adapted from his scripts, written 3/18/2015.
#
### endregion ######################################### DocBlock

### region ############################################ Dependencies

([ -z "$(which jhelper)" ] && jamf policy --trigger InstallJHelper) && ([ -z "$(which jhelper)" ] && echo "ERROR: JHelper Not Installed") && exit 1

# shellcheck source=_jhelper.sh
source "$(jhelper prep)"

### endregion ######################################### Dependencies

### region ############################################ Functions

function getLatestVersion() {
  local OSvers_URL userAgent lVersion
  ## Get OS version and adjust for use with the URL string
  OSvers_URL=$( sw_vers -productVersion | sed 's/[.]/_/g' )

  ## Set the User Agent string for use with curl
  userAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X ${OSvers_URL}) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2"

  # Get the latest version of Firefox available from Firefox page.
  lVersion=$(/usr/bin/curl -s -A "$userAgent" https://omahaproxy.appspot.com/json | jq -r ".[] | select(.os==\"mac\").versions[] | select(.channel==\"stable\").current_version" )
  if [ -z "$lVersion" ]; then
    lVersion="ERROR"
  fi

  echo "${lVersion}"
}

### endregion ######################################### Functions

# Display Message
echo ""
$JH_MSG "Checking Current Version"
# Get Current Version
CURRENT=$(getLatestVersion)
# Display Badge & Exit if No Version Given
([ -n "${CURRENT}" ] && $JH_SUCCESS) || ($JH_ERROR && exit 1)

# Set Variables
APP_URL='https://dl.google.com/chrome/mac/stable/gcem/GoogleChrome.pkg'
APP_PATH="/Applications/Google Chrome.app"

# Install DMG
if $JHELPER install:pkg "${APP_PATH}" "${APP_URL}" --target="${CURRENT}"; then
  echo "" && exit 0
else
  echo "" && exit 1
fi
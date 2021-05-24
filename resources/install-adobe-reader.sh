#!/bin/bash

### region ############################################ DocBlock
#
# Installs the latest Adobe Acrobat Reader DC version, using
# JHelper for the heavy lifting.
#
# Credit to Joe Farage for the concepts in the version function(s)
# which were adapted from his scripts, written 3/18/2015.
#
### endregion ######################################### DocBlock

### region ############################################ Dependencies

([ -z "$(which jez)" ] && jamf policy --trigger InstallJez) && ([ -z "$(which jez)" ] && echo "ERROR: JEZ Not Installed") && exit 1

# shellcheck source=_JEZ.sh
source "$(jez prep)"

### endregion ######################################### Dependencies

### region ############################################ Functions

function getLatestVersion() {
  local OSvers_URL userAgent latestver latestvernorm i

	## Get OS version and adjust for use with the URL string
	OSvers_URL=$( sw_vers -productVersion | sed 's/[.]/_/g' )

	## Set the User Agent string for use with curl
	userAgent="Mozilla/5.0 (Macintosh; Intel Mac OS X ${OSvers_URL}) AppleWebKit/535.6.2 (KHTML, like Gecko) Version/5.2 Safari/535.6.2"

	# Get the latest version of Adobe Reader DC available from Adobe Reader page.
  for i in {1..5}
  do
    if [ -z "$latestver" ]; then
      latestver=$(curl -LSs -A "$userAgent" "https://get.adobe.com/reader" | grep "id=\"AUTO_ID_columnleft_p_version\"" | awk -F '\\>Version ' '{print $NF}' | awk -F\< '{print $1}')
    fi
  done

  if [ -n "$latestver" ]; then
    latestvernorm=$(echo "${latestver}" | sed -e 's/20//')
    echo "$latestvernorm"
  fi

  return 0
}

function getAdobeReaderUrl() {
  local VERSION NORMALIZED

  VERSION=${1}
  NORMALIZED=$( echo "${VERSION}" | sed -e 's/[.]//g' )

  echo "http://ardownload2.adobe.com/pub/adobe/reader/mac/AcrobatDC/${NORMALIZED}/AcroRdrDC_${NORMALIZED}_MUI.dmg"

  return 0;
}

### endregion ######################################### Functions

# Display Message
echo ""
$JMSG "Checking Current Version"
# Get Current Version
CURRENT=$(getLatestVersion)
# Display Badge & Exit if No Version Given
([ -n "${CURRENT}" ] && $JSUCCESS) || ($JERROR && exit 1)

# Set Variables
APP_PATH="/Applications/Adobe Acrobat Reader DC.app"
APP_URL=$(getAdobeReaderUrl "${CURRENT}")

# Install DMG
if $JEZ install:dmg "${APP_PATH}" "${APP_URL}" --target="${CURRENT}"; then
  echo "" && exit 0
else
  echo "" && exit 1
fi
#!/bin/sh
# plex-install.sh
# Created by: J.M Rivera

# Determine current working directory as absolute path.
CWDIR=$(dirname $(realpath $0))

# Global variables.
PLATFORM=`uname -p`
PRDVERSION=`uname -r | cut -d. -f1`
SCRIPTNAME=`basename $0`

error_notify()
{
    # Logg and notify message on error and exit.
    /usr/bin/logger "${SCRIPTNAME}: an error has occurred during install process"
    echo -e "$*" >&2 ; exit 1
}

fetch_branch()
{
    # Fetch latest master branch, unless other specified.
    if [ "${BRANCH}" = "testing" ]; then
        # Fetch the latest testing branch(for developers/testing).
        echo "=> Retrieving the latest testing branch..."
        /usr/bin/fetch -o ${CWDIR} https://github.com/JRGTH/nas4free-plex-extension/archive/testing.zip || error_notify "Error: A problem has occurred while fetching testing branch."
        /bin/mv ${CWDIR}/testing.zip ${CWDIR}/master.zip
    else
        # Fetch the latest master branch.
        echo "=> Retrieving the latest master branch..."
        /usr/bin/fetch -o ${CWDIR} https://github.com/JRGTH/nas4free-plex-extension/archive/master.zip || error_notify "Error: A problem has occurred while fetching master branch."
    fi
}

install_main()
{
    # Check for system/product compatibility.
    if [ ! ${PLATFORM} == "amd64" ]; then
        echo "Unsupported platform!"; exit 1
    fi
    if [ ! ${PRDVERSION} -ge "10" ]; then
        echo "Unsupported version!"; exit 1
    fi

    # Fetch selected branch, default master.
    fetch_branch

    # Extract the package and exclude unneeded files.
    echo "=> Extracting package files..."
    /usr/bin/tar -xf ${CWDIR}/master.zip --exclude='.git*' --strip-components 1 -C ${CWDIR}/ || error_notify "Error: A problem has occurred while extracting package."

    # Make executable and run plexinit script.
    /bin/chmod 0755 ${CWDIR}/plex/plexinit
    ${CWDIR}/plex/plexinit

    # Check exit status and finish installation.
    if [ $? -eq 0 ]; then
        echo "Plex Extension installed successfully!"
        /usr/bin/logger "${SCRIPTNAME} Plex Extension installed successfully"
        /bin/rm -f "${CWDIR}/master.zip" "${CWDIR}/README.md" "${CWDIR}/plex-install.sh"
    else
        error_notify "Error: A problem has occurred during install process."
    fi
}

# Alternate branches(for developers/testing).
while getopts ":t" option; do
    case ${option} in
        [t]) BRANCH="testing";;
    esac
done

install_main

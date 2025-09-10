XigmaNAS® Plex Extension
========================

This is the XigmaNAS® Plex Extension which integrates Plex Media Server to your Server.


Additional information can be found in the: <a href="https://www.xigmanas.com/forums/viewforum.php?f=32">XigmaNAS Extensions Forum</a>



Manual Installation:

fetch https://raw.githubusercontent.com/JRGTH/xigmanas-plex-extension/master/plex-install.php && mkdir -p ext/plex-install && echo '<a href="plex-install.php">Plex Extension Installer</a>' > ext/plex-install/menu.inc && echo -e "\n=> Done!"

Common management shell options:

xigmanas: ~# plexinit -h
Usage: plexinit -[option] | [path|file]
Options:

-s  Start Plex Media Server.

-p  Stop Plex Media Server.

-r  Restart Plex Media Server.

-u  Upgrade Plex/Extension packages.

-U  Upgrade Extension packages only.

-g  Enables the addon GUI.

        -t  Disable the addon GUI.
        -x  Reset Plex Extension Config.
        -b  Backup Plexdata Directory.
        -f  Restore Plexdata Directory.
        -e  Install/Upgrade Plex package from tarball.
        -i  Install/Upgrade Plex package from pkg tool
        -v  Display product version.
        -h  Display this help message.

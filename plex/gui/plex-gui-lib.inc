<?php
/*
	plex-gui-lib.inc

	Copyright (c) 2018 - 2019 Jose Rivera (JoseMR)
    All rights reserved.

	Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Initialize some variables.
$application = "Plex Media Server";

//$rootfolder = dirname($config['rc']['postinit']['cmd'][$i]);
$pidfile = "/var/run/plex/plex.pid";
$confdir = "/var/etc/plexconf";
$wwwpath = "/usr/local/www";
$cwdir = exec("/usr/bin/grep 'INSTALL_DIR=' {$confdir}/conf/plex_config | cut -d'\"' -f2");
$rootfolder = $cwdir;
$configfile = "{$rootfolder}/conf/plex_config";
$versionfile = "{$rootfolder}/version";
$tarballversion = "{$rootfolder}/plexversion";
$date = strftime('%c');
$logfile = "{$rootfolder}/log/plex_ext.log";
$logevent = "{$rootfolder}/log/plex_last_event.log";

// For NAS4Free 10.x versions.
$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
if ($return_val == 0):
	if (is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ):
		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) { if (preg_match('/plexinit/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
	endif;
endif;

// Set the installed Plex package name.
$return_val = mwexec("/usr/bin/grep 'PLEX_CHANNEL=\"plexpass\"' {$confdir}/conf/plex_config", true);
if ($return_val == 0):
	$prdname = "plexmediaserver-plexpass";
else:
	$prdname = "plexmediaserver";
endif;

if ($rootfolder == ""):
	$input_errors[] = gtext("Extension installed with fault");
else:
	// Initialize locales.
	$textdomain = "/usr/local/share/locale";
	$textdomain_plex = "/usr/local/share/locale-plex";
	if (!is_link($textdomain_plex)) { mwexec("ln -s {$rootfolder}/locale-plex {$textdomain_plex}", true); }
	bindtextdomain("xigmanas", $textdomain_plex);
endif;

if (is_file("{$rootfolder}/postinit")) unlink("{$rootfolder}/postinit");

// Set default backup directory.
if (1 == mwexec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR='")):
	if (is_file("{$configfile}")) exec("/usr/sbin/sysrc -f {$configfile} BACKUP_DIR={$rootfolder}/backup");
endif;
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");

// Retrieve IP@.
$ipaddr = get_ipaddr($config['interfaces']['lan']['if']);
$url = htmlspecialchars("http://{$ipaddr}:32400/web");
$ipurl = "<a href='{$url}' target='_blank'>{$url}</a>";

?>

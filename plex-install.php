<?php
/* 
	plex-install.php

	Plex* Extension Installer for the NAS4Free "Plex Media Server*" add-on created by J.M Rivera.
	(http://forums.nas4free.org/viewtopic.php?f=71&t=11049)
	*Plex(c) (Plex Media Server) is a registered trademark of Plex(c), Inc.

	Installer based on OneButtonInstaller(OBI.php) NAS4Free extension created by Andreas Schmidhuber(crest).
	Credits to Andreas Schmidhuber(crest).

	Copyright (c) 2015 - 2016 Andreas Schmidhuber
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

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the FreeBSD Project.
 */
require("auth.inc");
require("guiconfig.inc");

$application = "Plex Media Server";
$pgtitle = array(gtext("Extensions"), gtext($application), gtext("Installation Directory"));
if (!isset($config['plex']) || !is_array($config['plex'])) $config['plex'] = array();

/*
Check if the directory exists, the mountpoint has at least o=rx permissions and
set the permission to 775 for the last directory in the path.
 */
function change_perms($dir) {
	global $input_errors;

	$path = rtrim($dir,'/');   // Remove trailing slash.
	if (strlen($path) > 1) {
		if (!is_dir($path)) {   // Check if directory exists.
			$input_errors[] = sprintf(gtext("Directory %s doesn't exist!"), $path);
		}
		else {
			$path_check = explode("/", $path);   // Split path to get directory names.
			$path_elements = count($path_check);   // Get path depth.
			$fp = substr(sprintf('%o', fileperms("/$path_check[1]/$path_check[2]")), -1);   // Get mountpoint permissions for others.
			if ($fp >= 5) {   // Some  applications needs at least read & search permission at the mountpoint.
				$directory = "/$path_check[1]/$path_check[2]";   // Set to the mountpoint.
				for ($i = 3; $i < $path_elements - 1; $i++) {   // Traverse the path and set permissions to rx.
					$directory = $directory."/$path_check[$i]";   // Add next level.
					exec("chmod o=+r+x \"$directory\"");   // Set permissions to o=+r+x.
				}
				$path_elements = $path_elements - 1;
				$directory = $directory."/$path_check[$path_elements]";   // Add last level.
				exec("chmod 775 {$directory}");   // Set permissions to 775.
				exec("chown {$_POST['who']} {$directory}*");
			}
			else {
				$input_errors[] = sprintf(gtext("%s needs at least read & execute permissions at the mount point for directory %s! Set the Read and Execute bits for Others (Access Restrictions | Mode) for the mount point %s (in <a href='disks_mount.php'>Disks | Mount Point | Management</a> or <a href='disks_zfs_dataset.php'>Disks | ZFS | Datasets</a>) and hit Save in order to take them effect."), $application, $path, "/{$path_check[1]}/{$path_check[2]}");
			}
		}
	}
}

if (isset($_POST['save-install']) && $_POST['save-install']) {
	unset($input_errors);
	if (empty($input_errors)) {
		$config['plex']['storage_path'] = !empty($_POST['storage_path']) ? $_POST['storage_path'] : $g['media_path'];
		$config['plex']['storage_path'] = rtrim($config['plex']['storage_path'],'/');   // Ensure to have NO trailing slash.
		if (!isset($_POST['path_check']) && (strpos($config['plex']['storage_path'], "/mnt/") === false)) {
			$input_errors[] = gtext("The common directory for Plex Extension MUST be set to a directory below '/mnt/' to prevent to loose the extension after a reboot on embedded systems!");
		}
		else {
			if (!is_dir($config['plex']['storage_path'])) mkdir($config['plex']['storage_path'], 0775, true);
			change_perms($config['plex']['storage_path']);
			$config['plex']['path_check'] = isset($_POST['path_check']) ? true : false;
			$install_dir = $config['plex']['storage_path']."/";   // Get directory where the installer script resides.
			//if (!is_dir("{$install_dir}plex/log")) { mkdir("{$install_dir}plex/log", 0775, true); }
			$return_val = mwexec("fetch {$verify_hostname} -vo {$install_dir}plex/plexinit 'https://raw.githubusercontent.com/JRGTH/nas4free-plex-extension/testing/plex/plexinit'", true);
			if ($return_val == 0) {
				// Perform cleanup for obsolete files on upgrades.
				if (is_file("plex-gui.php")) {
					if (is_file("{$install_dir}plex/version")) unlink("{$install_dir}plex/version");
					if (is_dir("{$install_dir}plex/conf")) exec("rm -rf {$install_dir}plex/conf");
					if (is_dir("{$install_dir}plex/gui")) exec("rm -rf {$install_dir}plex/gui");
					if (is_dir("{$install_dir}plex/locale-plex")) exec("rm -rf {$install_dir}plex/locale-plex");
					if (is_dir("ext/plex-gui")) exec("rm -rf ext/plex-gui");
					if (is_file("plex-gui.php")) unlink("plex-gui.php");
				}
				exec("sh {$install_dir}plex/plexinit -o");
				exec("php {$install_dir}plex/postinit");
				if (is_file("{$install_dir}plex/postinit")) unlink("{$install_dir}plex/postinit");
			}
			else {
				$input_errors[] = sprintf(gtext("Installation file %s not found, installation aborted!"), "{$install_dir}plex/plexinit");
				return;
			}
			mwexec("rm -rf ext/plex-install; rm -f plex-install.php", true);
			header("Location:plex-gui.php");
		}
	}
}

if (isset($_POST['cancel']) && $_POST['cancel']) {
	$return_val = mwexec("rm -rf ext/plex-install; rm -f plex-install.php", true);
	if ($return_val == 0) { $savemsg .= $application." ".gtext("not installed"); }
	else { $input_errors[] = $application." removal failed"; }
	header("Location:index.php");
}

$pconfig['storage_path'] = !empty($config['plex']['storage_path']) ? $config['plex']['storage_path'] : $g['media_path'];
$pconfig['path_check'] = isset($config['plex']['path_check']) ? true : false;

include("fbegin.inc"); ?>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<form action="plex-install.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr><td class="tabcont">
		<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
		<?php if (!empty($savemsg)) print_info_box($savemsg);?>
		<table width="100%" border="0" cellpadding="6" cellspacing="0">
			<?php html_titleline($application);?>
			<?php html_filechooser("storage_path", gtext("Common directory"), $pconfig['storage_path'], gtext("Common directory for the Plex Extension, a persistent place where the extension should installed, a directory below /mnt/."), $pconfig['storage_path'], true, 60);?>
			<?php html_checkbox("path_check", gtext("Path check"), $pconfig['path_check'], gtext("If this option is selected no examination of the common directory path will be carried out, whether it was set to a directory below /mnt/."), "<b><font color='red'>".gtext("Please use this option only if you know what you are doing!")."</font></b>", false);?>
		</table>
		<div id="submit">
			<input id="save-install" name="save-install" type="submit" class="formbtn" value="<?=gtext("Save & Install");?>" onclick="return confirm('<?=gtext("Ready to install Plex Media Server Extension?");?>')" />
			<input id="cancel" name="cancel" type="submit" class="formbtn" value="<?=gtext("Cancel");?>"/>
		</div>
	</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<?php include("fend.inc");?>

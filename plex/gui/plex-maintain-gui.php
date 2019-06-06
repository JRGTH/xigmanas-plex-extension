<?php
/*
	plex-maintain-gui.php

	Copyright (c) 2018 - 2019 Jose Rivera (JoseMR)
    All rights reserved.

	Portions of XigmaNAS (https://www.xigmanas.com).
	Copyright (c) 2018 XigmaNAS <info@xigmanas.com>.
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
require("auth.inc");
require("guiconfig.inc");
require_once("plex-gui-lib.inc");

$pgtitle = array(gtext("Extensions"), "Plex Media Server", "Maintenance");

if ($_POST):
	if(isset($_POST['upgrade']) && $_POST['upgrade']):
		if (isset($_POST['plex_upgrade'])):
			$cmd = sprintf('%1$s/plexinit -u > %2$s',$rootfolder,$logevent);
		else:
			$cmd = sprintf('%1$s/plexinit -U > %2$s',$rootfolder,$logevent);
		endif;
		$return_val = 0;
		$output = [];
		exec($cmd,$output,$return_val);
		if($return_val == 0):
			ob_start();
			include("{$logevent}");
			$ausgabe = ob_get_contents();
			ob_end_clean(); 
			$savemsg .= str_replace("\n", "<br />", $ausgabe)."<br />";
		else:
			$input_errors[] = gtext('An error has occurred during upgrade process.');
			$cmd = sprintf('echo %s: %s An error has occurred during upgrade process. >> %s',$date,$application,$logfile);
			exec($cmd);
		endif;
	endif;

	if (isset($_POST['restore']) && $_POST['restore']):
		// The restore process is now handled by the plexinit script, also prevent gui hangs during restore process.
		$backup_file = ($_POST['backup_path']);
		$file_match = mwexec("echo {$backup_file} | grep -qw 'plexdata-[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}-[0-9]\{6\}.tar'");
		$folder_match = mwexec("echo {$backup_file} | grep -qw 'plexdata-[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}-[0-9]\{6\}'");
		if (!"{$backup_file}" == "" && "{$file_match}" == 0 || "{$folder_match}" == 0):
			$return_val = mwexec("nohup {$rootfolder}/plexinit -f {$backup_file} >/dev/null 2>&1 &", true);
			if ($return_val == 0):
				$savemsg .= gtext("Plexdata restore process started in the background successfully.");
				//exec("echo '{$date}: Plexdata restore successfully created' >> {$logfile}");
			else:
				$input_errors[] = gtext("Plexdata restore failed.");
				//exec("echo '{$date}: Plexdata restore failed' >> {$logfile}");
			endif;
		else:
			$input_errors[] = gtext("Please select a plexdata file or directory to restore from.");
		endif;
	endif;

	if (isset($_POST['remove']) && $_POST['remove']):
		bindtextdomain("xigmanas", $textdomain);
		if (is_link($textdomain_plex)) mwexec("rm -f {$textdomain_plex}", true);
		if (is_dir($confdir)) mwexec("rm -rf {$confdir}", true);
		mwexec("rm {$wwwpath}/plex-gui.php && {$wwwpath}/plex-gui-lib.inc && {$wwwpath}/plex-maintain-gui.php && rm -R {$wwwpath}/ext/plex-gui", true);
		mwexec("{$rootfolder}/plexinit -t", true);
		exec("echo '{$date}: Extension GUI successfully removed' >> {$logfile}");
		header("Location:index.php");
	endif;

	// Remove only extension related files during cleanup.
	if (isset($_POST['uninstall']) && $_POST['uninstall']):
		bindtextdomain("xigmanas", $textdomain);
		if (is_link($textdomain_plex)) mwexec("rm -f {$textdomain_plex}", true);
		if (is_dir($confdir)) mwexec("rm -rf {$confdir}", true);
		mwexec("rm {$wwwpath}/plex-gui.php && {$wwwpath}/plex-gui-lib.inc && {$wwwpath}/plex-maintain-gui.php && rm -R {$wwwpath}/ext/plex-gui", true);
		mwexec("{$rootfolder}/plexinit -t", true);
		mwexec("{$rootfolder}/plexinit -p && rm -f {$pidfile}", true);
		mwexec("pkg delete -y -f -q {$prdname}", true);
		if (isset($_POST['plexdata'])):
			$uninstall_plexdata = "{$rootfolder}/plexdata {$rootfolder}/plexdata-*";	
		else:
			$uninstall_plexdata = "";
		endif;
		$uninstall_cmd = "rm -rf {$rootfolder}/backup {$rootfolder}/conf {$rootfolder}/gui {$rootfolder}/locale-plex {$rootfolder}/log {$uninstall_plexdata} {$rootfolder}/system {$rootfolder}/plexinit {$rootfolder}/README {$rootfolder}/release_notes {$rootfolder}/version";
		mwexec($uninstall_cmd, true);
		if (is_link("/usr/local/share/{$prdname}")) mwexec("rm /usr/local/share/{$prdname}", true);
		if (is_link("/var/cache/pkg")) mwexec("rm /var/cache/pkg", true);
		if (is_link("/var/db/pkg")) mwexec("rm /var/db/pkg && mkdir /var/db/pkg", true);

		// Remove postinit cmd in NAS4Free 10.x versions.
		$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
		if ($return_val == 0):
			if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])):
				for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) { if (preg_match('/plexinit/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]); } ++$i; }
			endif;
			write_config();
		endif;

		// Remove postinit cmd in NAS4Free later versions.
		if (is_array($config['rc']) && is_array($config['rc']['param'])):
			$postinit_cmd = "{$rootfolder}/plexinit";
			$value = $postinit_cmd;
			$sphere_array = &$config['rc']['param'];
			$updateconfigfile = false;
			if (false !== ($index = array_search_ex($value, $sphere_array, 'value'))):
				unset($sphere_array[$index]);
				$updateconfigfile = true;
			endif;
			if ($updateconfigfile):
				write_config();
				$updateconfigfile = false;
			endif;
		endif;
		header("Location:index.php");
	endif;
endif;

// Update some variables.
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");

function get_version_plex() {
	global $tarballversion, $prdname;
	if (is_file("{$tarballversion}")) {
		exec("/bin/cat {$tarballversion}", $result);
		return ($result[0]);
	}
	else {
		exec("/usr/local/sbin/pkg info -I {$prdname}", $result);
		return ($result[0]);
	}
}

function get_version_ext() {
	global $versionfile;
	exec("/bin/cat {$versionfile}", $result);
	return ($result[0]);
}

function get_process_info() {
	global $pidfile;
	if (exec("/bin/ps acx | /usr/bin/grep -f {$pidfile}")) { $state = '<a style=" background-color: #00ff00; ">&nbsp;&nbsp;<b>'.gtext("running").'</b>&nbsp;&nbsp;</a>'; }
	else { $state = '<a style=" background-color: #ff0000; ">&nbsp;&nbsp;<b>'.gtext("stopped").'</b>&nbsp;&nbsp;</a>'; }
	return ($state);
}

bindtextdomain("xigmanas", $textdomain);
include("fbegin.inc");
bindtextdomain("xigmanas", $textdomain_plex);
?>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<script type="text/javascript">
<!--
//-->
</script>
<form action="plex-maintain-gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr><td class="tabnavtbl">
    		<ul id="tabnav">
    			<li class="tabinact"><a href="plex-gui.php"><span><?=gettext("Plex");?></span></a></li>
    			<li class="tabact"><a href="plex-maintain-gui.php"><span><?=gettext("Maintenance");?></span></a></li>
    		</ul>
    	</td></tr>
		<tr><td class="tabcont">
			<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
			<?php if (!empty($savemsg)) print_info_box($savemsg);?>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_titleline(gtext("Upgrade & Restore"));?>
				<?php html_text("installation_directory", gtext("Installation directory"), sprintf(gtext("The extension is installed in %s"), $rootfolder));?>
				<?php html_filechooser("backup_path", gtext("Plexdata archive"), "", gtext("Select a previous plexdata backup file or directory to restore from."), $backup_path, true, 60);?>
				<?php html_checkbox("plex_upgrade", gtext("Plex package upgrade"), false, "<font color='red'>".gtext("Upgrade the Plex Media Server component.")."</font>", sprintf(gtext("If not activated, only the extension files will be upgraded."), ""), false);?>
			</table>
			<div id="submit">
				<input name="upgrade" type="submit" class="formbtn" title="<?=gtext("Upgrade Extension and Plex Packages");?>" value="<?=gtext("Upgrade");?>" />
				<input name="restore" type="submit" class="formbtn" title="<?=gtext("Restore Plexdata Folder");?>" value="<?=gtext("Restore");?>" onclick="return confirm('<?=gettext("Do you really want to restore plex configuration from the selected file?");?>')" />
			</div>
			<div id="remarks">
				<?php html_remark("note", gtext("Note"), sprintf(gtext("Use the %s button to restore plexdata folder from the selected item."), gtext("Restore")));?>
			</div>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_separator();?>
				<?php html_titleline(gtext("Uninstall"));?>
				<?php html_checkbox("plexdata", gtext("Plexdata"), false, "<font color='red'>".gtext("Activate to delete user data (metadata and configuration) as well during the uninstall process.")."</font>", sprintf(gtext("If not activated the directory %s remains intact on the server."), "{$rootfolder}/plexdata"), false);?>
				<?php html_separator();?>
			</table>
			<div id="submit1">
				<input name="remove" type="submit" class="formbtn" title="<?=gtext("Disable Plex Extension GUI");?>" value="<?=gtext("Disable");?>" onclick="return confirm('<?=gtext("Plex Extension GUI will be disabled, ready to proceed?");?>')" />
				<input name="uninstall" type="submit" class="formbtn" title="<?=gtext("Uninstall Extension and Plex Media Server completely");?>" value="<?=gtext("Uninstall");?>" onclick="return confirm('<?=gtext("Plex Extension and Plex packages will be completely removed, ready to proceed?");?>')" />
			</div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
//-->
</script>
<?php include("fend.inc");?>

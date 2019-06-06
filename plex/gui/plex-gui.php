<?php
/*
	plex-gui.php

	WebGUI wrapper for the NAS4Free/XigmaNAS "Plex Media Server*" add-on created by JoseMR.
	(https://www.xigmanas.com/forums/viewtopic.php?f=71&t=11184)
	*Plex(c) (Plex Media Server) is a registered trademark of Plex(c), Inc.

	Copyright (c) 2016 Andreas Schmidhuber
	All rights reserved.

	Portions of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2016 The NAS4Free Project <info@nas4free.org>.
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
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");
require_once("plex-gui-lib.inc");

$pgtitle = array(gtext("Extensions"), "Plex Media Server");

if ($_POST):
	if (isset($_POST['start']) && $_POST['start']):
		$running = mwexec("/bin/ps -acx | /usr/bin/grep -q '{$application}'", true);
		if ($running == 1):
			$return_val = mwexec("{$rootfolder}/plexinit -s", true);
			if ($return_val == 0):
				$savemsg .= gtext("Plex Media Server started successfully.");
				exec("echo '{$date}: {$application} successfully started' >> {$logfile}");
			else:
				$input_errors[] = gtext("Plex Media Server startup failed.");
				exec("echo '{$date}: {$application} startup failed' >> {$logfile}");
			endif;
		else:
			$savemsg .= gtext("Plex Media Server already started.");
		endif;
	endif;

	if (isset($_POST['stop']) && $_POST['stop']):
		$running = mwexec("/bin/ps -acx | /usr/bin/grep -q '{$application}'", true);
		if ($running == 0):
			$return_val = mwexec("{$rootfolder}/plexinit -p", true);
			if ($return_val == 0):
				$savemsg .= gtext("Plex Media Server stopped successfully.");
				exec("echo '{$date}: {$application} successfully stopped' >> {$logfile}");
			else:
				$input_errors[] = gtext("Plex Media Server stop failed.");
				exec("echo '{$date}: {$application} stop failed' >> {$logfile}");
			endif;
		else:
			$savemsg .= gtext("Plex Media Server already stopped.");
		endif;
	endif;

	if (isset($_POST['restart']) && $_POST['restart']):
		$return_val = mwexec("{$rootfolder}/plexinit -r", true);
		if ($return_val == 0):
			$savemsg .= gtext("Plex Media Server restarted successfully.");
			exec("echo '{$date}: {$application} successfully restarted' >> {$logfile}");
		else:
			$input_errors[] = gtext("Plex Media Server restart failed.");
			exec("echo '{$date}: {$application} restart failed' >> {$logfile}");
		endif;
	endif;

	if (isset($_POST['backup']) && $_POST['backup']):
		// The backup process is now handled by the plexinit script, also prevent gui hangs during backup process.
		$return_val = mwexec("nohup {$rootfolder}/plexinit -b >/dev/null 2>&1 &", true);
		if ($return_val == 0):
			//$savemsg .= gtext("Plexdata backup created successfully in {$backup_path}.");
			$savemsg .= gtext("Plexdata backup process started in the background successfully.");
			//exec("echo '{$date}: Plexdata backup successfully created' >> {$logfile}");
		else:
			$input_errors[] = gtext("Plexdata backup failed.");
			//exec("echo '{$date}: Plexdata backup failed' >> {$logfile}");
		endif;
	endif;

	if (isset($_POST['save']) && $_POST['save']):
		// Ensure to have NO whitespace & trailing slash.
		$backup_path = rtrim(trim($_POST['backup_path']),'/');
		if ("{$backup_path}" == ""):
			$backup_path = "{$rootfolder}/backup";
		else:
			exec("/usr/sbin/sysrc -f {$configfile} BACKUP_DIR={$backup_path}");
		endif;

		if (isset($_POST['enable'])):
			exec("/usr/sbin/sysrc -f {$configfile} PLEX_ENABLE=YES");
			$running = mwexec("/bin/ps -acx | /usr/bin/grep -q '{$application}'", true);
			if ($running == 1):
				mwexec("{$rootfolder}/plexinit -s", true);
				$savemsg .= gtext("Extension settings saved and enabled.");
				exec("echo '{$date}: Extension settings saved and enabled' >> {$logfile}");
			endif;
		else:
			exec("/usr/sbin/sysrc -f {$configfile} PLEX_ENABLE=NO");
			$running = mwexec("/bin/ps -acx | /usr/bin/grep -q '{$application}'", true);
			if ($running == 0):
				$return_val = mwexec("{$rootfolder}/plexinit -p && rm -f {$pidfile}", true);
				if ($return_val == 0):
					$savemsg .= gtext("Plex Media Server stopped successfully.");
					exec("echo '{$date}: Extension settings saved and disabled' >> {$logfile}");
				else:
					$input_errors[] = gtext("Plex Media Server stop failed.");
					exec("echo '{$date}: {$application} stop failed' >> {$logfile}");
				endif;
			endif;
		endif;
	endif;
endif;

// Update some variables.
$plexenable = exec("/bin/cat {$configfile} | /usr/bin/grep 'PLEX_ENABLE=' | cut -d'\"' -f2");
$backup_path = exec("/bin/cat {$configfile} | /usr/bin/grep 'BACKUP_DIR=' | cut -d'\"' -f2");

function get_version_plex() {
	global $tarballversion, $prdname;
	if (is_file("{$tarballversion}")):
		exec("/bin/cat {$tarballversion}", $result);
		return ($result[0]);
	else:
		exec("/usr/local/sbin/pkg info -I {$prdname}", $result);
		return ($result[0]);
	endif;
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

function get_process_pid() {
	global $pidfile;
	exec("/bin/cat {$pidfile}", $state); 
	return ($state[0]);
}

if (is_ajax()) {
	$getinfo['info'] = get_process_info();
	$getinfo['pid'] = get_process_pid();
	$getinfo['plex'] = get_version_plex();
	$getinfo['ext'] = get_version_ext();
	render_ajax($getinfo);
}

bindtextdomain("xigmanas", $textdomain);
include("fbegin.inc");
bindtextdomain("xigmanas", $textdomain_plex);
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'plex-gui.php', null, function(data) {
		$('#getinfo').html(data.info);
		$('#getinfo_pid').html(data.pid);
		$('#getinfo_plex').html(data.plex);
		$('#getinfo_ext').html(data.ext);
	});
});
//]]>
</script>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<script type="text/javascript">
<!--
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
	document.iform.start.disabled = endis;
	document.iform.stop.disabled = endis;
	document.iform.restart.disabled = endis;
	document.iform.backup.disabled = endis;
	document.iform.backup_path.disabled = endis;
	document.iform.backup_pathbrowsebtn.disabled = endis;
}
//-->
</script>
<form action="plex-gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr><td class="tabnavtbl">
    		<ul id="tabnav">
    			<li class="tabact"><a href="plex-gui.php"><span><?=gettext("Plex");?></span></a></li>
    			<li class="tabinact"><a href="plex-maintain-gui.php"><span><?=gettext("Maintenance");?></span></a></li>
    		</ul>
    	</td></tr>
		<tr><td class="tabcont">
			<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
			<?php if (!empty($savemsg)) print_info_box($savemsg);?>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_titleline_checkbox("enable", gtext("Plex"), $plexenable == "YES", gtext("Enable"));?>
				<?php html_text("installation_directory", gtext("Installation directory"), sprintf(gtext("The extension is installed in %s"), $rootfolder));?>
				<tr>
					<td class="vncellt"><?=gtext("Plex version");?></td>
					<td class="vtable"><span name="getinfo_plex" id="getinfo_plex"><?=get_version_plex()?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><?=gtext("Extension version");?></td>
					<td class="vtable"><span name="getinfo_ext" id="getinfo_ext"><?=get_version_ext()?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><?=gtext("Status");?></td>
					<td class="vtable"><span name="getinfo" id="getinfo"><?=get_process_info()?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;PID:&nbsp;<span name="getinfo_pid" id="getinfo_pid"><?=get_process_pid()?></span></td>
				</tr>
				<?php html_filechooser("backup_path", gtext("Backup directory"), $backup_path, gtext("Directory to store archive.tar files of the plexdata folder, use as file chooser for restoring from tar file."), $backup_path, true, 60);?>
				<?php html_text("url", gtext("WebGUI")." ".gtext("URL"), $ipurl);?>
			</table>
			<div id="submit">
				<input id="save" name="save" type="submit" class="formbtn" title="<?=gtext("Save settings");?>" value="<?=gtext("Save");?>"/>
				<input name="start" type="submit" class="formbtn" title="<?=gtext("Start Plex Media Server");?>" value="<?=gtext("Start");?>" />
				<input name="stop" type="submit" class="formbtn" title="<?=gtext("Stop Plex Media Server");?>" value="<?=gtext("Stop");?>" />
				<input name="restart" type="submit" class="formbtn" title="<?=gtext("Restart Plex Media Server");?>" value="<?=gtext("Restart");?>" />
				<input name="backup" type="submit" class="formbtn" title="<?=gtext("Backup Plexdata Folder");?>" value="<?=gtext("Backup");?>" />
			</div>
			<div id="remarks">
				<?php html_remark("note", gtext("Note"), sprintf(gtext("Use the %s button to create an archive.tar of the plexdata folder."), gtext("Backup")));?>
			</div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>

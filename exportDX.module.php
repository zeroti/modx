/*
 * ExportDXモジュール Ver 0.02
 *
 * 2009/04/19
 * Property:&hostname=エクスポートホスト名;string;
 */
define("UNIQKEY","EXPORTDX-ZERO");
define("EXPORT_DX_VERSION","0.02");
global $modx,$modx_charset,$base,$_lang,$manager_theme,$SystemAlertMsgQueque,$friendly_url_suffix,$friendly_url_prefix,$incPath,$export_hostname;
$base = MODX_SITE_URL;
$export_hostname = isset($hostname) && !empty($hostname) ? $hostname:$_SERVER['HTTP_HOST'];
$export_path = isset($export_path) ? $export_path : MODX_BASE_PATH."assets/export/";

// Modified for export alias path  2006/3/24 start
function removeDirectoryAll($directory) {
	// if the path has a slash at the end, remove it
	if(substr($directory,-1) == '/') {
		$directory = substr($directory,0,-1);
	}
	// if the path is not valid or is not a directory ...
	if(!file_exists($directory) || !is_dir($directory)) {
		return FALSE;
	} elseif(!is_readable($directory)) {
		return FALSE;
	} else {
		$dh = opendir($directory);
		while (FALSE !== ($file = @readdir($dh))) {
			if($file != '.' && $file != '..') {
				$path = $directory.'/'.$file;
				if(is_dir($path)) {
					// call myself
					removeDirectoryAll($path);
				} else {
					@unlink($path);
				}
			}
		}
		closedir($dh);
	}
	return (@rmdir($directory));
}

function writeAPage($baseURL, $docid, $filepath) {
global $_lang,$base,$export_hostname;


	echo "EXPORT HOST=".$export_hosttname."<br />";
	$client = new Snoopy();
	$client->read_timeout = 5;
	$result = @$client->fetch($baseURL."/index.php?id=$docid&export=".sha1(UNIQKEY)."&exhost=".$export_hostname);
	if ($result!= false)
	{
		$somecontent = $client->results;
		if (!$handle = fopen($filepath, 'w')) {
			echo $_lang['export_site_failed']." Cannot open file ($filepath)<br />";
			return FALSE;
		} else {
			// Write $somecontent to our opened file.
			if(fwrite($handle, $somecontent) === FALSE) {
				echo $_lang['export_site_failed']." Cannot write file.<br />";
				return FALSE;
			}
			fclose($handle);
			echo $_lang['export_site_success']."<br />";
		}
	} else {
		echo $_lang['export_site_failed']." Could not retrieve document.<br />";
	//			return FALSE;
	}
	return TRUE;
}

function getPageName($docid, $alias, $prefix, $suffix) {
	if(empty($alias)) {
		$filename = $prefix.$docid.$suffix;
	} else {
		$pa = pathinfo($alias); // get path info array
		if (!isset($pa['extension']))
		{	$tsuffix = '';
		} else
		{	$tsuffix = !empty($pa['extension']) ? '':$suffix;
		}
		$filename = $prefix.$alias.$tsuffix;
	}
	return $filename;
}

function scanDirectory($path, $files) {
	// if the path has a slash at the end, remove it
	if(substr($path, -1) == '/') {
		$path = substr($path, 0, -1);
	}
	// if the path is not valid or is not a directory ...
	if(!file_exists($path) || !is_dir($path)) {
		return FALSE;
	} elseif(!is_readable($path)) {
		return FALSE;
	} else {
		$dh = opendir($path);
		while (FALSE !== ($filename = @readdir($dh))) {
			if($filename != '.' && $filename != '..' && substr($filename, 1) != '.') {
				if (!in_array($filename, $files)) {
					$file = $path."/".$filename;
					if (is_dir($file)) {
						removeDirectoryAll($file);
					} else {
						@unlink($file);
					}
				}
			}
		}
		closedir($dh);
		return TRUE;
	}
}

function exportDir($dirid, $dirpath, $i) {
	global $_lang;
	global $base;
	global $modx;
	global $limit;
	global $dbase;
	global $table_prefix;
	global $sqlcond;
	global $export_hostname;
	
    $sql = "SELECT id, alias, pagetitle, isfolder, (content = '' AND template = 0) AS wasNull, editedon FROM ".$modx->getFullTableName('site_content')." WHERE parent = ".$dirid." AND ".$sqlcond;
	$rs = $modx->dbQuery($sql);
	
	$dircontent = array();
	while($row = $modx->fetchRow($rs)) {
	    if (!$row['wasNull']) { // needs writing a document
			$docname = getPageName($row['id'], $row['alias'], $modx->config['friendly_url_prefix'], $suffix = $modx->config['friendly_url_suffix']);
			printf($_lang['export_site_exporting_document'], $i++, $limit, $row['pagetitle'], $row['id']);
			$filename = $dirpath.$docname;
			if (is_dir($filename)) {
				removeDirectoryAll($filename);
			}
			if (!file_exists($filename) || (filemtime($filename) < $row['editedon'])) {
				if (!writeAPage($base, $row['id'], $filename)) exit;
			} else {
				echo $_lang['export_site_success']." Skip this document.<br />";
			}
			$dircontent[] = $docname;
		}
		if ($row['isfolder']) { // needs making a folder
			$dirname = $dirpath.$row['alias'];
			if (!is_dir($dirname)) {
				if (file_exists($dirname)) @unlink($dirname);
				mkdir($dirname);
				if ($row['wasNull']) {
					printf($_lang['export_site_exporting_document'], $i++, $limit, $row['pagetitle'], $row['id']);
					echo $_lang['export_site_success']."<br />";
				}
			} else {
				if ($row['wasNull']) {
					printf($_lang['export_site_exporting_document'], $i++, $limit, $row['pagetitle'], $row['id']);
					echo $_lang['export_site_success']." Skip this folder.<br />";
				}
			}
			exportDir($row['id'], $dirname."/", &$i);
			$dircontent[] = $row['alias'];
		}
	}
	// remove No-MODx files/dirs 
	if (!scanDirectory($dirpath, $dircontent)) exit;
//		print_r ($dircontent);
}

/*
 * 画面出力開始
 */
include_once "header.inc.php";
?>
<br />
<div class="sectionHeader"><?php echo $_lang['export_site_html']; ?> MODULE Ver.<?php echo EXPORT_DX_VERSION; ?></div>
<div class="sectionBody">
<?php
if(!isset($_POST['export'])) {
echo $_lang['export_site_message'];
?>
<fieldset style="padding:10px"><legend><?php echo $_lang['export_site']; ?></legend>
<form method="post" name="exportFrm">
<input type="hidden" name="export" value="export" />
<table border="0" cellspacing="0" cellpadding="2" width="400">
  <tr>
    <td valign="top"><b>EXPORT HOST NAME</b></td>
    <td width="30">&nbsp;</td>
    <td><input type="text" name="export_hostname" value="<?php echo $export_hostname; ?>" ></td>
  </tr>
  <tr>
    <td valign="top"><b><?php echo $_lang['export_site_cacheable']; ?></b></td>
    <td width="30">&nbsp;</td>
    <td><input type="radio" name="includenoncache" value="1" checked="checked"><?php echo $_lang['yes'];?><br />
		<input type="radio" name="includenoncache" value="0"><?php echo $_lang['no'];?></td>
  </tr>
  <tr>
    <td><b><?php echo $_lang['export_site_prefix']; ?></b></td>
    <td>&nbsp;</td>
    <td><input type="text" name="prefix" value="<?php echo $friendly_url_prefix; ?>" /></td>
  </tr>
  <tr>
    <td><b><?php echo $_lang['export_site_suffix']; ?></b></td>
    <td>&nbsp;</td>
    <td><input type="text" name="suffix" value="<?php echo $friendly_url_suffix; ?>" /></td>
  </tr>
<?php if(!ini_get('safe_mode') ) : ?>
  <tr>
    <td valign="top"><b><?php echo $_lang['export_site_maxtime']; ?></b></td>
    <td>&nbsp;</td>
    <td><input type="text" name="maxtime" value="60" />
		<br />
		<small><?php echo $_lang['export_site_maxtime_message']; ?></small>
	</td>
  </tr>
<?php else: ?>
<input type="hidden" name="maxtime" value="0" >
<?php endif; ?>
  <tr>
    <td><b>Export Path</b></td>
    <td>&nbsp;</td>
    <td><input type="text" name="export_path" value="<?php echo $export_path; ?>" /></td>
  </tr>
</table>
<p />
<table cellpadding="0" cellspacing="0" class="actionButtons">
	<td id="Button1"><a href="#" onclick="document.exportFrm.submit();"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/save.gif" align="absmiddle"> <?php echo $_lang["export_site_start"]; ?></a></td>
</table>
</form>
</fieldset>

<?php
} else {
    include $incPath."../media/rss/extlib/Snoopy.class.inc";

	$maxtime = $_POST['maxtime'];
	if(!is_numeric($maxtime)) {
		$maxtime = 30;
	}

	if(!ini_get('safe_mode') ) 	@set_time_limit($maxtime);
	$mtime = microtime(); $mtime = explode(" ",$mtime); $mtime = $mtime[1] + $mtime[0]; $exportstart = $mtime;

	$export_path = realpath($_POST['export_path']);
	if (substr($export_path,-1) == '/')
	{	$export_path = substr($export_path,0,-1);
	}
	if (strpos($export_path,MODX_BASE_PATH)===false)
	{
		echo $_lang['export_site_target_unwritable'];
		include "footer.inc.php";
		exit;
	}
	if (!is_dir($export_path)){
		mkdir($export_path);
	}

	if(!is_writable($export_path)) {
		echo $_lang['export_site_target_unwritable'];
		include "footer.inc.php";
		exit;
	}
	$export_path .= '/';
	$export_hostname = htmlentities($_POST['export_hostname']);
    include_once "./processors/cache_sync.class.processor.php";
    $sync = new synccache();
    $sync->setCachepath("../assets/cache/");
    $sync->setReport(true);
    $sync->emptyCache();

	$prefix = $_POST['prefix'];
	$suffix = $_POST['suffix'];

	$noncache = $_POST['includenoncache']==1 ? "" : "AND cacheable=1";

	if($modx->config['friendly_urls']==1 && $modx->config['use_alias_path']==1) {
	    $sqlcond = "deleted=0 AND ((published=1 AND type='document') OR (isfolder=1)) $noncache";
		$sql = "SELECT count(*) as count1 FROM ".$modx->getFullTableName("site_content")." WHERE ".$sqlcond;

		$rs = $modx->dbQuery($sql);
		$row = $modx->fetchRow($rs,'num');
		$prefix = $modx->config['friendly_url_prefix'];
		$suffix = $modx->config['friendly_url_suffix'];
		$limit = $row[0];
		printf($_lang['export_site_numberdocs'], $limit);
		$n = 1;
		exportDir(0, $export_path, &$n);

	} else {
	// Modified for export alias path  2006/3/24 end
		$sql = "SELECT id, alias, pagetitle, (content = '' AND template = 0) AS wasNull FROM ".$modx->getFullTableName('site_content')." WHERE deleted=0 AND published=1 AND type='document' $noncache";
        $rs = $modx->dbQuery($sql);

		$limit = $modx->recordCount($rs);
		printf($_lang['export_site_numberdocs'], $limit);
		for($i=0; $i<$limit; $i++) {

			$row=$modx->fetchRow($rs);
		    if ($row['wasNull'] || $modx->config['error_page'] == $row['id'])
		    {   continue;
		    }
			$id = $row['id'];
			printf($_lang['export_site_exporting_document'], $i, $limit, $row['pagetitle'], $id);
			$alias = $row['alias'];
		
			// Modified for .xml extension 2006/1/18
			//$filename = !empty($alias) ? $prefix.$alias.$suffix : $prefix.$id.$suffix ;
			if(empty($alias)) {
				$filename = $prefix.$id.$suffix;
			} else {
				$pa = pathinfo($alias); // get path info array
				$tsuffix = !empty($pa['extension']) ? '':$suffix;
				$filename = $prefix.$alias.$tsuffix;
			}
			// get the file
            $client = new Snoopy();
            $client->read_timeout = 5;

            $result = @$client->fetch("$base/index.php?id=$id&export=".sha1(UNIQKEY)."&exhost=".$export_hostname);
    	    if ($result != false)
		    {	// save it
	            $somecontent = $client->results;
				$filename = "$export_path$filename";
				if(!$handle = fopen($filename, 'w')) {
					echo $_lang['export_site_failed']." Cannot open file ($filename)<br />";
					exit;
				} else {
					// Write $somecontent to our opened file.
					if(fwrite($handle, $somecontent) === FALSE) {
						echo $_lang['export_site_failed']." Cannot write file.<br />";
						exit;
					}
					fclose($handle);
					echo $_lang['export_site_success']."<br />";
				}
			} else {
				echo $_lang['export_site_failed']." Could not retrieve document.<br />";
			}
		}
	}

	$mtime = microtime(); $mtime = explode(" ",$mtime); $mtime = $mtime[1] + $mtime[0]; $exportend = $mtime;
	$totaltime = ($exportend - $exportstart);
	printf ("<p />".$_lang['export_site_time'], round($totaltime, 3));
    include_once "./processors/cache_sync.class.processor.php";
    $sync = new synccache();
    $sync->setCachepath("../assets/cache/");
    $sync->setReport(true);
    $sync->emptyCache();
?>
<p />
<table cellpadding="0" cellspacing="0" class="actionButtons">
	<td id="Button2"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cancel.gif" align="absmiddle"> <?php echo $_lang["close"]; ?></a></td>
</table>
<?php
}
include_once "footer.inc.php";


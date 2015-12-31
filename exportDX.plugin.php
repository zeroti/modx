/***************************************************************
 *
 * ExportDX Plugin Ver0.01
 *
 * 2009/04/19
 * 
 * Event:OnWebPageInit
 *
 ***************************************************************
 */
define("UNIQKEY","EXPORTDX-ZERO");
if (!defined("IN_PARSER_MODE"))
{
	return;
}

$e = & $modx->Event;
if ($e->name == "OnWebPageInit" && isset($_GET['export']) && $_GET['export'] == sha1(UNIQKEY))
{
    $ex_hostname = strip_tags($_GET['exhost']);
    $wk_base_url= $ex_base_url . (substr($ex_base_url, -1) != "/" ? "/" : "");
    $wk_site_url= ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port) ? 'https://' : 'http://';
    $wk_site_url .= $ex_hostname;
    if ($_SERVER['SERVER_PORT'] != 80)
	    $wk_site_url= str_replace(':' . $_SERVER['SERVER_PORT'], '', $wk_site_url); // remove port from HTTP_HOST 
    $wk_site_url .= ($_SERVER['SERVER_PORT'] == 80 || (isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port) ? '' : ':' . $_SERVER['SERVER_PORT'];
    $wk_site_url .= $wk_base_url;
    $modx->config['base_url']= $wk_base_url;
    $modx->config['site_url']= $wk_site_url;
}

<?php
require((defined('BASEPATH') ? BASEPATH . '/commons/base.inc.php' : '../../commons/base.inc.php'));
ob_end_clean();
// Allow AJAX check
if (!$_SESSION['AllowAJAXTasks'])
	die('FOG Session Invalid');
if ($_SESSION['allow_ajax_kdl'] && $_SESSION['dest-kernel-file'] && $_SESSION['tmp-kernel-file'] && $_SESSION['dl-kernel-file'])
{
	if ($_REQUEST['msg'] == 'dl')
	{
		// download kernel from sf
		$blUseProxy = false;
		$proxy = '';
		if (trim($FOGCore->getSetting('FOG_PROXY_IP')))
		{
			$blUseProxy = true;
			$proxy = $FOGCore->getSetting('FOG_PROXY_IP').':'.$FOGCore->getSetting('FOG_PROXY_PORT');
		}
		if ($FOGCore->getSetting('FOG_PROXY_USERNAME'))
		{
			$blUseProxyAuth = true;
			$proxyauth = $FOGCore->getSetting('FOG_PROXY_USERNAME').':'.$FOGCore->getSetting('FOG_PROXY_PASSWORD');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, '700');
		if ($blUseProxy)
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		if ($blUseProxyAuth)
			curl_setopt($ch, CURLOPT_PROXYUSERPWD,$proxyauth);
		curl_setopt($ch, CURLOPT_URL, $_SESSION['dl-kernel-file']);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$fp = fopen($_SESSION['tmp-kernel-file'], 'wb');
		if ($fp)
		{
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_exec ($ch);
			curl_close ($ch);
			fclose($fp);
			if ( file_exists($_SESSION['tmp-kernel-file']))
			{
				if (filesize($_SESSION['tmp-kernel-file']) > 1048576)
					print "##OK##";
				else
					print "Error: Download failed: filesize = " . filesize( $_SESSION["tmp-kernel-file"]);
			}
			else
				print "Error: Failed to download kernel!";
		}
		else
			print "Error: Failed to open temp file.";
	}
	else if ($_REQUEST['msg'] == 'tftp')
	{
		$FOGFTP->set('host',$FOGCore->getSetting('FOG_TFTP_HOST'))
			   ->set('username', $FOGCore->getSetting('FOG_TFTP_FTP_USERNAME'))
			   ->set('password', $FOGCore->getSetting('FOG_TFTP_FTP_PASSWORD'));
		$destfile=$_SESSION['dest-kernel-file'];
		$tmpfile=$_SESSION['tmp-kernel-file'];
		unset($_SESSION['dest-kernel-file'],$_SESSION['tmp-kernel-file'],$_SESSION['dl-kernel-file']);
		if ($FOGFTP->connect()) 
		{				
			try
			{
				$backuppath = rtrim($FOGCore->getSetting('FOG_TFTP_PXE_KERNEL_DIR'),'/')."/backup/";	
				$orig = rtrim($FOGCore->getSetting('FOG_TFTP_PXE_KERNEL_DIR'),'/').'/'.$destfile;
				$backupfile = $backuppath.$destfile.date("Ymd")."_".date("His");
				$FOGFTP->mkdir($backuppath);
				if ($FOGFTP->rename($backupfile,$orig) || $FOGFTP->put($orig,$tmpfile,FTP_BINARY))
				{	
					@unlink($tmpfile);
					print '##OK##';
				}
				else
					print _('Error: Failed to install new kernel!');
				$FOGFTP->close();
			}
			catch (Exception $e)
			{
				print $e->getMessage();
			}
		}
		else
			print _('Error: Unable to connect to tftp server.');
	}
}
else
	echo "<b><center>"._("This page can only be viewed via the FOG Management portal")."</center></b>";

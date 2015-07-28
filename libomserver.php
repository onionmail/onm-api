<?
/* LibOmServer v1.0 OnionMail Integration libary 1.0 for PHP
 * Copyright (C) 2013 by Tramaci.Org
 * This file is part of OnionMail (http://onionmail.info)
 * 
 * LibOmServer is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This source code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this source code; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

if (!isset($ini)) $ini = parse_ini_file('etc/config.php',true);

require 'libgpg.php';
require 'libonionmail.php';

function OMOpenNick($srvNick) {
	global $ini;
	if (!isset($ini['srv-'.$srvNick])) return false;
	$SRV = $ini['srv-'.$srvNick];
	$OM = OmOpen($SRV['ip'],intval($SRV['port']),$SRV['nick'],$SRV['pass']);
	if ($OM===false) return false;
	return $OM;
	}	

function OMSubscribeProc($srvNick,$UserName,$GPGAsciiKey,$UserNormalMail) {
	global $ini;
	
	$Invite=@file_get_contents($ini['main']['invitemsg']);
	if ($Invite===false) return "libomserver: Invalid invite message file";
	
	$UserName=strtolower($UserName);
	$UserName=trim($UserName,"\t\r\n._-<>@ ");
	//flt
	
	if ($ini['main']['mindless']!=0) {
		if (strpos("\n".$_SERVER['REMOTE_ADDR'],"\n127.0.")!==false) {
			$ini['main']['verifymx']=0; //Prevent Hidden service Address leak!
			$ini['main']['maildirect']=0;
			if ($ini['main']['verbose']!=0) echo "LibOmServer:Mindless!\n";
			}
		}
	
	if (!isset($ini['srv-'.$srvNick])) return "libomserver: Invalid server `$srvNick`";
	$SRV = $ini['srv-'.$srvNick];
		
	$UserNormalMail=ParseMail($UserNormalMail);
	if ($UserNormalMail===false) return "Mail: Invalid mail address";
	
	if ($ini['main']['verbose']!=0) echo "LibOmServer:LoadKey\n";
	
	$RAWGPG=LoadPublicKey($GPGAsciiKey);
	if ($RAWGPG===false) return "PGPKey: Invalid ASCII armor";
	if (!CheckMailKey($RAWGPG,$UserNormalMail)) return "PGPKey: This key is not for your mail address";
	$RAWGPG=null;
	 
	if ($ini['main']['verbose']!=0) echo "OnionMail:Logon\n";
	 	
	$OM = OmOpen($SRV['ip'],intval($SRV['port']),$SRV['nick'],$SRV['pass']);
	if ($OM===false) return "OnionMail: Can't logon to the server";
	
	if ($ini['main']['verbose']!=0) echo "OnionMail:Vrfy\n";
	
	if (OmVrfy($OM,$UserName)) {
		OmClose($OM);
		return "OnionMail: User arleady exists";
		}
	
	if ($ini['main']['verbose']!=0) echo "GPG:ImportKey\n";
	            
	$er='';
	if (!GPGImportKey($GPGAsciiKey,$er)) {
		OmClose($OM);
		return 'GPG: '.trim($er,"\t\r\n ");
		}
	    
	if ($ini['main']['verbose']!=0) echo "OnionMail:Addusr\n";	    
	               
	$PR = OmAddusr($OM,$UserName);
	if ($PR===false) {
		OmClose($OM);
		if ($ini['main']['deletekeys']!=0) GPGDeleteKey($UserNormalMail);
		return "Can't create this user!";
		} 
	
	foreach($PR as $k => $v) {
	        $kk=strtoupper($k);
	        $Invite=str_replace('%%'.$kk.'%%',$v,$Invite);
	        }
	
	if ($ini['main']['addident']!=0) {
	        $i = OmInfo($OM);
		if ($i!==false) $Invite.="\r\n".implode("\r\n",$i)."\r\n";
		$i=null;
		}
	
	
	if ($ini['main']['verbose']!=0) echo "GPG:Encrypt\n";
	                 
	$er='';
	$msg=GPGEncrypt($Invite,$UserNormalMail,$er);
	if ($msg===false) {
		OmClose($OM);
		if ($ini['main']['deletekeys']!=0) GPGDeleteKey($UserNormalMail);
		return "GPG: ".trim($er,"\t\r\n ");
		}

	if ($ini['main']['nosendmail']) {
	        OmClose($OM);
	        if ($ini['main']['deletekeys']!=0) GPGDeleteKey($UserNormalMail);
	        return array(
	        	'ok'	=>	true ,
			'msg'	=>	$msg ,
			'mail'	=>	$UserNormalMail )
			;
		}

	if ($ini['main']['verbose']!=0) echo "SMTP:Send Mail\n";

	if ($ini['main']['maildirect']==0) {

		$msg=str_replace("\r\n","\n",$msg);
		$msg=
			"Subject: ".$ini['main']['invitesubject']."\n".
			"MIME-Version: 1.0\n".
			"X-Enigmail-Version: 1.5.0\n".
			"Content-Type: text/plain; charset=ISO-8859-15\n".
			"Content-Transfer-Encoding: 8bit\n\n".
			$msg;
		
		if (!OmSend($OM,$UserNormalMail,$msg)) {
			OmClose($OM);
			return "OnionMail: Can't send mail message!";	
			}
		} else {
	
		$H = 
			"Subject: ".$ini['main']['invitesubject']."\r\n".
			"From: server@".$_SERVER['HTTP_HOST']."\r\n".
			"To: $UserNormalMail\r\n".
			"Error-To: <>\r\n".
			"MIME-Version: 1.0\r\n".
			"X-Enigmail-Version: 1.5.0\r\n".
			"Content-Type: text/plain; charset=ISO-8859-15\r\n".
			"Content-Transfer-Encoding: 8bit\r\n".
			"X-SSL-Transaction: NO\r\n".
			"X-Generated: libombserver-http"
			; 
		
		$msg=str_replace("\r\n","\n",$msg);
	        $msg=str_replace("\n","\r\n",$msg);
		if (!@mail($UserNormalMail, $ini['main']['invitesubject'], $msg, $H)) {
			OmClose($OM);
			return "libomserver: Can't send mail message";
			}
		
		}
		
	if ($ini['main']['deletekeys']!=0) GPGDeleteKey($UserNormalMail);
	if ($ini['main']['verbose']!=0) echo "LibOmServer:Complete\n";
		
	OmClose($OM);
	return true;
		
	}

function OMGetServerArray() {
	global $ini;
	$ar=array();
	foreach($ini as $k=>$v) if (strpos($k,"srv-")===0) {
	        list($b,$a)=explode('-',$k,2);
	        $ar[]=$a;
		} 
	return $ar;
	}

function OMGetServerArrayEx() {
	global $ini;
	$ar=array();
	foreach($ini as $k=>$v) if (strpos($k,"srv-")===0) {
	        list($b,$a)=explode('-',$k,2);
	        $ar[$a] = $v;
		} 
	return $ar;
	}
	
/*
* Eaxmple of use:
*
* 
*  $rs=    OMSubscribeProc('serverNick','userName', file_get_contents('publicKey.asc'), 'inetMail@example.org') ;
*  Rs === true Ok.
*  Rs === array() Ok, in nosendmail mode.
*  Or $rs = Error Message       
*  
*
**/
		
	
     
?> 

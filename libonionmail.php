<?
/* libOnionMail 1.3 for PHP
 * Copyright (C) 2013, 2015 by Tramaci.Org
 * This file is part of OnionMail (http://onionmail.info)
 * 
 * libOnionMail is free software; you can redistribute it and/or modify
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
 
$OnionMailError=false;
$OnionMailCmdType = array(
        	'access'	=>	'ReadSt'	,
        	
		'info'		=>	'ReadMul'	,
        	'getkey'	=>	'ReadMul'	,
        	'sslcert'	=>	'ReadMul'	,
        	'all'		=>	'ReadMul'	,
        	
        	'spam list'	=>	'ReadMul'	,
        	'spam del'	=>	'ReadMul'	,
        	'getrulez'	=>	'ReadMul'	,
        	'list'		=>	'ReadMul'	,     
        	'iplist'	=>	'ReadMul'	,
        	
        	'send'		=>	'WriteMul'	,
        	
        	'addusr'	=>	'ReadAS'	,
        	'stat'		=>	'ReadHead'	,
        	'elist'		=>	'ReadHead'	,
        	'mklist'	=>	'ReadHead'	,
        	'par'		=>	'ReadHead'	,
		
		'ver'		=>	'ReadSt'	,
        	'quit'		=>	'ReadSt'	,
        	'sslcheck'	=>	'ReadMul'	,
        	'status'	=>	'ReadSt'	,
        	'dnsbl'		=>	'ReadSt'	,
        	'frienderr'	=>	'ReadMul'	,
        	'friends'	=>	'ReadMul'	,
        	'vrfy'		=>	'ReadSt'	,
        	'ssleid'	=>	'ReadMul'	,
        	
        	'vmatreg'	=>	'ReadHead'	
		)
		;

// OnionMail API ///////////////////////////////////////////////////////////////

function OmServer(&$fp,$nick,$passw) { 
	return OmCmd($fp,'server',"$nick $passw"); 
	}
	 
function OmInfo(&$fp) { 
	return OmCmd($fp,'info'); 
	}
	
function OmAddusr(&$fp,$user) {
	if (!OmCheckPar(array($user))) return false; 
	return OmCmd($fp,'addusr',$user); 
	}

function OmVrfy(&$fp,$user) {
	if (!OmCheckPar(array($user))) return false; 
	return OmCmd($fp,'vrfy',$user); 
	}
	
function OmElist(&$fp) { 
	return OmCmd($fp,'elist'); 
	}
	
function OmStat(&$fp) { 
	return OmCmd($fp,'stat'); 
	}
	
function OmGetPar(&$fp) { 
	return OmCmd($fp,'par'); 
	}
	
function OmSetPar(&$fp,$par,$val) {
	if (!OmCheckPar(array($par,$val))) return false; 
	return OmCmd($fp,'par',"$par $val"); 
	}
	
function OmSpamList(&$fp) { 
	return OmCmd($fp,'spam list',''); 
	}
	
function OmSpamDel(&$fp,$mail) {
	if (!OmCheckPar(array($mail))) return false; 
	return OmCmd($fp,'spam del',$mail); 
	}
			
function OmListCreate(&$fp,$list,$owner,$title,$isPublic=true,$isGPG=false) {
        if (strpos($title,' ')!==false) return false;
	if (!OmCheckPar(array($list,$owner,$title))) return false;
        $param='';
	if ($isPublic!=false) $param='/OPEN';
	if ($isGPG!=false) $param='/PGP';       
        fwrite($fp,trim("mklist $list $owner $title $param\r",' '));
        return ReadBool($fp); 
	}			
	
function OmListOpen(&$fp,$list,$usermail,$passw) { 
	if (!OmCheckPar(array($list,$usermail,$passw))) return false;
	fwrite($fp,"list $list $usermail $passw\r");
	return ReadBool($fp);  
	}

function OmListList(&$fp) { 
	return OmCmd($fp,'list'); 
	}
	
function OmListUserMode(&$fp,$usermail,$mode) { 
	if (!OmCheckPar(array($usermail,$mode))) return false;
	return OmCmd($fp,'mode',"$usermail $mode"); 
	}
	
function OmListUserInvite(&$fp,$usermail) { 
	if (!OmCheckPar(array($usermail))) return false;
	return OmCmd($fp,'invite',$usermail); 
	}
	
function OmListUserRemove(&$fp,$usermail) { 
	if (!OmCheckPar(array($usermail))) return false;
	return OmCmd($fp,'remove',$usermail); 
	}
	
function OmListDelete(&$fp,$list) { 
	if (!OmCheckPar(array($list))) return false;
	return OmCmd($fp,'delete',$list); 
	}

function OmCheckPar($arr) {
	global $OnionMailError;
	foreach($arr as $v) if (strpos(' ',$v) || strpos("\n",$v) || strpos("\r",$v) || strpos("\t",$v)) {
		$OnionMailError='Syntax error';
		return false;
		}
	return true;
	}
	
function OmSend(&$fp,$to,$txt) {
	global $OnionMailCmdType; 
	if (!OmCheckPar(array($to))) return false;
	fwrite($fp,"send $to\r");
	return WriteMul($fp,$txt); 
	}	

function OmOpen($ip,$port,$server=false,$pass=false) {
        $fp = @fsockopen($ip,$port);
        if ($fp===false) return false;
        $st = ReadSt($fp);
        if ($st===false) {
        	fclose($fp);
        	return false;
		}
	if ($server!==false) {
	        if (!OmCmd($fp,'server',"$server $pass")) {
	        	fclose($fp);
        		return false;
			}
		}
	return $fp;
	}

function OmClose(&$fp) {
	OmCmd($fp,'quit');
	fclose($fp);
	}

///////////////////////// SUBROUTINES //////////////////////////////////////////

function ReadBool(&$fp) {
	global $OnionMailError;
	$OnionMailError=false;
	$li=fgets($fp,1024);
	$li=trim($li,"\t\r\n ");
	list($cod,$li)=explode(' ',$li,2);
	if ($cod[0]=='+') return true;
	$OnionMailError=$li;
	return false;
	}

function ReadSt(&$fp) {
	global $OnionMailError;
	$OnionMailError=false;
	$li=fgets($fp,1024);
	$li=trim($li,"\t\r\n ");
	list($cod,$li)=explode(' ',$li,2);
	if ($cod[0]=='+') return $li;
	$OnionMailError=$li;
	return false;
	}

function ReadMul(&$fp) {
	global $OnionMailError;
	$OnionMailError=false;
	$li=fgets($fp,1024);
	$li=trim($li,"\t\r\n ");
	list($cod,$li)=explode(' ',$li,2);
	if ($cod[0]!='+') {
		$OnionMailError=$li;
		return false;
		}
	$li='';	
	$arr=array();
	while(!feof($fp)) {
	        $li=fgets($fp,1024);
	        $li=trim($li,"\t\r\n ");
	        if ($li=='.') break;
	        $arr[] = $li;
		}
	return $arr;
	}	

function ReadHead(&$fp) {
	$arr=ReadMul($fp);
	if ($arr===false) return false;
	$q=array();
	foreach($arr as $li) {
	        list($k,$v)=explode(':',$li,2);
	        $k=strtolower($k);
	        $k=trim($k,' ');
	        $v=trim($v,' ');
	        $q[$k]=$v;
		} 
	return $q;
	}

function ReadAS(&$fp) {
	$arr=ReadMul($fp);
	if ($arr===false) return false;
	$q=array();
	foreach($arr as $li) {
	        list($k,$v)=explode('=',$li,2);
	        $k=strtolower($k);
	        $k=trim($k,' ');
	        $v=trim($v,' ');
	        $q[$k]=$v;
		} 
	return $q;
	}
	
function WriteMul(&$fp,$write) {
	$x = ReadBool($fp);
	if ($x===false) return false;
	$write=str_replace("\r\n","\n",$write);
	$write=str_replace("\r","\n",$write);
	$write=explode("\n",$write);
	
	foreach($write as $li) {
	        if ($li=='.') $li=' .';     
	        fwrite($fp,$li."\r");
	        }
	fwrite($fp,"\r.\r");
	return ReadBool($fp);
	}	
	
		
function OmCmd(&$fp,$cmd,$par=false) {
	global $OnionMailCmdType; 
	if ($par===false) list($cmd,$par)=explode(' ',$cmd,2);
	fwrite($fp,"$cmd $par\r");
	if (!isset($OnionMailCmdType[$cmd])) return ReadBool($fp);
	if ($OnionMailCmdType=='WriteMul') return WriteMul($fp,$par); else return $OnionMailCmdType[$cmd]($fp);
	}
	

//////////////////////// EXTRA FUNCTIONS ///////////////////////////////////////
	
function Socks4aOpen($proxyh,$proxyp,$host,$port,$userid='',$timeout=2,&$errno,&$errstr) {
	$errno=0;
	$errstr='';
	
	$fp = @fsockopen($proxyh, $proxyp, $errno, $errstr, $timeout);
	if ($fp===false) return false;
	$req=chr(4).chr(1).pack('n',$port).	/*pack('N',ip2long('0.0.0.1'))*/
				"\0\0\0\x01"
				."$userid\0$host\0";
	fwrite($fp, $req);
	$rs=fread($fp,8);
	$cod = ord($rs[1]);
	if ($cod==90) return $fp;
	fclose($fp);
	$errno = -$cod;
	$errstr="SOCKS error $cod [".dechex($cod).']';
	if ($cod==91) $errstr="Richiesta fallita o rifiutata";
	if ($cod==92) $errstr="Il server SOCKS non ha potuto connettersi al server ident sul client";
	if ($cod==93) $errstr="Il server ident e il client hanno riportado USERID differenti";
	
	return false;
	}


?>

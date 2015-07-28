<?
/* libgpg GPG Integration libary 1.0 for PHP
 * Copyright (C) 2013 by Tramaci.Org
 * This file is part of OnionMail (http://onionmail.info)
 * 
 * libgpg is free software; you can redistribute it and/or modify
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
 
//Parse ascii armor
 
function LoadPublicKey($asc) {
	$asc=str_replace("\r\n","\n",$asc);
	$asc=str_replace("\r","\n",$asc);
	$fi = explode("\n",$asc);
	$asc='';
	$k='';
	$f=0;
	$z=false;
	foreach($fi as $li) {
		$li=trim($li,"\t\r\n ");
		if ($f==2 AND $li!='') return false;
		if ($f==0) {
		        if (strpos($li,'--BEGIN PGP PUBLIC KEY BLOCK--')!==false) {
		        	$f=1;
		        	continue;
				}
			} else {
			if (strpos($li,'--END PGP PUBLIC KEY BLOCK--')!==false) {
				$f=2;  
				break;
				} 
			if ($li=='') {
			        if ($z==false) {
			        	$z=true;
			        	$k='';
			        	continue;
					} else {
					return false;
					}
				} else $k.=$li;		
			}
		}
	if ($f!=2) return false;
	$k=base64_decode($k);
	if ($k[0]!=chr(0x99) || $k[1]!=chr(1)) return false;
	return $k; //crc24 is loaded into the data!
	}

//Verifiy mail->key 
function CheckMailKey(&$rawkey,$mail) {
	$mail=strtolower($mail);
	$mail=trim($mail,"\t\r\n<> ");
	if (stripos($rawkey,"<$mail>")!==false) return true;
	return false;
	}

function ParseMail($addr) {
	global $ini;
	$q='';
	$addr=trim($addr,"\t ");
	$addr=strtolower($addr);
	$cx=strlen($addr);
	for ($ax=0;$ax<$cx;$ax++) {
	        $ch = $addr[$ax];
	        if (strpos("1234567890._-qwertyuiopasdfghjklzxcvbnm@",$ch)===false) return false;
	        $q.=$ch;
		}
	$t = explode('@',$q);
	if (count($t)!=2) return false;
	$host = $t[1];
	if ($host!=trim($host,'.')) return false;
	if ($ini['main']['verifymx']==0) return $q;
	if (function_exists('getmxrr')) {
		$r=getmxrr($host,$mxs);
		if ($r===false) return false;
		}
	return $q;
	}

function __mypopen($cmd,$argv) {
	global $ini;
	$ocmd=$cmd;
	$t0="sh-$cmd";
	if (!isset($ini[$t0])) die("__mypopen: No ini section for: `".htmlspecialchars('sh-'.$cmd,ENT_QUOTES)."`\n");
	$par=$ini[$t0];
	$t0='';
	
	$stdio = array(
		   0 => array("pipe", "r") ,  
		   1 => array("pipe", "w") ,  
		   2 => array("pipe", "w") )
		   ;
	
	$cwd=NULL;
	$env=NULL;
	$t0=array();
	$mypath=dirname(__FILE__)."/";
	$t1=array();
	foreach($par as $k => $v) {
	        $v=str_replace('%%PATH%%',$mypath,$v);
	        if ($k=='cmd') { $cmd=$v; continue; }
	        if ($k=='cwd') { $cwd=$v; continue; }
	        if ($k=='par') {
		        if (!isset($ini["par-$v"])) die("__mypopen: No ini/par section for: `".htmlspecialchars('sh-'.$cmd.'` Call `par-'.$par,ENT_QUOTES)."`\n");
		        foreach($ini["par-$v"] as $kk => $vv) {
				$vv=str_replace('%%PATH%%',$mypath,$vv);
				$t1[$kk]=$vv;			
				}
			continue;
			}
		if (!is_array($env)) $env=array();
		$env[$k]=$v;	        
		}
	
	foreach($t1 as $k => $v) {
		
		$k=trim($k,'_');
		if (strlen($k)>1) $k="--$k"; else $k="-$k";
		if ($v=='') $t0[]=$k; else $t0[] = "$k ".escapeshellarg($v);
	        }
	
	
	foreach($argv as $k => $v) {
		
		if (strlen($k)>1) $k="--$k"; else $k="-$k";
		if ($v=='') $t0[]=$k; else $t0[] = "$k ".escapeshellarg($v);
	        }
	  	
	$args=implode(' ',$t0);
				
	$process = proc_open("$cmd $args", $stdio, $pipes, $cwd, $env);
	if (!is_resource($process)) return false;
	$pipes['h'] = $process;	         
	return $pipes;	
	}
	
function __mypopclose(&$obj) {
	@fclose($obj[0]);
	@fclose($obj[1]);
	@fclose($obj[2]);
	return proc_close($obj['h']);
	}

function ArmorCheck($armor) {
        $cx=strlen($armor);
        for ($ax=0;$ax<$cx;$ax++) {
	        $ch=ord($armor[$ax]);
	        if ($ch<32 && $ch!=13 && $ch!=10) return false;
		if ($ch>127) return false; 
		}
	return true;
	}

function GPGImportKey($armor,&$er='') {
	if (!ArmorCheck($armor)) return false;	
	$H = __mypopen('gpg', array('import'=>'','a'=>''));
	if ($H===false) return false;
	fwrite($H[0],$armor."\n\n");
	fclose($H[0]);
	$rs='';
	$er='';
	while(!feof($H[1])) {
		$rs.=trim(fgets($H[1],512),"\r\n")."\n";
		if (!feof($H[2])) $er.=fgets($H[2],512)."\n";
		}
	$rc= __mypopclose($H);
	if ($rc==0) return true; else return $rc;	
	}

function GPGDelKey($keyid,&$er='') {
	$H = __mypopen('gpg', array('delete-keys'=>$keyid));
	if ($H===false) return false;
	$rs='';
	$er='';
	while(!feof($H[1])) {
		$rs.=trim(fgets($H[1],512),"\r\n")."\n";
		if (!feof($H[2])) $er.=fgets($H[2],512)."\n";
		}
	$rc= __mypopclose($H);
	if ($rc==0) return true; else return true;	
	}

function GPGEncrypt($msg,$keyid,&$er='') {
	
	$msg=str_replace("\r\n","\r",$msg);
	$msg=str_replace("\n","\r",$msg);
	
	$H = __mypopen('gpg',array('e'=>'','a'=>'','r'=>$keyid));
	if (!is_array($H)) return $H;
	fwrite($H[0],$msg."\n");
	fclose($H[0]);
	$rs='';
	$er='';
	while(!feof($H[1])) {
		$rs.=trim(fgets($H[1],512),"\r\n")."\n";
		if (!feof($H[2])) $er.=fgets($H[2],512)."\n";
		}
	$rc= __mypopclose($H);
	if ($er=='' AND $rc!=0) $er="Return code $rc";
	if ($rs=='' OR strpos($rs,'--END PGP MESSAGE--')===false) {
		if ($er=='') $er='No PGP Message';
		return false;
		}
	return $rs;	
	}

function GPGHasKey($keyid,&$er='') {
	$keyid=strtolower($keyid);
	$keyid=trim($keyid,"\t\r\n<>@ ");
	$H = __mypopen('gpg',array('fingerprint'=>$keyid));
	if (!is_array($H)) return $H;
	$rs='';
	$er='';
	while(!feof($H[1])) {
		$rs.=trim(fgets($H[1],512),"\r\n")."\n";
		if (!feof($H[2])) $er.=fgets($H[2],512)."\n";
		}
	$rc= __mypopclose($H);
	if ($er=='' AND $rc!=0) $er="Return code $rc";
	return strpos($rs,"<$keyid>")!==false;	
	}
function GPGDeleteKey($keyid,&$er='') {
	$keyid=strtolower($keyid);
	$keyid=trim($keyid,"\t\r\n<>@ ");
	$H = __mypopen('gpg',array('delete-keys'=>$keyid));
	if (!is_array($H)) return $H;
	$rs='';
	$er='';
	while(!feof($H[1])) {
		$rs.=trim(fgets($H[1],512),"\r\n")."\n";
		if (!feof($H[2])) $er.=fgets($H[2],512)."\n";
		}
	$rc= __mypopclose($H);
	if ($er=='' AND $rc!=0) $er="Return code $rc";
	return $rc==0;	
	}
	   
?>

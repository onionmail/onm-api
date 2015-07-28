<?php
define('AL_ALFA'    , '1234567890qwertyuiopasdfghjklzxcvbnm');
define('AL_AZ'      , 'qwertyuiopasdfghjklzxcvbnm');
define('AL_NUM'     , '1234567890');
define('AL_IP'      , '1234567890.');
define('AL_DOM'     , '1234567890qwertyuiopasdfghjklzxcvbnm-.');
define('AL_MAIL'    , '1234567890qwertyuiopasdfghjklzxcvbnm-.@');

require 'libomserver.php';

echo "\nOnionMail cmd V1.0\n\t(C) 2013 tramaci.org\n\n";

$action=0;
$par=array();
for ($ax=1;$ax<$argc;$ax++) {
	if (($argv[$ax]=='-n' OR $argv[$ax]=='--addusr')) {
		if ($action!=0) Helpex();
		$action=1;
		continue;
		}
		
	if (($argv[$ax]=='-al' OR $argv[$ax]=='--addlist')) {
		if ($action!=0) Helpex();
		$action=2;
		continue;
		}
       
	if (($argv[$ax]=='-ll' OR $argv[$ax]=='--list')) {
		if ($action!=0) Helpex();
		$action=3;
		continue;
		}		    
		 
	if (($argv[$ax]=='-li' OR $argv[$ax]=='--invite')) {
		if ($action!=0) Helpex();
		$action=4;
		continue;
		}		 
		 
	if (($argv[$ax]=='-lm' OR $argv[$ax]=='--mode')) {
		if ($action!=0) Helpex();
		$action=5;
		continue;
		}		 
		 
	if (($argv[$ax]=='-ld' OR $argv[$ax]=='--listdel')) {
		if ($action!=0) Helpex();
		$action=6;
		continue;
		}		 
		 
	if ($argv[$ax]=='-l' OR $argv[$ax]=='--local') { 
		$par['local']=true; 
		continue; 
		}
	
	if ($argv[$ax]=='-h' OR $argv[$ax]=='-?') Helpex(); 
			
	echo "\nError on parameter `".$argv[$ax]."`\n";
	Helpex(); 		
	}
	
if ($action==0) Helpex();

$SRV = ChooseServer();

if ($action==2) {
        $lst = IAInput("Mailing list id",AL_DOM,8,18);
        $own = IAInput("Owner mail",AL_MAIL,4,64);
	$tit = IAInput("Title (one word)",AL_DOM,0,32);
	
	echo "List type:\n";
	$x = IChoose(array(
		'p'	=>	'Public list'	,
		'h'	=>	'Hidden list'	))
		;
	
	$OM = OMOpenNick($SRV['nick']);
	if ($OM===false) die("Error\n\tCan't connect\n\tError: $OnionMailError\n");
	
	$RS = OmListCreate($OM,$lst,$own,$tit, ($x=='p'));
	OmClose($OM);
	if ($RS===false) die("Error: $OnionMailError\n");
	echo "Ok\n";
	}

if ($action>2 AND  $action<8) {
        $lst = IAInput("Mailing list id",AL_MAIL,8,50);
        $OM = OMOpenNick($SRV['nick']);
	if ($OM===false) die("Error\n\tCan't connect\n\tError: $OnionMailError\n");
	$own = IAInput("User name",AL_MAIL,4,64);
	$pwl = IAInput("Password",false,1,256);
	
	$RS = OmListOpen($OM,$lst,$own,$pwl);
	if ($OM===false) {
		OmClose($OM);
		die("Error: $OnionMailError\n");
		}
		
	if ($action==3) {		
		$RS=OmListList($OM);
		OmClose($OM);
		if ($RS===false) die("Error: $OnionMailError\n");
		echo implode("\n",$RS);
		}
		
	if ($action==4) {		
		$usr = IAInput("User invite mail",AL_MAIL,8,50);
		$RS=OmListUserInvite($OM,$usr);
		OmClose($OM);
		if ($RS===false) die("Error: $OnionMailError\n");
		echo "Ok\n";
		}
	
	if ($action==5) {		
		$usr = IAInput("User",AL_MAIL,8,50);
		$b= array(
			1	=>	'Normal',
			2	=>	'Admin')
			;
		$a= IChoose($b);
		$a=$b[$a];
		$b='';
		
		$RS=OmListUserMode($OM,$usr,$a);
		OmClose($OM);
		if ($RS===false) die("Error: $OnionMailError\n");
		echo "Ok\n";
		}

	if ($action==6) {		
		$usr = IAInput("User",AL_MAIL,8,50);
		$RS=OmListUserRemove($OM,$usr);
		OmClose($OM);
		if ($RS===false) die("Error: $OnionMailError\n");
		echo "Ok\n";
		}	                
	
	}
         
if ($action==1) {
	if ($par['local']) {
		$NU =IAInput("New user name",AL_DOM,4,18);
		echo "Create new user ... ";
		
		$OM = OMOpenNick($SRV['nick']);
		if ($OM===false) die("Error\n\tCan't open server\n");
		$RS=OmAddusr($OM,$NU);
		OmClose($OM);
		if ($RS===false) die("Error\n\tCan't create user\n\tError: $OnionMailError\n");
		print_r($RS);
		exit;			        
		} else {
	        $NU =IAInput("New user name",AL_DOM,4,18); 
		$M = IAInput("Original mail",AL_MAIL,4,80);
		echo "Paste public key:\n";
		$K='';
		while(true) {
		        $li = IInput();
		        $K.=$li."\r\n";
		        if (strpos($li,'--END PGP PUBLIC KEY BLOCK--')!==false) break;
			}
		
		echo "Create new user ... ";				
		$rs = OMSubscribeProc($SRV['nick'],$NU,$K,$M);
		if (is_array($rs)) echo "Ok\n".$rs['msg']."\n";
		if ($rs===true) echo "Ok\n"; else {
			echo "Error\n\t$rs\n";
			exit(1);
			}
		exit;
		} 
	}




function Helpex() { 
ob_start();
?>
  -n	--addusr	Add new user/mailbox.
  -al	--addlist	Create new mailing list.
  -ll	--list		List all users of mailinglist.
  -li	--invite	Invite new user to the mailing list.
  -lm	--mode		Set the admin/normal user of mailing list.
  -ld	--listdel	Remove user form mailing list.
  -l			Use with -n create local user without 
  			sending GPG email.

Files:
	ks		Keyrings folder (libgpg)
	etc		Config folder (libonionmail)

	Required:
			libomserver.php
			libgpg.php
			libonionmail.php  			

Configuration:
	File: etc/config.php
	
	Create one section [] per server, named [srv-ServerKickName]
	Example: Server nick=`example` onion=`lololololololol.onion`
	
	[srv-example]
	nick="example"
	pass="password"
	ip="127.0.0.1"	;ControlPort IP
	port=19100	;ControlPort
	onion=lololololololol.onion
  			
<?
	$st=ob_get_clean();
	echo str_replace("\r\n","\n",$st);		
	exit("\n");
	}

function IsAlpha($st,$aa) {
	$cx=strlen($st);
	for ($ax=0;$ax<$cx;$ax++) if (stripos($aa,$st[$ax])===false) return false;
	return true;
	}

function IInput() {
	$li = fgets(STDIN,512);
	if ($li===false) die("\n");
	$li=trim($li,"\t\r\n ");
	return $li;
	}

function IChoose($opt) {
	foreach($opt as $k=> $v) echo "$k)\t$v\n";
	echo " >";
	while(true) {
	        $x=fgets(STDIN,100);
	        if ($x===false) die("\n");
	        $x=trim($x,"\t\r\n ");
	        if (isset($opt[$x])) return $x;
		}
	}  

function IAInput($tit,$aa,$mi,$ma) {
        while(true) {
		echo "$tit: >";
		$a = IInput();
		$l = strlen($a);
		if ($l>=$mi AND $l<=$ma) {
			 if ($aa!==false) {
			 	if(IsAlpha($a,$aa)) return $a;
				} else return $a;
			}
		echo "Invalid!\n";
		}
	}
	
function ChooseServer() {
	echo "Select Server:\n";
	$A = OMGetServerArrayEx();
	$Q = array();
	$C = array();
	$bp=1;
	foreach($A as $k => $v) {
		$C[$bp] = str_pad($k.' ',16,'.',STR_PAD_RIGHT).' '.$v['onion'];
		$Q[$bp] = $v;
		$bp++;
		}
	$x= IChoose($C);
	return $Q[$x];
	}
?>

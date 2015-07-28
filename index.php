<!doctype html>
<html>
<head>
<title>PHPOnionMail Default WebPage</title>
<style>
#win2 { visibility: hidden; }
</style>
<script>

function Running() {
	var v=document.getElementById('win1');
	v.style.visibility='hidden';
	v=document.getElementById('win2');
	v.style.visibility='visible';	
	}
</script>
</head>
<body>
<?
if (!count($_POST)) {
?>
<form method="POST" id="win1">

	Your old mail address: <input type="text" name="mail" value=""><br>
	Your new mail address: <input type="text" name="user" value="">@etc.....onion<br>
	Paste here your PGP public key:<br>
	<textarea rows="15" cols="40" name="pgp"> </textarea><br>
	<input type="submit" value="Subscribe" onclick="Running()">
	
</form><p id="win2">Please wait...</p>
<? } else { ?>
<h1>Creating OnionMail address</h1>
<?

require 'libomserver.php';

$mail = trim($_POST['mail'],"\t\r\n ");
$pgp = trim($_POST['pgp'],"\t\r\n ");
$user= trim($_POST['user'],"\t\r\n ");

if ($mail=='' OR $pgp=='' OR $user=='') die('Example: Invalid data');
ob_flush();

$srv=false;
foreach($ini as $k => $v) {
	if (strpos("\n$k","\nsrv-")!==false) {
	        $srv=str_replace(array("\nsrv-","\n"),"","\n$k");
	        break;
		}
	}
	
if ($srv===false) die('Example: No server!');	
	
$RS = OMSubscribeProc($srv,$user, $pgp, $mail) ;
if (is_array($RS)) {
	echo '<pre>';
	echo htmlspecialchars($RS['msg'],ENT_QUOTES);
	echo '</pre>';
	} else if ($RS===true) {
	echo "<h2>Complete</h2>";
	} else {
	echo "<h3>";
	echo htmlspecialchars($RS,ENT_QUOTES);
	echo "</h3>";
	}

 } ?>
</body>
</html>

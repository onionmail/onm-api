
	PHPOnionMail Web integration 1.3
	

etc/			Configuration folder
kr/			Keyring folder
etc/config.php		Main configuration file
libgpg.php              GPG PHP Interface
libonionmail.php	OnionMail 0.1.2 PHP ControlPort Interface
libomserver.php		OnionMail 0.1.2 PHP Web Interface
omcmd.php		OnionMail cmd 1.0 PHP Command line interface
index.php		Example web site.
	
Configure the file etc/config.php to use:

[srv-example]	;Example server
nick="example"	;Server NickName
pass="HackMe"	;Server Password
ip="127.0.0.1"	;OnionMail ControlPort address and port
port=19100

[main]		;Main configuration
mindless=1	;Enable mindless compilant!
verifymx=0	;Verifiy mail by DNS request (dangerous for Hidden services).
maildirect=0	;Send message directly by php (dangerous for Hidden services).
nosendmail=0	;Do no send any email, retunr tne message into an array.
addident=1	;Add `info` request to the message 
invitesubject=Your new OnionMail	;Subject of message	
invitemsg=etc/invito.msg		;Text file of message (ISO-8859-2)
verbose=0
deletekeys=1	;Delete all PGP received keys.


[par-gpg] 	; GPG Parameters (All required)
		; %%PATH%% is expanded by dirname(__FILE__)."/" of php script.
			
[sh-gpg] 	; GPG Call mode
cmd="gpg"	;GPG Executable
cwd="%%PATH%%"	;Current working directory for GPG 
par=gpg		;It call par-gpg

[srv-example]	;Server section (example)
nick="example"	;Server nick name
pass="HackMe"	;Server password
ip="127.0.0.1"	;ControlPort IP (for remote use: tls//)
port=19100	;ControlPort 
onion=foo.onion	;Hidden service



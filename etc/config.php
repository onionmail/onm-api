;<? die(')') ; ?> Do not modify this line!
[main]
mindless=1
verifymx=0
maildirect=0
nosendmail=0
addident=1
invitesubject=Your new OnionMail
invitemsg=etc/invito.msg
verbose=0
deletekeys=1

[sh-gpg]
cmd="gpg"
cwd="%%PATH%%"
par=gpg

[par-gpg]
no-default-keyring=""
keyring="%%PATH%%kr\keyring"
secret-keyring="%%PATH%%kr\secring"
trustdb-name="%%PATH%%kr\trustdb"
homedir="%%PATH%%kr"
no-greeting=""
ask-sig-expire=""
no-ask-cert-expire=""
_yes=""
trust-model=always
no-options=""

[srv-example]
nick="example"
pass="HackMe"
ip="127.0.0.1"
port=19100
onion=fooooooooooooooo.onion

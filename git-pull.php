<?php
echo `ls | grep -v git-pull | xargs rm -rf`;
echo `git clone https://github.com/NathanTCz/sbl-app.com.git`;
echo `mv sbl-app.com/* sbl-app.com/.* ./`;
echo `rm -rf sbl-app.com`;
echo `whoami`;
?>


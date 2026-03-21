#!/usr/local/bin/php
<?php


/*** ZIP IT ***/
if (!chdir('../')) { echo "ERROR! chdir"; }
unlink('~/public_html/domain.signedon.net.archive/www.AionianBible.org.tar.gz');
system('tar c www-production-files www-resources | gzip --best >  ~/public_html/domain.signedon.net.archive/www.AionianBible.org.tar.gz');
system('ls -ail ~/public_html/domain.signedon.net.archive/www.AionianBible.org.tar.gz');

/*** done ***/
echo "DONE! ARCHIVE SUCCESS!!";

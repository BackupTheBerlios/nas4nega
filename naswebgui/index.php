<html>
<head>
<title>Nas4Nega</title>
<link rel="stylesheet" type="text/css" href="naswebgui.css">

</head>
<body>
<?php 
// nas4nega - a WebGui for the NAS for Zenega
// lot of code is based on 
// phpSysInfo - A PHP System Information Script
// http://phpsysinfo.sourceforge.net/
// The version was 2.5.2_rc1
// nas4nega is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// $Id: index.php,v 1.1 2006/03/17 18:29:02 bed Exp $  $Release$
// nas4nega external release version number
$VERSION = "0.0.1";
$lang = "de"; // or en in the moment, see directory lang for more languages

define('APP_ROOT', dirname(__FILE__));
$startTime = array_sum( explode( " ", microtime() ) );

ini_set('magic_quotes_runtime', 'off');
ini_set('register_globals', 'off');
ini_set('display_errors','on');
require_once( APP_ROOT . '/lang/' . $lang . '.php' );
// echo APP_ROOT . '/lang/' . $lang . '.php' ;
require_once( APP_ROOT . '/includes/class.error.inc.php' );
$error = new Error;
require_once( APP_ROOT . '/includes/class.Linux.inc.php' );

$sysinfo = new sysinfo;
menu();

echo "</span></div>\n" ;
echo "<div class=\"container\">\n" ;
echo "<div span class=\"inhalt\">\n" ;

echo "<p>" . $text['welcome'];

$x = $sysinfo->vhostname();
echo "\n<br>  :" .  $x . " : \n" ;

//phpinfo();

?>
</div>



</div>
<div id="footer"><p id="klein">
Besuchen Sie das WebGui Projekt!
<a href="http://developer.berlios.de/projects/nas4nega/">nas4nega</a></p>
</div>
</body>
</html>
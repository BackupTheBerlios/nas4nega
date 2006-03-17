<?php 
// nas4nega - a WebGui for the NAS for Zenega 
// Class and function library for nas4nega
// plenty of code in this file is based on 
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
// $Id: class.Linux.inc.php,v 1.1 2006/03/17 18:29:05 bed Exp $  $Release$
// nas4nega external release version number
// Class and function library for nas4nega
// $Id: class.Linux.inc.php,v 1.1 2006/03/17 18:29:05 bed Exp $ $Release$

function menu( ) {
   global $text;
   echo "<!-- The Menu -->\n";
   echo "<div class=\"menu\">\n"; 
   echo "<img src=\"logo.png\" ALT=\"" . $text['logo_desc'] . "\">\n"; 
   echo "NAS4nega<br>\n"; 
   echo "<span class=\"link\">\n"; 
   echo "System<br>\n"; 
   echo "<a href=\"setup.htm\">" . $text['setup'] . "</a><br>\n"; 
   echo "<br>\n";
   echo "Dienste<br>\n";
   echo "<a href=\"samba.htm\">Samba</a><br>\n";
   echo "<a href=\"nfs.htm\">NFS</a><br>\n";
   echo "<br>";
   echo "<a href=\"d.htm\">D</a><br>\n";
   echo "<br>";
   echo "<a href=\"e.htm\">E</a><br>\n";
   echo "<br>\n";
   echo "<br>\n";
   echo "WebGui<br>\n";
   echo "<a href=\"setup.htm\">Einstellungen</a><br>\n";
   for ($i=0; $i<15; $i++ ){
    echo "<br>\n";
    }
}
class sysinfo {
  var $inifile = "distros.ini";
  var $icon = "unknown.png";
  var $distro = "unknown";

  // get the distro name and icon when create the sysinfo object
  function sysinfo() {
   $list = @parse_ini_file(APP_ROOT . "/" . $this->inifile, true);
   if (!$list) {
    return;
   }
   foreach ($list as $section => $distribution) {
    if (!isset($distribution["Files"])) {
     continue;
    } else {
     foreach (explode(";", $distribution["Files"]) as $filename) {
      if (file_exists($filename)) {
       $buf = rfts( $filename );
       $this->icon = isset($distribution["Image"]) ? $distribution["Image"] : $this->icon;
       $this->distro = isset($distribution["Name"]) ? $distribution["Name"] . " " . trim($buf) : trim($buf);
       break 2;
      }
     }
    }
   }
  }

  // get our apache SERVER_NAME or vhost
  function vhostname () {
    if (! ($result = getenv('SERVER_NAME'))) {
      $result = 'N.A.';
    } 
    return $result;
  } 
  // get our canonical hostname
  function chostname () {
    $result = rfts( '/proc/sys/kernel/hostname', 1 );
    if ( $result == "ERROR" ) {
      $result = "N.A.";
    } else {
      $result = gethostbyaddr( gethostbyname( trim( $result ) ) );
    } 
    return $result;
  } 
  // get the IP address of our canonical hostname
  function ip_addr () {
    if (!($result = getenv('SERVER_ADDR'))) {
      $result = gethostbyname($this->chostname());
    } 
    return $result;
  } 

  function kernel () {
    $buf = rfts( '/proc/version', 1 );
    if ( $buf == "ERROR" ) {
      $result = "N.A.";
    } else {
      if (preg_match('/version (.*?) /', $buf, $ar_buf)) {
        $result = $ar_buf[1];

        if (preg_match('/SMP/', $buf)) {
          $result .= ' (SMP)';
        } 
      } 
    } 
    return $result;
  } 
  
  function uptime () {
    $buf = rfts( '/proc/uptime', 1 );
    $ar_buf = split( ' ', $buf );
    $result = trim( $ar_buf[0] );

    return $result;
  } 

  function users () {
    $who = split('=', execute_program('who', '-q'));
    $result = $who[1];
    return $result;
  } 

  function loadavg ($bar = false) {
    $buf = rfts( '/proc/loadavg' );
    if( $buf == "ERROR" ) {
      $results['avg'] = array('N.A.', 'N.A.', 'N.A.');
    } else {
      $results['avg'] = preg_split("/\s/", $buf, 4);
      unset($results['avg'][3]);	// don't need the extra values, only first three
    } 
    if ($bar) {
      $buf = rfts( '/proc/stat', 1 );
      if( $buf != "ERROR" ) {
	sscanf($buf, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
	// Find out the CPU load
	// user + sys = load 
	// total = total
	$load = $ab + $ac + $ad;	// cpu.user + cpu.sys
	$total = $ab + $ac + $ad + $ae;	// cpu.total

	// we need a second value, wait 1 second befor getting (< 1 second no good value will occour)
	sleep(1);
	$buf = rfts( '/proc/stat', 1 );
	sscanf($buf, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
	$load2 = $ab + $ac + $ad;
	$total2 = $ab + $ac + $ad + $ae;
	$results['cpupercent'] = (100*($load2 - $load)) / ($total2 - $total);
      }
    }
    return $results;
  } 

  function cpu_info () {
    $bufr = rfts( '/proc/cpuinfo' );

    if ( $bufr != "ERROR" ) {
      $bufe = explode("\n", $bufr);

      $results = array('cpus' => 0, 'bogomips' => 0);
      $ar_buf = array();
      
      foreach( $bufe as $buf ) {
       if($buf != "\n") {
        list($key, $value) = preg_split('/\s+:\s+/', trim($buf), 2); 
        // All of the tags here are highly architecture dependant.
        // the only way I could reconstruct them for machines I don't
        // have is to browse the kernel source.  So if your arch isn't
        // supported, tell me you want it written in.
        switch ($key) {
          case 'model name':
            $results['model'] = $value;
            break;
          case 'cpu MHz':
            $results['cpuspeed'] = sprintf('%.2f', $value);
            break;
          case 'cycle frequency [Hz]': // For Alpha arch - 2.2.x
            $results['cpuspeed'] = sprintf('%.2f', $value / 1000000);
            break;
          case 'clock': // For PPC arch (damn borked POS)
            $results['cpuspeed'] = sprintf('%.2f', $value);
            break;
          case 'cpu': // For PPC arch (damn borked POS)
            $results['model'] = $value;
            break;
          case 'L2 cache': // More for PPC
            $results['cache'] = $value;
            break;
          case 'revision': // For PPC arch (damn borked POS)
            $results['model'] .= ' ( rev: ' . $value . ')';
            break;
          case 'cpu model': // For Alpha arch - 2.2.x
            $results['model'] .= ' (' . $value . ')';
            break;
          case 'cache size':
            $results['cache'] = $value;
            break;
          case 'bogomips':
            $results['bogomips'] += $value;
            break;
          case 'BogoMIPS': // For alpha arch - 2.2.x
            $results['bogomips'] += $value;
            break;
          case 'BogoMips': // For sparc arch
            $results['bogomips'] += $value;
            break;
          case 'cpus detected': // For Alpha arch - 2.2.x
            $results['cpus'] += $value;
            break;
          case 'system type': // Alpha arch - 2.2.x
            $results['model'] .= ', ' . $value . ' ';
            break;
          case 'platform string': // Alpha arch - 2.2.x
            $results['model'] .= ' (' . $value . ')';
            break;
          case 'processor':
            $results['cpus'] += 1;
            break;
          case 'Cpu0ClkTck': // Linux sparc64
            $results['cpuspeed'] = sprintf('%.2f', hexdec($value) / 1000000);
            break;
          case 'Cpu0Bogo': // Linux sparc64 & sparc32
            $results['bogomips'] = $value;
            break;
          case 'ncpus probed': // Linux sparc64 & sparc32
            $results['cpus'] = $value;
            break;
        } 
       }
      } 
    } 


    $keys = array_keys($results);
    $keys2be = array('model', 'cpuspeed', 'cache', 'bogomips', 'cpus');

    while ($ar_buf = each($keys2be)) {
      if (! in_array($ar_buf[1], $keys)) {
        $results[$ar_buf[1]] = 'N.A.';
      } 
    } 
    return $results;
  } 

  function pci () {
    $results = array();

    if ($_results = execute_program('lspci', '', false)) {
      $lines = split("\n", $_results);
      for ($i = 0, $max = sizeof($lines); $i < $max; $i++) {
        list($addr, $name) = explode(' ', trim($lines[$i]), 2);

        if (!preg_match('/bridge/i', $name) && !preg_match('/USB/i', $name)) {
          // remove all the version strings
          $name = preg_replace('/\(.*\)/', '', $name);
	  // is addr really usefull for this??? i think it's not
          // $results[] = $addr . ' ' . $name;
	  $results[] = $name;
        } 
      } 
    } else {
      $bufr = rfts( '/proc/pci' );
      foreach( $bufr as $buf ) {
        if (preg_match('/Bus/', $buf)) {
          $device = true;
          continue;
        } 

        if ($device) {
          list($key, $value) = split(': ', $buf, 2);

          if (!preg_match('/bridge/i', $key) && !preg_match('/USB/i', $key)) {
            $results[] = preg_replace('/\([^\)]+\)\.$/', '', trim($value));
          } 
          $device = false;
        } 
      } 
    } 
    asort($results);
    return $results;
  } 

  function ide () {
    $results = array();
    $bufd = gdc( '/proc/ide' );

    foreach( $bufd as $file ) {
      if (preg_match('/^hd/', $file)) {
        $results[$file] = array(); 
        // Check if device is CD-ROM (CD-ROM capacity shows as 1024 GB)
	$buf = rfts("/proc/ide/" . $file . "/media", 1 );
        if ( $buf != "ERROR" ) {
          $results[$file]['media'] = trim($buf);
          if ($results[$file]['media'] == 'disk') {
            $results[$file]['media'] = 'Hard Disk';
          } 

          if ($results[$file]['media'] == 'cdrom') {
            $results[$file]['media'] = 'CD-ROM';
          } 
        } 

	$buf = rfts( "/proc/ide/" . $file . "/model", 1 );
        if ( $buf != "ERROR" ) {
          $results[$file]['model'] = trim( $buf );
          if (preg_match('/WDC/', $results[$file]['model'])) {
            $results[$file]['manufacture'] = 'Western Digital';
          } elseif (preg_match('/IBM/', $results[$file]['model'])) {
            $results[$file]['manufacture'] = 'IBM';
          } elseif (preg_match('/FUJITSU/', $results[$file]['model'])) {
            $results[$file]['manufacture'] = 'Fujitsu';
          } else {
            $results[$file]['manufacture'] = 'Unknown';
          } 
        } 
	
	$buf = rfts( "/proc/ide/" . $file . "/capacity", 1);
	if( $buf == "ERROR" )
	  $buf = rfts( "/sys/block/" . $file . "/size", 1);

        if ( $buf != "ERROR" ) {
          $results[$file]['capacity'] = trim( $buf );
          if ($results[$file]['media'] == 'CD-ROM') {
            unset($results[$file]['capacity']);
          } 
        } 
      } 
    } 

    asort($results);
    return $results;
  } 

  function scsi () {
    $results = array();
    $dev_vendor = '';
    $dev_model = '';
    $dev_rev = '';
    $dev_type = '';
    $s = 1;
    $get_type = 0;

    $bufr = rfts( '/proc/scsi/scsi' );
    if ( $bufr != "ERROR" ) {
      $bufe = explode("\n", $bufr);
      foreach( $bufe as $buf ) {
        if (preg_match('/Vendor/', $buf)) {
          preg_match('/Vendor: (.*) Model: (.*) Rev: (.*)/i', $buf, $dev);
          list($key, $value) = split(': ', $buf, 2);
          $dev_str = $value;
          $get_type = true;
          continue;
        } 

        if ($get_type) {
          preg_match('/Type:\s+(\S+)/i', $buf, $dev_type);
          $results[$s]['model'] = "$dev[1] $dev[2] ($dev_type[1])";
          $results[$s]['media'] = "Hard Disk";
          $s++;
          $get_type = false;
        } 
      } 
    } 
    asort($results);
    return $results;
  } 

  function usb () {
    $results = array();
    $devnum = -1;

    $bufr = rfts( '/proc/bus/usb/devices' );
    if ( $bufr != "ERROR" ) {
      $bufe = explode("\n", $bufr);
      foreach( $bufe as $buf ) {
        if (preg_match('/^T/', $buf)) {
          $devnum += 1;
	  $results[$devnum] = "";
        } elseif (preg_match('/^S:/', $buf)) {
          list($key, $value) = split(': ', $buf, 2);
          list($key, $value2) = split('=', $value, 2);
	  if (trim($key) != "SerialNumber") {
            $results[$devnum] .= " " . trim($value2);
            $devstring = 0;
	  }
        } 
      }
    } 
    return $results;
  } 

  function sbus () {
    $results = array();
    $_results[0] = ""; 
    // TODO. Nothing here yet. Move along.
    $results = $_results;
    return $results;
  } 

  function network () {
    $results = array();

    $bufr = rfts( '/proc/net/dev' );
    if ( $bufr != "ERROR" ) {
      $bufe = explode("\n", $bufr);
      foreach( $bufe as $buf ) {
        if (preg_match('/:/', $buf)) {
          list($dev_name, $stats_list) = preg_split('/:/', $buf, 2);
          $stats = preg_split('/\s+/', trim($stats_list));
          $results[$dev_name] = array();

          $results[$dev_name]['rx_bytes'] = $stats[0];
          $results[$dev_name]['rx_packets'] = $stats[1];
          $results[$dev_name]['rx_errs'] = $stats[2];
          $results[$dev_name]['rx_drop'] = $stats[3];

          $results[$dev_name]['tx_bytes'] = $stats[8];
          $results[$dev_name]['tx_packets'] = $stats[9];
          $results[$dev_name]['tx_errs'] = $stats[10];
          $results[$dev_name]['tx_drop'] = $stats[11];

          $results[$dev_name]['errs'] = $stats[2] + $stats[10];
          $results[$dev_name]['drop'] = $stats[3] + $stats[11];
        } 
      }
    }
    return $results;
  } 

  function memory () {
    $results['ram'] = array();
    $results['swap'] = array();
    $results['devswap'] = array();

    $bufr = rfts( '/proc/meminfo' );
    if ( $bufr != "ERROR" ) {
      $bufe = explode("\n", $bufr);
      foreach( $bufe as $buf ) {
        if (preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['total'] = $ar_buf[1];
        } else if (preg_match('/^MemFree:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['t_free'] = $ar_buf[1];
        } else if (preg_match('/^Cached:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['cached'] = $ar_buf[1];
        } else if (preg_match('/^Buffers:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['ram']['buffers'] = $ar_buf[1];
        } else if (preg_match('/^SwapTotal:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['swap']['total'] = $ar_buf[1];
        } else if (preg_match('/^SwapFree:\s+(.*)\s*kB/i', $buf, $ar_buf)) {
          $results['swap']['free'] = $ar_buf[1];
        } 
      } 

      $results['ram']['t_used'] = $results['ram']['total'] - $results['ram']['t_free'];
      $results['ram']['percent'] = round(($results['ram']['t_used'] * 100) / $results['ram']['total']);
      $results['swap']['used'] = $results['swap']['total'] - $results['swap']['free'];
      $results['swap']['percent'] = round(($results['swap']['used'] * 100) / $results['swap']['total']);
      
      // values for splitting memory usage
      if (isset($results['ram']['cached']) && isset($results['ram']['buffers'])) {
        $results['ram']['app'] = $results['ram']['t_used'] - $results['ram']['cached'] - $results['ram']['buffers'];
	$results['ram']['app_percent'] = round(($results['ram']['app'] * 100) / $results['ram']['total']);
	$results['ram']['buffers_percent'] = round(($results['ram']['buffers'] * 100) / $results['ram']['total']);
	$results['ram']['cached_percent'] = round(($results['ram']['cached'] * 100) / $results['ram']['total']);
      }

      $bufr = rfts( '/proc/swaps' );
      if ( $bufr != "ERROR" ) {
        $swaps = explode("\n", $bufr);
        for ($i = 1; $i < (sizeof($swaps)); $i++) {
	  if( trim( $swaps[$i] ) != "" ) {
            $ar_buf = preg_split('/\s+/', $swaps[$i], 6);
            $results['devswap'][$i - 1] = array();
            $results['devswap'][$i - 1]['dev'] = $ar_buf[0];
            $results['devswap'][$i - 1]['total'] = $ar_buf[2];
            $results['devswap'][$i - 1]['used'] = $ar_buf[3];
            $results['devswap'][$i - 1]['free'] = ($results['devswap'][$i - 1]['total'] - $results['devswap'][$i - 1]['used']);
            $results['devswap'][$i - 1]['percent'] = round(($ar_buf[3] * 100) / $ar_buf[2]);
	  }
        } 
      }
    }
    return $results;
  } 

  function filesystems () {
    global $show_bind;
    $fstype = array();
    $fsoptions = array();

    $df = execute_program('df', '-kP');
    $mounts = split("\n", $df);

    $buffer = execute_program("mount");
    $buffer = explode("\n", $buffer);

    $j = 0;
    foreach($buffer as $line) {
      preg_match("/(.*) on (.*) type (.*) \((.*)\)/", $line, $result);
      if (count($result) == 5) {
        $dev = $result[1]; $mpoint = $result[2]; $type = $result[3]; $options = $result[4];
        $fstype[$mpoint] = $type; $fsdev[$dev] = $type; $fsoptions[$mpoint] = $options;

        foreach ($mounts as $line2) {
          if (preg_match("#^" . str_replace("\$","\\$",$result[1]) . "#", $line2)) {
            $line2 = preg_replace("#^" . str_replace("\$","\\$",$result[1]) . "#", "", $line2);
            $ar_buf = preg_split("/(\s+)/", $line2, 6);
            $ar_buf[0] = $result[1];

            if (hide_mount($ar_buf[5]) || $ar_buf[0] == "") {
              continue;
            }

            if ($show_bind || !stristr($fsoptions[$ar_buf[5]], "bind")) {
              $results[$j] = array();
              $results[$j]['disk'] = $ar_buf[0];
              $results[$j]['size'] = $ar_buf[1];
              $results[$j]['used'] = $ar_buf[2];
              $results[$j]['free'] = $ar_buf[3];
              $results[$j]['percent'] = round(($results[$j]['used'] * 100) / $results[$j]['size']);
              $results[$j]['mount'] = $ar_buf[5];
              ($fstype[$ar_buf[5]]) ? $results[$j]['fstype'] = $fstype[$ar_buf[5]] : $results[$j]['fstype'] = $fsdev[$ar_buf[0]];
              $results[$j]['options'] = $fsoptions[$ar_buf[5]];
              $j++;
            }
          }
	}
      }
    }
    return $results;
  } 

  function distro () {
   return $this->distro;
  }

  function distroicon () {   
   return $this->icon;
  }

} 

?>
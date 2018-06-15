<?php

// Bandwidth graphs
$bw_graphs = array(
    '/stats/eth0-hour.png',
    '/stats/eth0-day.png',
    '/stats/eth0-month.png',
);

// Interfaces
$interfaces = array('eth0');

// Disks
$disks = array(
	array(
		'name' => 'System',
		'dir' => '/',
	),
	array(
		'name' => 'Example Array',
		'dir' => '/mnt/example/',
	),
);

if ($_SERVER['QUERY_STRING'] === 'phpinfo') {
    phpinfo();
    exit;
} elseif ($_SERVER['QUERY_STRING'] === 'source') {
    ini_set('highlight.default', '#bd7200');
    ini_set('highlight.keyword', '#626bf0');
    ini_set('highlight.string', '#5ffa68');
    ini_set('highlight.comment', '#7f7f7f');
    ini_set('highlight.html', '#ffff67');

    echo '<title>Source code for '.basename(__FILE__).'</title>';
    echo '<style>* { background: #202030 }</style>';
    highlight_file(__FILE__);
    exit;
}

// Functions
function make_size($size) {
    static $units = array('B', 'kB', 'MB', 'GB');
    if (!$size)
        return '0 B';

    for ($i = 0; $i < 4; $i++)
        if ($size >= pow(1024, $i) && $size < pow(1024, $i + 1))
            return sprintf('%0.2f %s', $size / pow(1024, $i), $units[$i]);

    return sprintf('%0.2f TB', $size / pow(1024, 4));
}

function tableize($command, $join = array()) {
    static $tables = array();

    if (!isset($tables[$command])) {
        $out = rtrim(shell_exec($command));
        foreach ($join as $h) {
            $out = str_replace($h, str_replace(' ', '&nbsp;', $h), $out);
        }

        $rows = 0;

        $table = '<table>';
        foreach (preg_split('/\r?\n|\r/', $out) as $row) {
            $cells = 0;

            $table .= '<tr>';
            foreach (preg_split('/\s+/', $row) as $cell) {
                $type = ($cells && $rows) ? 'td' : 'th';
                $table .= '<'.$type.'>';
                $table .= $cell;
                $table .= '</'.$type.'>';
                $cells++;
            }
            $table .= '</tr>';
            $rows++;
        }
        $table .= '</table>';

        $tables[$command] = $table;
    }

    return $tables[$command];
}

function calculate_age($timestamp, $comparison = null) {
    static $units = array('second' => 60, 'minute' => 60, 'hour' => 24,
        'day' => 7, 'week' => 4.35, 'month' => 12);
    if ($comparison === null)
        $comparison = $_SERVER['REQUEST_TIME'];

    $curunit = abs($comparison-$timestamp);
    foreach ($units as $unit => $max) {
        $next = $curunit / $max;
        if ($next < 1)
            return floor($curunit).' '.$unit.(floor($curunit) == 1 ? '' : 's');
        $curunit = $next;
    }

    return round($curunit, 1).' year'.(floor($curunit) == 1 ? '' : 's');
}

function uptime() {
    $uptime = file('/proc/uptime');
    $uptime = preg_split('/\s+/', $uptime[0]);
    return intval($uptime[0]);
}

function parse_vnstat($iface = 'eth0') {
    static $vnstat;
    if (isset($vnstat[$iface]))
        return $vnstat;

    $vnstat[$iface] = explode(';', rtrim(shell_exec("vnstat --oneline -i $iface")));
    return $vnstat[$iface];
}

$uptime = uptime();
$up_since = $_SERVER['REQUEST_TIME'] - $uptime;

// Disk space
foreach ($disks as &$disk) {
    $disk['spacefree'] = disk_free_space($disk['dir']);
    $disk['spacetotal'] = disk_total_space($disk['dir']);
    $disk['spaceused'] = $disk['spacetotal'] - $disk['spacefree'];
    $disk['spacepercent'] = $disk['spacetotal']
        ? round(100 / $disk['spacetotal'] * $disk['spaceused'], 2)
        : 100;
}
unset($disk);

// vnstat
$bwstats = array();
foreach ($interfaces as $iface)
    $bwstats[$iface] = parse_vnstat($iface);

// html
?>
<!DOCTYPE html>
<html>
<head>
    <title>neotheone.se</title>
    <meta charset="utf-8">
    <style type="text/css">
        html {
            background-color: #0a0a0a;
        }
        body {
            background-color: #D3433B;
            border-radius: 25px;
            box-shadow: 0 1px 2px #707070;
            color: #0a0a0a;
            font-family: arial;
            margin: 25px auto;
            padding: 1px 25px 5px;
            width: 850px;
        }
        h1 {
            border-bottom: solid 1px #a0a0a0;
            padding-bottom: 2px;
        }
        div.meter {
            background-color: #ddd;
            background-image: linear-gradient(top, #606060, #fafafa);
            background-image: -o-linear-gradient(top, #606060, #fafafa);
            background-image: -moz-linear-gradient(top, #606060, #fafafa);
            background-image: -webkit-linear-gradient(top, #606060, #fafafa);
            border-radius: 15px;
            box-shadow: 3px 3px 7px #707070;
        }
        div.bar {
            background-color: #eee;
            background-image: linear-gradient(top, #92CF00, #92CF00);
            background-image: -o-linear-gradient(top, #92CF00, #92CF00);
            background-image: -moz-linear-gradient(top, ##92CF00, #92CF00);
            background-image: -webkit-linear-gradient(top, #92CF00, #92CF00);
            border-radius: 15px;
            color: #0a0a0a;
            font-weight: bold;
            padding: 5px 0;
            text-shadow: 0 0 5px #fafafa;
            text-indent: 10px;
            white-space: nowrap;
        }
        table {
            border-collapse: collapse;
            border: solid 1px #a0a0a0;
        }
        th, td {
            border: solid 1px #a0a0a0;
            padding: .25em .5em;
        }
        th {
            background-color: #606060;
        }
        td {
            background-color: #fafafa;
        }
        a {
            color: #00e;
            text-decoration: none;
        }
        a:hover {
            color: #d00;
        }
        hr {
            border: none;
            border-top: solid 1px #a0a0a0;
        }
        div#footer {
            font-size: 10pt;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Operating system</h1>
    <p><?php echo htmlspecialchars(trim(shell_exec('lsb_release -sd'))) ?></p>
    <h1>Uptime</h1>
    <p>Been up for <strong><?php echo calculate_age($up_since) ?></strong>, since <strong><?php echo gmdate('Y-m-d, g:ia', $up_since) ?></strong>.</p>
    <p><code><?php echo htmlspecialchars(trim(shell_exec('uptime'))) ?></code></p>
    <h1>Volumes</h1>
    <?php foreach ($disks as $dir => $disk): ?>
        <h2><?php echo $disk['name'] ?></h2>
        <div class="meter">
            <div class="bar" style="width: <?php echo $disk['spacepercent'] ?>%"><?php echo make_size($disk['spaceused']) ?> / <?php echo make_size($disk['spacetotal']) ?> (<?php echo make_size($disk['spacefree']) ?> free)</div>
        </div>
    <?php endforeach ?>
    <h2>Mount points</h2>
    <?php echo tableize('df -h', array('Mounted on')) ?>
    <h1>Memory</h1>
    <?php echo tableize('free -m') ?>
    <h1>Bandwidth</h1>
    <?php foreach ($bwstats as $bw): ?>
        <h2><?php echo $bw[1] ?></h2>
        <table>
            <tr>
                <th>Average today</th>
                <td><?php echo $bw[11] ?></td>
                <th>Received this month</th>
                <td><?php echo $bw[8] ?></td>
                <th>All-time received</th>
                <td><?php echo $bw[12] ?></td>
            </tr>
            <tr>
                <th>Received today</th>
                <td><?php echo $bw[3] ?></td>
                <th>Transmitted this month</th>
                <td><?php echo $bw[9] ?></td>
                <th>All-time transmitted</th>
                <td><?php echo $bw[13] ?></td>
            </tr>
            <tr>
                <th>Transmitted today</th>
                <td><?php echo $bw[4] ?></td>
                <th>Total for this month</th>
                <td><?php echo $bw[10] ?></td>
                <th>All-time total</th>
                <td><?php echo $bw[14] ?></td>
            </tr>
        </table>
    <?php endforeach ?>
    <h2>Graphs</h2>
    <?php foreach($bw_graphs as $graph): ?>
        <p><img src="<?php echo $graph ?>" alt=""></p>
    <?php endforeach ?>
    <hr>
    <div id="footer">
        <p><a href="?source">Source code</a></p>
    </div>
</body>
</html>
<?php

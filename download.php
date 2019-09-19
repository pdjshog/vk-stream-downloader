<?php

function get($link)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $link);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');

    if (!$html = curl_exec($ch)) {
        return 'Ошибка curl: ' . curl_error($ch);
    }

    curl_close($ch);
    return $html;
}

function progressBar($done, $total, $size = 60, $lineWidth = -1)
{
    if ($lineWidth <= 0) {
        $lineWidth = $_ENV['COLUMNS'];
    }

    static $start_time;

    // to take account for [ and ]
    $size -= 3;
    // if we go over our bound, just ignore it
    if ($done > $total) {
        return;
    }

    if (empty($start_time)) {
        $start_time = time();
    }
    $now = time();

    $perc = (double)($done / $total);

    $bar = floor($perc * $size);

    // jump to the begining
    echo "\r";
    // jump a line up
    echo "\x1b[A";

    $status_bar = '[';
    $status_bar .= str_repeat('=', $bar);
    if ($bar < $size) {
        $status_bar .= '>';
        $status_bar .= str_repeat(' ', $size - $bar);
    } else {
        $status_bar .= '=';
    }

    $disp = number_format($perc * 100, 0);

    $status_bar .= ']';
    $details = "$disp%  $done/$total";

    $rate = ($now - $start_time) / $done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);

    $elapsed = $now - $start_time;

    $details .= ' ' . secToFormated($eta, 'h:i:s') . ' ' . secToFormated($elapsed, 'h:i:s') . '  ';

    $lineWidth--;
    if (strlen($details) >= $lineWidth) {
        $details = substr($details, 0, $lineWidth - 1);
    }
    echo "$details\n$status_bar";

    flush();

    // when done, send a newline
    if ($done == $total) {
        echo '\n';
    }

}

function secToFormated($init, $format = 'h:i:s')
{
    $hours = floor($init / 3600);
    if ($hours < 10) {
        $hours = '0' . $hours;
    }

    $minutes = floor(($init / 60) % 60);
    if ($minutes < 10) {
        $minutes = '0' . $minutes;
    }

    $seconds = $init % 60;
    if ($seconds < 10) {
        $seconds = '0' . $seconds;
    }

    return str_replace(array('h', 'i', 's'), array($hours, $minutes, $seconds), $format);
}

$url = "https://vk.com/video676806_456240550";

$re = '/#EXTM3U\\\\n(.*)https:(.*)\\\\n/m';
$str = get($url);
preg_match_all($re, $str, $matches);


$playlist = str_replace('\/', '/', $matches[2][0]);
$playlistURL = "https:$playlist";
$plist = get($playlistURL);

$playlistParts = explode('/', $playlist);
array_pop($playlistParts);
$prefix = "https:" . implode('/', $playlistParts);

$plistArr = explode("\n", $plist);

mkdir('temp/');
$filename = date('H_i_s') . '_video.ts';

$total = count($plistArr);

foreach ($plistArr as $iterate => $item) {
    progressBar($iterate + 1, $total, 60, 0);
    if (substr($item, 0, 1) == '#') {
        continue;
    }

    file_put_contents($filename, file_get_contents($prefix . '/' . $item), FILE_APPEND);
}

//shell_exec("ffmpeg -i {$filename} -f mp3 -ab 320000 -vn music.mp3");
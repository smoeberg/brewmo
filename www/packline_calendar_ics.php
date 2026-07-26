<?php
$res=0;
$paths=array(
    __DIR__.'/../../main.inc.php',
    __DIR__.'/../../../main.inc.php',
    __DIR__.'/../../../../main.inc.php',
    __DIR__.'/../main.inc.php',
    __DIR__.'/main.inc.php'
);
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }
$days=(int)GETPOST('days','int'); if ($days<=0) $days=30;
$line=(int)GETPOST('line','int'); // optional
$tz='UTC'; // keep UTC for ICS
$now=date('Y-m-d H:i:s');
$to=date('Y-m-d H:i:s', strtotime('+'.$days.' days'));
$sql="SELECT b.rowid,b.title,b.start_datetime,b.end_datetime,b.status,l.ref as lineref,l.label as linelabel
      FROM ".MAIN_DB_PREFIX."brew_packline_bookings b
      LEFT JOIN ".MAIN_DB_PREFIX."brew_packaging_lines l ON l.rowid=b.fk_packaging_line
      WHERE b.entity=".(int)$conf->entity." AND b.start_datetime >= '".$db->escape($now)."' AND b.start_datetime <= '".$db->escape($to)."'";
if ($line>0) $sql.=" AND b.fk_packaging_line=".(int)$line;
$sql.=" ORDER BY b.start_datetime ASC";
$res=$db->query($sql);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="brewmo_packline.ics"');

function ics_dt($s){ return gmdate('Ymd\THis\Z', strtotime($s)); }

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Brewmo//Packline Calendar//DA\r\n";

if ($res){ while($o=$db->fetch_object($res)){
    $uid = 'brewmo-packline-'.$o->rowid.'@'.$_SERVER['SERVER_NAME'];
    $title = $o->title.' ['.$o->lineref.' - '.$o->linelabel.']';
    echo "BEGIN:VEVENT\r\n";
    echo "UID:".$uid."\r\n";
    echo "DTSTAMP:".ics_dt($o->start_datetime)."\r\n";
    echo "DTSTART:".ics_dt($o->start_datetime)."\r\n";
    echo "DTEND:".ics_dt($o->end_datetime)."\r\n";
    echo "SUMMARY:".str_replace(array("\\",";",",","\n"), array("\\\\","\;","\,","\\n"), $title)."\r\n";
    echo "STATUS:".strtoupper($o->status)."\r\n";
    echo "END:VEVENT\r\n";
}}

echo "END:VCALENDAR\r\n";
exit;

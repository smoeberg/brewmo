<?php
// Diagnostic for Brewmo module visibility/activation
header('Content-Type: text/plain; charset=UTF-8');

$report = [];

function add(&$r,$k,$v){ $r[$k]=$v; }

$report['time'] = date('c');
$report['php_version'] = PHP_VERSION;

$res=0;
$paths=array(__DIR__.'/../../main.inc.php',__DIR__.'/../../../main.inc.php',__DIR__.'/../../../../main.inc.php', $_SERVER['DOCUMENT_ROOT'].'/main.inc.php');
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
add($report,'main_inc_included',$res?true:false);
add($report,'DOL_DOCUMENT_ROOT', defined('DOL_DOCUMENT_ROOT')?DOL_DOCUMENT_ROOT:null);

$base = dirname(__DIR__);
$moddir = $base.'/core/modules';
add($report,'module_dir',$moddir);
add($report,'module_dir_exists', is_dir($moddir));
$files = @scandir($moddir);
add($report,'module_dir_list',$files);

$descriptor = $moddir.'/modBrewmo.class.php';
add($report,'descriptor_path',$descriptor);
add($report,'descriptor_exists', file_exists($descriptor));
add($report,'descriptor_readable', is_readable($descriptor));

$code = @file_exists($descriptor)? @file_get_contents($descriptor) : null;
add($report,'descriptor_first_line', $code? substr($code,0,120) : null);

$cls_ok = false; $err = null;
if (file_exists($descriptor)) {
    try {
        require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
        require_once $descriptor;
        if (class_exists('modBrewmo')){
            $cls_ok = true;
            $tmp = new modBrewmo($GLOBALS['db']);
            add($report,'class_constructed', is_object($tmp));
            add($report,'const_name', $tmp->const_name);
            add($report,'rights_class', $tmp->rights_class);
        } else {
            $err = 'Class modBrewmo not found after include';
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}
add($report,'class_exists', $cls_ok);
add($report,'error', $err);

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

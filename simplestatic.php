<?php

function log_exception(Throwable $e)
{
    exit($e);
}

set_exception_handler('log_exception');
set_error_handler(function ($num, $str, $file, $line, $context = null) {
    log_exception(new ErrorException($str, 0, $num, $file, $line));
});

define('_DS_', DIRECTORY_SEPARATOR);

$wd = $argv[1] ?? getcwd();
if (! is_dir($wd)) {
    exit("Invalid working directory: $wd");
}

$configFilename =  $wd._DS_.'simplestatic.json';
if (is_file($configFilename)) {
    $config = json_decode(file_get_contents($configFilename), true);
    if (empty($config)) {
        exit("Invalid config file format: {$configFilename}  - JSON is expected.");
    }
}

$rootDir = $config['root-dir'] ?? $wd;
$pageDir = $config['page-dir'] ?? $rootDir._DS_.'pages';
$templateDir = $config['template-dir'] ?? $rootDir._DS_.'templates';
$layoutFile = $config['layout-file'] ?? $templateDir._DS_.'layout.php';
$publicDir = $config['public-dir'] ?? $rootDir._DS_.'public';
$staticExt = $config['static-ext'] ?? 'html';

echo "\nSimpleStatic starts working at $wd \n---\n";
$staticFileCount = 0;
foreach (scandir($pageDir) as $filename) {
    $pageFile = $pageDir._DS_.$filename;
    if (is_file($pageFile)) {
        $pageFile = realpath($pageFile);
        $pageFileInfo = pathinfo($pageFile);
        $pageFileBasename = $pageFileInfo['extension'] !== $staticExt ?
            $pageFileInfo['filename'].".$staticExt"
            : $pageFileInfo['basename'];
        $staticFile = $publicDir._DS_.$pageFileBasename;
        echo "Making {$pageFileBasename} ... ";
        ob_start();
        include $layoutFile;
        $staticContents = ob_get_clean();
        $result = file_put_contents($staticFile, $staticContents);
        if (FALSE === $result) {
            exit("Failed.\n");
        }
        $staticFileCount += 1;
        echo number_format($result) . " bytes Done.\n";
    }
} // foreach

echo "---\n$staticFileCount static files were made into $publicDir.\n";

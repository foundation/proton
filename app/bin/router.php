<?php

// Router script for PHP built-in server with live reload support.
// Used by DevServer — not meant to be run directly.

$reloadFile = getenv('PROTON_RELOAD_FILE');
$uri        = $_SERVER['REQUEST_URI'];

// 1. /__reload endpoint — returns last build timestamp
if ($uri === '/__reload') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    $timestamp = '0';
    if ($reloadFile && file_exists($reloadFile)) {
        $timestamp = trim(file_get_contents($reloadFile));
    }
    echo json_encode(['ts' => $timestamp]);

    return true;
}

// 2. Let PHP serve static files normally for non-HTML requests
$path    = parse_url((string)$uri, PHP_URL_PATH);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$file    = $docRoot . $path;

// If file exists and is not a directory, check if it's HTML
if (is_file($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'html' || $ext === 'htm') {
        // Inject live reload script into HTML
        $html   = file_get_contents($file);
        $script = <<<'SCRIPT'
<script>
(function(){
  var lastTs = null;
  setInterval(function(){
    fetch('/__reload').then(function(r){return r.json()}).then(function(d){
      if(lastTs === null){lastTs = d.ts; return;}
      if(d.ts !== lastTs){location.reload();}
    }).catch(function(){});
  }, 500);
})();
</script>
SCRIPT;
        $html = str_replace('</body>', $script . "\n</body>", $html);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;

        return true;
    }

    // Non-HTML file — let PHP serve it
    return false;
}

// Directory request — check for index.html
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.html';
    if (is_file($index)) {
        $html   = file_get_contents($index);
        $script = <<<'SCRIPT'
<script>
(function(){
  var lastTs = null;
  setInterval(function(){
    fetch('/__reload').then(function(r){return r.json()}).then(function(d){
      if(lastTs === null){lastTs = d.ts; return;}
      if(d.ts !== lastTs){location.reload();}
    }).catch(function(){});
  }, 500);
})();
</script>
SCRIPT;
        $html = str_replace('</body>', $script . "\n</body>", $html);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;

        return true;
    }
}

// File not found — let PHP handle the 404
return false;

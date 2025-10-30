<?php
// convertarraytojson.php
// Usage: php convertarraytojson.php [input_path] [output_path]
// Defaults:
//   input_path  = src\\indicators\\olddata.txt
//   output_path = src\\indicators\\olddata.json

$inPath = $argv[1] ?? 'src\\indicators\\olddata.txt';
$outPath = $argv[2] ?? 'src\\indicators\\olddata.json';

if (!is_file($inPath)) {
    fwrite(STDERR, "Input file not found: {$inPath}\n");
    exit(1);
}

$lines = file($inPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$result = [];
$current =& $result;
$stack = [];

foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || $trim === '(') {
        continue;
    }

    if ($trim === ')') {
        if (!empty($stack)) {
            // pop to parent level
            $parent =& $stack[count($stack) - 1];
            array_pop($stack);
            $current =& $parent;
        }
        continue;
    }

    // Expect lines like: [key] => value
    if (strpos($line, '=>') !== false) {
        [$left, $right] = explode('=>', $line, 2);
        $key = trim($left);
        // remove surrounding brackets like [key]
        $key = trim($key, "[] \t\r\n");
        $value = trim($right);

        if ($value === 'Array') {
            // descend into new array level
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            // push current by reference, then move into child
            $stack[] =& $current;
            $current =& $current[$key];
        } else {
            // normalize scalar value
            $v = $value;
            // strip surrounding quotes if present
            if ((strlen($v) >= 2) && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                $v = substr($v, 1, -1);
            }
            // cast numeric strings
            if (is_numeric($v)) {
                $v = (strpos($v, '.') !== false) ? (float)$v : (int)$v;
            }
            $current[$key] = $v;
        }
    }
}

$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Failed to encode JSON: " . json_last_error_msg() . "\n");
    exit(2);
}

// Ensure output directory exists
$dir = dirname($outPath);
if (!is_dir($dir)) {
    if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
        fwrite(STDERR, "Failed to create output directory: {$dir}\n");
        exit(3);
    }
}

file_put_contents($outPath, $json);
echo "Written JSON to {$outPath}\n";

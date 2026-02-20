<?php
$reportPath = __DIR__ . '/storage/app/swagger_unresolved_report.json';
$outPath = __DIR__ . '/config/swagger_request_map_extended.php';
$json = file_get_contents($reportPath);
$list = json_decode($json, true);
$lines = ["<?php", "", "return ["];
foreach ($list as $entry) {
    $controller = $entry['controller'];
    $method = $entry['controller_method'];
    $key = $controller . '@' . $method;
    $keyEscaped = str_replace(["\\", "'"], ["\\\\", "\\'"], $key);
    $lines[] = "    '{$key}' => ['data' => 'nullable|array'],";
}
$lines[] = "];";
file_put_contents($outPath, implode("\n", $lines));
echo "Written " . count($list) . " entries to " . $outPath . "\n";

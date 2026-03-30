<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$sql = <<<SQL
UPDATE pageview_analytics
SET page_slug = CASE
  WHEN page_path LIKE '/property/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/property/') + 1), '/', 1)
  WHEN page_path LIKE '/ar/property/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/ar/property/') + 1), '/', 1)
  WHEN page_path LIKE '/en/property/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/en/property/') + 1), '/', 1)
  WHEN page_path LIKE '/project/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/project/') + 1), '/', 1)
  WHEN page_path LIKE '/ar/project/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/ar/project/') + 1), '/', 1)
  WHEN page_path LIKE '/en/project/%' THEN SUBSTRING_INDEX(SUBSTRING(page_path, LENGTH('/en/project/') + 1), '/', 1)
  ELSE page_slug
END
WHERE (page_slug IS NULL OR page_slug = '')
  AND page_type IN ('property', 'project')
SQL;

$ok = DB::statement($sql);

echo $ok ? "OK\n" : "NOOP\n";


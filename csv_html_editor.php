<?php
/**
 * csv_html_editor.php
 * CSV HTML Editor (cleaned / documented)
 *
 * Usage:
 *   Place this file in a web-accessible directory alongside a subfolder `tables/`.
 *   Open in your browser: csv_html_editor.php?csv_filename=data.csv
 *
 * Notes:
 * - The script only opens CSV files that already exist in tables/.
 * - Versions are stored in tables/versions/<basename>_versions/
 * - Translations are loaded from translations.json (if present) and merged with defaults.
 *
 * Maintainers: keep translations.json up-to-date for new labels.
 */

/* --------------------------------------------------------------------------
   Configuration (change if needed)
   -------------------------------------------------------------------------- */
declare(strict_types=1);

$MAX_VERSIONS = 20;
$CSV_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . 'tables';
$TRANSLATIONS_JSON = __DIR__ . DIRECTORY_SEPARATOR . 'translations.json';

/* --------------------------------------------------------------------------
   Ensure the tables directory exists
   -------------------------------------------------------------------------- */
if (!is_dir($CSV_FOLDER)) {
    @mkdir($CSV_FOLDER, 0755, true);
}

/* --------------------------------------------------------------------------
   Helpers: filename validation
   These prevent path traversal and restrict allowed characters.
   -------------------------------------------------------------------------- */
function is_safe_csv_filename(string $name): bool
{
    // Allow only basename with alphanum, dot, underscore, hyphen ending with .csv
    return (bool) preg_match('/^[A-Za-z0-9._-]+\.csv$/i', $name);
}

function is_safe_version_filename(string $name): bool
{
    // Version filenames are sanitized basenames (no path separators)
    return (bool) preg_match('/^[A-Za-z0-9._-]+$/', $name);
}

/* --------------------------------------------------------------------------
   Load translations (JSON file optional)
   - Default translations are embedded; translations.json will override keys.
   -------------------------------------------------------------------------- */
$default_translations = [
    'en' => [
        'no_filename_title' => 'CSV Editor — No filename provided',
        'no_filename_msg' => 'This application must be opened with a csv_filename URL parameter.',
        'example' => 'Example:',
        'allowed_chars' => 'Allowed filename characters: letters, numbers, dot, hyphen, underscore. The filename must end with .csv. The file must already exist inside the tables/ directory.',
        'file_not_found_title' => 'CSV Editor — File not found',
        'file_not_found_msg' => 'Requested CSV file was not found in the tables directory.',
        'back' => 'Back',
        'save' => 'Save (creates version)',
        'download_current' => 'Download current CSV',
        'file_to_edit' => 'Editing file:',
        'files_stored_in' => 'Files & versions stored in',
        'add_row' => 'Add row at end',
        'trash_title' => 'Trash (client-side, persisted in session)',
        'trash_restore_all' => 'Restore All',
        'trash_is_empty' => 'Trash is empty.',
        'trash_empty' => 'Empty Trash',
        'versions_title' => 'Server-side Versions (recoverable)',
        'versions_note' => 'Saved snapshots (newest first). You can Show Diff to compare a version with the current CSV before Restore. Restoring will back up the current CSV automatically.',
        'diff_title' => 'Diff Preview',
        'no_diff_selected' => 'No diff selected. Click "Show diff" for a version to preview differences.',
        'restore_version_confirm' => 'Restore this version and overwrite current CSV? Current CSV will be backed up.',
        'restore_all_confirm' => 'Restore all rows from Trash?',
        'empty_trash_confirm' => 'Permanently remove all rows from Trash? This cannot be undone.',
        'delete_row_confirm' => 'Delete this row?',
        'delete_permanent_confirm' => 'Permanently delete this row from Trash?',
        'delete_version_confirm' => 'Permanently delete this version?',
        'restore_button' => 'Restore',
        'delete_permanent_button' => 'Delete permanently',
        'insert_above' => 'Insert above',
        'insert_below' => 'Insert below',
        'delete' => 'Delete',
        'actions' => 'Actions',
        'dismiss' => 'Dismiss',
        'row_deleted' => 'Row deleted',
        'show_diff' => 'Show diff',
        'download' => 'Download',
        'restore_this_version' => 'Restore this version',
        'no_versions_yet' => 'No versions yet.',
        'comparing_version' => 'Comparing version: <strong>%s</strong> to current CSV'
    ],
    'de' => [
        'no_filename_title' => 'CSV Editor — Kein Dateiname angegeben',
        'no_filename_msg' => 'Diese Anwendung muss mit dem URL-Parameter csv_filename geöffnet werden.',
        'example' => 'Beispiel:',
        'allowed_chars' => 'Erlaubte Zeichen im Dateinamen: Buchstaben, Zahlen, Punkt, Bindestrich, Unterstrich. Der Dateiname muss mit .csv enden. Die Datei muss bereits im Verzeichnis tables/ vorhanden sein.',
        'file_not_found_title' => 'CSV Editor — Datei nicht gefunden',
        'file_not_found_msg' => 'Die angeforderte CSV-Datei wurde im Verzeichnis tables nicht gefunden.',
        'back' => 'Zurück',
        'save' => 'Speichern (erstellt Version)',
        'download_current' => 'Aktuelle CSV herunterladen',
        'file_to_edit' => 'Bearbeite Datei:',
        'files_stored_in' => 'Dateien & Versionen gespeichert in',
        'add_row' => 'Zeile am Ende hinzufügen',
        'trash_title' => 'Papierkorb (client-seitig, in Sitzung gespeichert)',
        'trash_restore_all' => 'Alle wiederherstellen',
        'trash_is_empty' => 'Papierkorb ist leer.',
        'trash_empty' => 'Papierkorb leeren',
        'versions_title' => 'Server-seitige Versionen (wiederherstellbar)',
        'versions_note' => 'Gespeicherte Schnappschüsse (neueste zuerst). "Show diff" vergleicht eine Version mit der aktuellen CSV vor dem Wiederherstellen. Beim Wiederherstellen wird die aktuelle CSV automatisch gesichert.',
        'diff_title' => 'Diff Vorschau',
        'no_diff_selected' => 'Kein Diff ausgewählt. Klicken Sie für eine Vorschau auf "Show diff" bei einer Version.',
        'restore_version_confirm' => 'Diese Version wiederherstellen und aktuelle CSV überschreiben? Die aktuelle CSV wird gesichert.',
        'restore_all_confirm' => 'Alle Zeilen aus dem Papierkorb wiederherstellen?',
        'empty_trash_confirm' => 'Alle Zeilen im Papierkorb endgültig entfernen? Dies kann nicht rückgängig gemacht werden.',
        'delete_row_confirm' => 'Diese Zeile löschen?',
        'delete_permanent_confirm' => 'Diese Zeile aus dem Papierkorb endgültig löschen?',
        'delete_version_confirm' => 'Diese Version dauerhaft löschen?',
        'restore_button' => 'Wiederherstellen',
        'delete_permanent_button' => 'Endgültig löschen',
        'insert_above' => 'Zeile darüber einfügen',
        'insert_below' => 'Zeile darunter einfügen',
        'delete' => 'Zeile Löschen',
        'actions' => 'Aktionen',
        'dismiss' => 'Schließen',
        'row_deleted' => 'Zeile gelöscht',
        'show_diff' => 'Diff anzeigen',
        'download' => 'Herunterladen',
        'restore_this_version' => 'Diese Version wiederherstellen',
        'no_versions_yet' => 'Noch keine Versionen.',
        'comparing_version' => 'Vergleiche Version: <strong>%s</strong> mit der aktuellen CSV'
    ]
];

// Merge translations.json into defaults if present
$loaded = [];
if (file_exists($TRANSLATIONS_JSON)) {
    $raw = @file_get_contents($TRANSLATIONS_JSON);
    $decoded = @json_decode($raw, true);
    if (is_array($decoded)) {
        $loaded = $decoded;
    }
}
$T = $default_translations;
foreach ($loaded as $lk => $map) {
    if (!isset($T[$lk])) $T[$lk] = [];
    $T[$lk] = array_merge($T[$lk], $map);
}

// Determine locale (server-side preference via Accept-Language)
$locale = 'en';
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $al = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (strpos($al, 'de') === 0 || strpos($al, 'de-') === 0 || preg_match('/\bde\b/', $al)) $locale = 'de';
}
$tr = $T[$locale] ?? $T['en'];

/* -----------------------
   Request parameter: csv_filename
   ----------------------- */
$csv_param = isset($_REQUEST['csv_filename']) ? basename($_REQUEST['csv_filename']) : null;

if (empty($csv_param)) {
    // render a short instructive page when no filename provided
    http_response_code(200);
    ?>
    <!doctype html><html><head><meta charset="utf-8"><title><?=htmlspecialchars($tr['no_filename_title'])?></title>
    <style>body{font-family: sans-serif;margin:24px}.note{border:1px solid #ccc;padding:16px;border-radius:6px;background:#fafafa}</style>
    </head><body>
    <h1>CSV Editor</h1>
    <div class="note">
      <p><?=htmlspecialchars($tr['no_filename_msg'])?></p>
      <p><?=htmlspecialchars($tr['example'])?> <code>csv_html_editor.php?csv_filename=data.csv</code></p>
      <p><?=htmlspecialchars($tr['allowed_chars'])?></p>
    </div>
    </body></html>
    <?php
    exit;
}

// Validate filename
if (!is_safe_csv_filename($csv_param)) {
    http_response_code(400);
    echo 'Invalid csv_filename parameter.';
    exit;
}

// Compute file paths
$csv_path = $CSV_FOLDER . DIRECTORY_SEPARATOR . $csv_param;
$versions_root = $CSV_FOLDER . DIRECTORY_SEPARATOR . 'versions';
$version_subdir = pathinfo($csv_param, PATHINFO_FILENAME) . '_versions';
$versions_dir = $versions_root . DIRECTORY_SEPARATOR . $version_subdir;
if (!is_dir($versions_dir)) @mkdir($versions_dir, 0755, true);

// --- Small LCS-based diff helper (line-based) ---
function compute_diff_html(array $oldLines, array $newLines): string
{
    $n = count($oldLines);
    $m = count($newLines);
    $dp = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
    for ($i = $n - 1; $i >= 0; $i--) {
        for ($j = $m - 1; $j >= 0; $j--) {
            if ($oldLines[$i] === $newLines[$j]) $dp[$i][$j] = $dp[$i + 1][$j + 1] + 1;
            else $dp[$i][$j] = max($dp[$i + 1][$j], $dp[$i][$j + 1]);
        }
    }
    $i = 0; $j = 0; $ops = [];
    while ($i < $n && $j < $m) {
        if ($oldLines[$i] === $newLines[$j]) { $ops[] = [' ', $oldLines[$i]]; $i++; $j++; }
        else if ($dp[$i + 1][$j] >= $dp[$i][$j + 1]) { $ops[] = ['-', $oldLines[$i]]; $i++; }
        else { $ops[] = ['+', $newLines[$j]]; $j++; }
    }
    while ($i < $n) { $ops[] = ['-', $oldLines[$i]]; $i++; }
    while ($j < $m) { $ops[] = ['+', $newLines[$j]]; $j++; }

    $html = '<div class="diffWrap"><pre class="diffArea">';
    foreach ($ops as [$op, $line]) {
        $escaped = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        if ($op === ' ') $html .= '<span class="diffEqual">  ' . $escaped . "</span>\n";
        elseif ($op === '-') $html .= '<span class="diffDel">− ' . $escaped . "</span>\n";
        else $html .= '<span class="diffAdd">+ ' . $escaped . "</span>\n";
    }
    $html .= "</pre></div>";
    return $html;
}

/* --------------------------------------------------------------------------
   HTTP handlers (download, diff, version download, restore/delete, save)
   The order matters: downloads served and exit. POST save runs after JS
   has populated csv_data.
   -------------------------------------------------------------------------- */

/* Download current CSV (GET) */
if (isset($_GET['download_current'])) {
    if (!file_exists($csv_path)) { http_response_code(404); exit('Not found'); }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . basename($csv_path) . '"');
    header('Content-Length: ' . filesize($csv_path));
    if (ob_get_level()) ob_end_clean();
    readfile($csv_path);
    exit;
}

/* Show diff (GET) */
$diff_html = '';
$diff_version_name = '';
if (isset($_GET['diff_version'])) {
    $vf = basename($_GET['diff_version']);
    if (is_safe_version_filename($vf)) {
        $vpath = $versions_dir . DIRECTORY_SEPARATOR . $vf;
        if (file_exists($vpath)) {
            $currentLines = [];
            if (file_exists($csv_path) && ($h = fopen($csv_path, 'r')) !== false) {
                while (($line = fgets($h)) !== false) $currentLines[] = rtrim($line, "\r\n");
                fclose($h);
            }
            $versionLines = [];
            if (($h2 = fopen($vpath, 'r')) !== false) {
                while (($line = fgets($h2)) !== false) $versionLines[] = rtrim($line, "\r\n");
                fclose($h2);
            }
            $diff_html = compute_diff_html($versionLines, $currentLines);
            $diff_version_name = $vf;
        } else {
            $diff_html = '<div class="muted">Version file not found.</div>';
        }
    } else {
        $diff_html = '<div class="muted">Invalid version name.</div>';
    }
}

/* Download a specific version (GET) */
if (isset($_GET['download_version'])) {
    $vf = basename($_GET['download_version']);
    if (!is_safe_version_filename($vf)) { http_response_code(400); exit('Invalid filename'); }
    $vpath = $versions_dir . DIRECTORY_SEPARATOR . $vf;
    if (!file_exists($vpath)) { http_response_code(404); exit('Not found'); }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.basename($vpath).'"');
    header('Content-Length: ' . filesize($vpath));
    readfile($vpath);
    exit;
}

/* Restore version (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_version'])) {
    $posted = isset($_POST['csv_filename']) ? basename($_POST['csv_filename']) : '';
    if ($posted !== $csv_param) { header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param)); exit; }
    $vf = basename($_POST['restore_version'] ?? '');
    if (!is_safe_version_filename($vf)) { header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param)); exit; }
    $vpath = $versions_dir . DIRECTORY_SEPARATOR . $vf;
    if (file_exists($vpath)) {
        if (file_exists($csv_path)) {
            $ts = date('Ymd_His');
            @copy($csv_path, $versions_dir . DIRECTORY_SEPARATOR . "pre_restore_{$ts}.csv");
        }
        @copy($vpath, $csv_path);
    }
    header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param));
    exit;
}

/* Delete version (POST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_version'])) {
    $posted = isset($_POST['csv_filename']) ? basename($_POST['csv_filename']) : '';
    if ($posted !== $csv_param) { header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param)); exit; }
    $vf = basename($_POST['delete_version'] ?? '');
    if (is_safe_version_filename($vf)) {
        $vpath = $versions_dir . DIRECTORY_SEPARATOR . $vf;
        if (file_exists($vpath)) @unlink($vpath);
    }
    header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param));
    exit;
}

/* Save main CSV (POST) — csv_data set by client JS (JSON array of rows) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_data']) && !isset($_POST['restore_version']) && !isset($_POST['delete_version'])) {
    $posted = isset($_POST['csv_filename']) ? basename($_POST['csv_filename']) : '';
    if ($posted !== $csv_param) {
        header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param));
        exit;
    }
    $rows = json_decode($_POST['csv_data'], true);
    // Save previous content as a version before overwriting
    if (file_exists($csv_path)) {
        $ts = date('Ymd_His');
        $version_name = "data_{$ts}.csv";
        @copy($csv_path, $versions_dir . DIRECTORY_SEPARATOR . $version_name);
        // Rotate
        $files = glob($versions_dir . DIRECTORY_SEPARATOR . "data_*.csv");
        usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
        if (count($files) > $MAX_VERSIONS) {
            $to_delete = array_slice($files, $MAX_VERSIONS);
            foreach ($to_delete as $f) @unlink($f);
        }
    }
    // Write CSV
    $fp = fopen($csv_path, 'w');
    if ($fp) {
        foreach ($rows as $row) {
            if (!is_array($row)) $row = [];
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
    header("Location: ".$_SERVER['PHP_SELF']."?csv_filename=".urlencode($csv_param));
    exit;
}

/* --------------------------------------------------------------------------
   Load CSV into memory for rendering
   -------------------------------------------------------------------------- */
$rows = [];
if (file_exists($csv_path) && ($h = fopen($csv_path, 'r')) !== false) {
    while (($data = fgetcsv($h)) !== false) $rows[] = $data;
    fclose($h);
}
if (empty($rows)) $rows[] = [''];

/* --------------------------------------------------------------------------
   Load versions list (sorted newest first)
   -------------------------------------------------------------------------- */
$versions = [];
if (is_dir($versions_dir)) {
    $files = glob($versions_dir . DIRECTORY_SEPARATOR . "*.csv");
    usort($files, function($a,$b){ return filemtime($b) - filemtime($a); });
    foreach ($files as $f) {
        $versions[] = ['name' => basename($f), 'mtime' => date('Y-m-d H:i:s', filemtime($f)), 'size' => filesize($f)];
    }
}

/* --------------------------------------------------------------------------
   JS translation payload: keep it minimal but complete
   -------------------------------------------------------------------------- */
$js_trans = [
    'lang' => $locale,
    'confirm' => [
        'delete_row' => $tr['delete_row_confirm'],
        'restore_all' => $tr['restore_all_confirm'],
        'empty_trash' => $tr['empty_trash_confirm'],
        'delete_permanent' => $tr['delete_permanent_confirm'],
        'delete_version' => $tr['delete_version_confirm'],
        'restore_version' => $tr['restore_version_confirm']
    ],
    'labels' => [
        'restore' => $tr['restore_button'],
        'delete_permanent' => $tr['delete_permanent_button'],
        'insert_above' => $tr['insert_above'],
        'insert_below' => $tr['insert_below'],
        'delete' => $tr['delete'],
        'dismiss' => $tr['dismiss'],
        'row_deleted' => $tr['row_deleted']
    ]
];

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>CSV Editor — <?= htmlspecialchars($csv_param) ?></title>
<style>
/* (CSS kept minimal; colors defined earlier in previous iterations) */
body{font-family:sans-serif;margin:16px}
table{border-collapse:collapse;width:100%}
td,th{border:1px solid #666;padding:6px;vertical-align:top}
td{min-width:80px}
td[contenteditable="true"]{background:#eef}
.actions{white-space:nowrap;width:1%}
.actions button{margin:0 2px;padding:6px 8px;font-size:12px;border-radius:3px;border:none;cursor:pointer}
.btn-insert-above,.btn-insert-below{background:#28a745;color:#fff}
.btn-delete{background:#dc3545;color:#fff}
.btn{display:inline-block;padding:6px 10px;background:#007bff;color:#fff;border-radius:4px;border:none;cursor:pointer}
.undoToast{background:#222;color:#fff;padding:10px 12px;margin-top:8px;border-radius:4px;display:flex;gap:8px;align-items:center;opacity:.95}
.undoToast button{background:#fff;color:#000;border:none;padding:6px 8px;border-radius:3px;cursor:pointer}
.panelToast{background:#eee;color:#555;padding:10px 12px;margin-top:8px;border-radius:4px;opacity:.95}
.panelToast a{color:#8ecbff}.panelToast .muted{color:#999}
#controls{margin:10px 0;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#controls-bottom{margin:12px 0;display:flex;gap:8px;align-items:center}
#undoContainer{position:fixed;right:20px;bottom:20px;z-index:1000}
.muted{color:#999;font-size:13px}.small{font-size:12px;color:#999}
.diffWrap{max-height:400px;overflow:auto;border:1px solid #ddd;background:#fff;padding:8px}
.diffArea{margin:0;font-family:monospace;font-size:13px;color:#222}
.diffAdd{background:#e6ffed;display:block;color:#1a7f37}
.diffDel{background:#ffecec;display:block;color:#a1322a}
.diffEqual{display:block;color:#444}
</style>
</head>
<body>
<h1>CSV Editor — <?= htmlspecialchars($csv_param) ?></h1>

<form method="post" id="csvForm">
<input type="hidden" name="csv_filename" value="<?= htmlspecialchars($csv_param) ?>">
<div id="controls">
  <button type="submit" class="btn"><?= htmlspecialchars($tr['save']) ?></button>
  <a class="btn" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?download_current=1&csv_filename=' . urlencode($csv_param) ?>"><?= htmlspecialchars($tr['download_current']) ?></a>
  <span class="muted"><?= htmlspecialchars($tr['file_to_edit']) ?> <strong><?= htmlspecialchars($csv_param) ?></strong>. <?= htmlspecialchars($tr['files_stored_in']) ?> <code><?= htmlspecialchars(basename($CSV_FOLDER)) ?></code>.</span>
</div>

<table id="csvTable">
  <thead>
    <tr>
      <?php foreach ($rows[0] as $header): ?><th><?= htmlspecialchars($header) ?></th><?php endforeach; ?>
      <th class="action"><?= htmlspecialchars($tr['actions']) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (array_slice($rows, 1) as $row): ?>
      <tr>
        <?php foreach ($row as $cell): ?><td contenteditable="true"><?= htmlspecialchars($cell) ?></td><?php endforeach; ?>
        <?php
          $numHeaderCols = count($rows[0]);
          $numCells = count($row);
          for ($i = $numCells; $i < $numHeaderCols; $i++): ?>
            <td contenteditable="true"></td>
        <?php endfor; ?>
        <td class="actions">
          <button type="button" class="btn-insert-above"><?= htmlspecialchars($tr['insert_above']) ?></button>
          <button type="button" class="btn-insert-below"><?= htmlspecialchars($tr['insert_below']) ?></button>
          <button type="button" class="btn-delete"><?= htmlspecialchars($tr['delete']) ?></button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div id="controls-bottom">
  <button type="submit" class="btn"><?= htmlspecialchars($tr['save']) ?></button>
</div>

<input type="hidden" name="csv_data" id="csv_data">
</form>
<hr>

<div id="trashPanel" class="panelToast" aria-live="polite" aria-atomic="true">
  <h3><?= htmlspecialchars($tr['trash_title']) ?></h3>
  <div id="trashList"></div>
  <div style="margin-top:8px;">
    <button type="button" id="restoreAllBtn"><?= htmlspecialchars($tr['trash_restore_all']) ?></button>
    <button type="button" id="emptyTrashBtn"><?= htmlspecialchars($tr['trash_empty']) ?></button>
  </div>
</div>

<div id="versionsPanel" class="panelToast" aria-live="polite">
  <h3><?= htmlspecialchars($tr['versions_title']) ?></h3>
  <div class="small"><?= htmlspecialchars($tr['versions_note']) ?></div>
  <div id="versionsList" style="margin-top:8px;">
    <?php if (empty($versions)): ?>
      <div class="muted" style="margin-top:6px;"><?= htmlspecialchars($tr['no_versions_yet']) ?></div>
    <?php else: foreach ($versions as $v): ?>
      <div class="versionItem" style="display:flex;gap:8px;align-items:center;padding:6px;border-radius:4px;margin-bottom:6px;background:#fff;color:#555;">
        <div style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($v['name']) ?> — <?= htmlspecialchars($v['mtime']) ?> — <?= round($v['size']/1024,2) ?> KB</div>
        <div class="versionActions" style="white-space:nowrap;">
          <a href="?diff_version=<?= urlencode($v['name']) ?>&csv_filename=<?= urlencode($csv_param) ?>"><?= htmlspecialchars($tr['show_diff']) ?></a>
          <form method="post" style="display:inline">
            <input type="hidden" name="csv_filename" value="<?= htmlspecialchars($csv_param) ?>">
            <input type="hidden" name="restore_version" value="<?= htmlspecialchars($v['name']) ?>">
            <button type="submit"><?= htmlspecialchars($tr['restore_button']) ?></button>
          </form>
          <form method="post" style="display:inline; margin-left:4px;">
            <input type="hidden" name="csv_filename" value="<?= htmlspecialchars($csv_param) ?>">
            <input type="hidden" name="delete_version" value="<?= htmlspecialchars($v['name']) ?>">
            <button type="submit" onclick="return confirm('<?= htmlspecialchars($tr['delete_version_confirm']) ?>')"><?= htmlspecialchars($tr['delete_permanent_button']) ?></button>
          </form>
          <a href="?download_version=<?= urlencode($v['name']) ?>&csv_filename=<?= urlencode($csv_param) ?>"><?= htmlspecialchars($tr['download']) ?></a>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div id="diffPanel" class="panelToast" aria-live="polite">
  <h3><?= htmlspecialchars($tr['diff_title']) ?></h3>
  <div class="small"><?= sprintf($tr['comparing_version'], $diff_version_name ? htmlspecialchars($diff_version_name) : '—') ?></div>
  <div style="margin-top:8px;">
    <?php if ($diff_html): ?>
      <?= $diff_html ?>
      <?php if ($diff_version_name): ?>
        <form method="post" style="margin-top:8px;">
          <input type="hidden" name="csv_filename" value="<?= htmlspecialchars($csv_param) ?>">
          <input type="hidden" name="restore_version" value="<?= htmlspecialchars($diff_version_name) ?>">
          <button type="submit" onclick="return confirm('<?= htmlspecialchars($tr['restore_version_confirm']) ?>')"><?= htmlspecialchars($tr['restore_this_version']) ?></button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <div class="muted"><?= htmlspecialchars($tr['no_diff_selected']) ?></div>
    <?php endif; ?>
  </div>
</div>

<div id="undoContainer"></div>

<script>
/* Client-side I18N and UI logic
   - All runtime strings come from the I18N payload.
   - No remaining hard-coded English in dynamic UI.
*/
const I18N = <?= json_encode($js_trans, JSON_UNESCAPED_UNICODE) ?>;

// Merge a small German fallback if client prefers 'de' and server isn't de
(function(){
  const clientLang = (navigator.language || navigator.userLanguage || '').toLowerCase();
  if (clientLang && clientLang.startsWith('de') && I18N.lang !== 'de') {
    const deFallback = {
      confirm: {
        delete_row: "Diese Zeile löschen?",
        restore_all: "Alle Zeilen aus dem Papierkorb wiederherstellen?",
        empty_trash: "Alle Zeilen im Papierkorb endgültig entfernen? Dies kann nicht rückgängig gemacht werden.",
        delete_permanent: "Diese Zeile aus dem Papierkorb endgültig löschen?",
        delete_version: "Diese Version dauerhaft löschen?",
        restore_version: "Diese Version wiederherstellen und aktuelle CSV überschreiben? Die aktuelle CSV wird gesichert."
      },
      labels: {
        restore: "Wiederherstellen",
        delete_permanent: "Endgültig löschen",
        insert_above: "Über dieser Zeile einfügen",
        insert_below: "Unter dieser Zeile einfügen",
        delete: "Löschen",
        dismiss: "Schließen",
        row_deleted: "Zeile gelöscht"
      }
    };
    I18N.confirm = Object.assign(deFallback.confirm, I18N.confirm);
    I18N.labels = Object.assign(deFallback.labels, I18N.labels);
  }
})();

/* Main UI code (unchanged logic but using I18N values) */
(function() {
  const STORAGE_KEY = 'csv_deleted_rows_v1_' + encodeURIComponent('<?= rawurlencode($csv_param) ?>');
  const UNDO_TIMEOUT_MS = 30000;
  const UNDO_LIMIT = 200;

  const table = document.getElementById('csvTable');
  const tbody = table.tBodies[0] || table.appendChild(document.createElement('tbody'));
  const undoContainer = document.getElementById('undoContainer');
  const trashList = document.getElementById('trashList');

  let deletedStack = loadDeletedStack();

  function numDataCols() { return table.tHead.rows[0].cells.length - 1; }

  function createDataCell(text) {
    const td = document.createElement('td');
    td.setAttribute('contenteditable', 'true');
    td.innerText = text || '';
    return td;
  }

  function createActionCell() {
    const td = document.createElement('td');
    td.className = 'actions';
    const btnAbove = document.createElement('button'); btnAbove.type='button'; btnAbove.className='btn-insert-above'; btnAbove.textContent = I18N.labels.insert_above;
    const btnBelow = document.createElement('button'); btnBelow.type='button'; btnBelow.className='btn-insert-below'; btnBelow.textContent = I18N.labels.insert_below;
    const btnDelete = document.createElement('button'); btnDelete.type='button'; btnDelete.className='btn-delete'; btnDelete.textContent = I18N.labels.delete;
    td.appendChild(btnAbove); td.appendChild(btnBelow); td.appendChild(btnDelete);
    return td;
  }

  function buildRowFromData(cellsData) {
    const tr = document.createElement('tr');
    for (let i = 0; i < numDataCols(); i++) {
      const text = (i < cellsData.length) ? cellsData[i] : '';
      tr.appendChild(createDataCell(text));
    }
    tr.appendChild(createActionCell());
    return tr;
  }

  function addRowAt(index) {
    const tr = buildRowFromData([]);
    const rows = tbody.rows;
    if (index >= 0 && index < rows.length) tbody.insertBefore(tr, rows[index]);
    else tbody.appendChild(tr);
    if (tr.cells.length) tr.cells[0].focus();
  }

  function generateId() { return Date.now().toString(36) + '-' + Math.random().toString(36).slice(2,9); }

  function pushDeletedRow(cellsData, index, showToast=true) {
    while (deletedStack.length >= UNDO_LIMIT) deletedStack.shift();
    const entry = { id: generateId(), cells: cellsData.slice(), index: index, ts: Date.now(), toastElem: null };
    deletedStack.push(entry);
    saveDeletedStack();
    renderTrash();
    if (showToast) showUndoToast(entry);
  }

  function deleteRowWithUndo(tr) {
    if (!tr) return;
    const cells = [];
    for (let i=0;i<tr.cells.length;i++){
      const cell = tr.cells[i];
      if (cell.classList && cell.classList.contains('actions')) continue;
      cells.push(cell.innerText);
    }
    const rows = Array.prototype.slice.call(tbody.rows);
    const idx = rows.indexOf(tr);
    tbody.removeChild(tr);
    pushDeletedRow(cells, idx, true);
  }

  function restoreDeletedEntryById(id) {
    const idx = deletedStack.findIndex(e => e.id === id);
    if (idx === -1) return;
    const entry = deletedStack[idx];
    restoreDeletedEntry(entry);
    deletedStack.splice(idx, 1);
    saveDeletedStack();
    renderTrash();
  }

  function restoreDeletedEntry(entry) {
    const insertIndex = Math.max(0, Math.min(entry.index, tbody.rows.length));
    const tr = buildRowFromData(entry.cells);
    const rows = tbody.rows;
    if (insertIndex < rows.length) tbody.insertBefore(tr, rows[insertIndex]);
    else tbody.appendChild(tr);
    if (tr.cells.length) tr.cells[0].focus();
    if (entry.toastElem) { entry.toastElem.remove(); entry.toastElem = null; }
  }

  function permanentlyRemoveEntryById(id) {
    const idx = deletedStack.findIndex(e => e.id === id);
    if (idx === -1) return;
    const entry = deletedStack[idx];
    if (entry.toastElem) entry.toastElem.remove();
    deletedStack.splice(idx, 1);
    saveDeletedStack();
    renderTrash();
  }

  function saveDeletedStack() {
    try {
      const serial = deletedStack.map(e => ({ id: e.id, cells: e.cells, index: e.index, ts: e.ts }));
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(serial));
    } catch (err) { console.warn('Failed to save deleted rows', err); }
  }

  function loadDeletedStack() {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) return [];
      const arr = JSON.parse(raw);
      return arr.map(e => ({ id: e.id, cells: e.cells, index: e.index, ts: e.ts, toastElem: null }));
    } catch (err) { console.warn('Failed to load deleted rows', err); return []; }
  }

  function clearAllDeletedStack() {
    deletedStack.forEach(e => { if (e.toastElem) e.toastElem.remove(); });
    deletedStack = []; sessionStorage.removeItem(STORAGE_KEY); renderTrash();
  }

  function renderTrash() {
    trashList.innerHTML = '';
    if (deletedStack.length === 0) {
      trashList.innerHTML = '<div class="muted"><?= htmlspecialchars($tr['trash_is_empty']) ?></div>';
      return;
    }
    deletedStack.forEach(function(entry){
      const div = document.createElement('div');
      div.className = 'trashItem';
      div.style.display='flex'; div.style.gap='8px'; div.style.alignItems='center'; div.style.padding='6px'; div.style.background='#fff'; div.style.color='#000'; div.style.borderRadius='4px'; div.style.marginBottom='6px';
      const preview = document.createElement('div'); preview.style.flex='1'; preview.style.whiteSpace='nowrap'; preview.style.overflow='hidden'; preview.style.textOverflow='ellipsis';
      preview.textContent = entry.cells.join(' | ');
      const actions = document.createElement('div');
      const restoreBtn = document.createElement('button'); restoreBtn.type='button'; restoreBtn.textContent = I18N.labels.restore;
      const deleteBtn = document.createElement('button'); deleteBtn.type='button'; deleteBtn.textContent = I18N.labels.delete_permanent || 'Delete';
      restoreBtn.addEventListener('click', function(){ restoreDeletedEntryById(entry.id); });
      deleteBtn.addEventListener('click', function(){ if (confirm(I18N.confirm.delete_permanent)) permanentlyRemoveEntryById(entry.id); });
      actions.appendChild(restoreBtn); actions.appendChild(deleteBtn);
      div.appendChild(preview); div.appendChild(actions);
      trashList.appendChild(div);
    });
  }

  function showUndoToast(entry) {
    const toast = document.createElement('div'); toast.className='undoToast';
    const span = document.createElement('span'); span.textContent = I18N.labels.row_deleted || 'Row deleted';
    const undoBtn = document.createElement('button'); undoBtn.type='button'; undoBtn.textContent = I18N.labels.restore || 'Undo';
    const dismissBtn = document.createElement('button'); dismissBtn.type='button'; dismissBtn.textContent = I18N.labels.dismiss || 'Dismiss';
    const timerInfo = document.createElement('small'); timerInfo.textContent = '';
    toast.appendChild(span); toast.appendChild(undoBtn); toast.appendChild(dismissBtn); toast.appendChild(timerInfo);
    undoContainer.appendChild(toast);
    entry.toastElem = toast;
    let remaining = Math.floor(UNDO_TIMEOUT_MS/1000); timerInfo.textContent = ' ('+remaining+'s)';
    const intervalId = setInterval(function(){ remaining--; if (remaining <= 0) { timerInfo.textContent=''; clearInterval(intervalId);} else timerInfo.textContent=' ('+remaining+'s)'; }, 1000);
    const timeoutId = setTimeout(function(){ if (entry.toastElem) entry.toastElem.remove(); entry.toastElem = null; clearInterval(intervalId); }, UNDO_TIMEOUT_MS);
    undoBtn.addEventListener('click', function(){ const idx = deletedStack.findIndex(e => e.id === entry.id); if (idx !== -1) { const e=deletedStack[idx]; restoreDeletedEntry(e); deletedStack.splice(idx,1); saveDeletedStack(); renderTrash(); } clearTimeout(timeoutId); clearInterval(intervalId); if (entry.toastElem) entry.toastElem.remove();});
    dismissBtn.addEventListener('click', function(){ clearTimeout(timeoutId); clearInterval(intervalId); if (entry.toastElem) entry.toastElem.remove(); entry.toastElem = null; });
  }

  // Delegated row action clicks
  table.addEventListener('click', function(e){
    const t = e.target;
    if (t.matches('.btn-insert-above')||t.matches('.btn-insert-below')||t.matches('.btn-delete')) {
      const tr = t.closest('tr');
      const rows = Array.prototype.slice.call(tbody.rows);
      const idx = rows.indexOf(tr);
      if (t.matches('.btn-insert-above')) addRowAt(idx);
      else if (t.matches('.btn-insert-below')) addRowAt(idx+1);
      else if (t.matches('.btn-delete')) { if (confirm(I18N.confirm.delete_row)) deleteRowWithUndo(tr); }
    }
  });

  document.getElementById('restoreAllBtn').addEventListener('click', function(){
    if (deletedStack.length === 0) return;
    if (!confirm(I18N.confirm.restore_all)) return;
    const copy = deletedStack.slice(); copy.sort((a,b)=>a.index-b.index); copy.forEach(function(e){ restoreDeletedEntry(e); });
    deletedStack=[]; saveDeletedStack(); renderTrash();
  });

  document.getElementById('emptyTrashBtn').addEventListener('click', function(){
    if (!confirm(I18N.confirm.empty_trash)) return;
    clearAllDeletedStack();
  });

  // CSV form submit builds data (header + rows) and sets #csv_data
  document.getElementById('csvForm').onsubmit = function(){
    const data = [];
    const headerRow = table.tHead.rows[0];
    const header = [];
    for (let i=0;i<headerRow.cells.length;i++){
      const th = headerRow.cells[i];
      if (th.classList.contains('action')) continue;
      header.push(th.innerText);
    }
    data.push(header);
    const bodyRows = tbody.rows;
    for (let r=0;r<bodyRows.length;r++){
      const row = bodyRows[r];
      const rowData = [];
      for (let c=0;c<row.cells.length;c++){
        const cell = row.cells[c];
        if (cell.classList.contains('actions')) continue;
        rowData.push(cell.innerText);
      }
      while (rowData.length < header.length) rowData.push('');
      data.push(rowData);
    }
    deletedStack.forEach(function(e){ if (e.toastElem) e.toastElem.remove(); });
    deletedStack = []; sessionStorage.removeItem(STORAGE_KEY); renderTrash();
    document.getElementById('csv_data').value = JSON.stringify(data);
    return true;
  };

  // Ctrl/Cmd+Z restore last deleted
  document.addEventListener('keydown', function(e){
    const isMac = navigator.platform.toUpperCase().indexOf('MAC')>=0;
    const modifier = isMac ? e.metaKey : e.ctrlKey;
    if (modifier && e.key === 'z') {
      if (deletedStack.length > 0) {
        e.preventDefault();
        const last = deletedStack.pop();
        restoreDeletedEntry(last);
        saveDeletedStack();
        renderTrash();
      }
    }
  });

  // Initialize
  renderTrash();
})();
</script>
</body>
</html>
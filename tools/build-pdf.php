<?php
/**
 * tools/build-pdf.php
 *
 * Build a print-quality PDF of the customer USER-GUIDE.md using Dompdf
 * (already a project dependency, used for invoice PDFs).
 *
 * Usage:   php tools/build-pdf.php [path/to/input.md] [path/to/output.pdf]
 * Default: tools/build-pdf.php USER-GUIDE.md USER-GUIDE.pdf
 *
 * The Markdown→HTML converter here is intentionally focused on what the
 * user-guide actually uses (H1-H4, bold, italic, inline code, fenced code,
 * GFM pipe tables, ul/ol lists, blockquotes, hr, links, paragraphs). It
 * is *not* a general-purpose Markdown renderer — keep that in mind if you
 * extend the guide with new syntax.
 */

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$projectRoot = realpath(__DIR__ . '/..');
$inputPath   = $argv[1] ?? ($projectRoot . '/USER-GUIDE.md');
$outputPath  = $argv[2] ?? ($projectRoot . '/USER-GUIDE.pdf');

if (!is_file($inputPath)) {
    fwrite(STDERR, "Input file not found: {$inputPath}\n");
    exit(1);
}

$md   = file_get_contents($inputPath);
$html = md_to_html($md);

// Wrap with print stylesheet
$doc = render_document_html($html, [
    'title'    => 'TPT Aggregator — User Guide',
    'subtitle' => 'A complete walk-through for owners, managers, operations, finance and customers',
    'date'     => date('F Y'),
]);

$opts = new Options();
$opts->set('isRemoteEnabled', false);   // no remote images / CSS
$opts->set('defaultFont', 'DejaVu Sans');
$opts->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($opts);
$dompdf->loadHtml($doc, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Footer with page numbers
$canvas = $dompdf->getCanvas();
$footer = $canvas->open_object();
$canvas->page_text(
    $canvas->get_width() / 2 - 30,
    $canvas->get_height() - 30,
    'Page {PAGE_NUM} of {PAGE_COUNT}',
    null,
    9,
    [0.45, 0.49, 0.55]
);
$canvas->close_object();
$canvas->add_object($footer, 'all');

file_put_contents($outputPath, $dompdf->output());

echo "PDF written: {$outputPath}\n";
echo "Pages: " . $dompdf->getCanvas()->get_page_count() . "\n";
echo "Size:  " . number_format(filesize($outputPath) / 1024, 1) . " KB\n";

// ─────────────────────────────────────────────────────────────────────
// Markdown → HTML
// ─────────────────────────────────────────────────────────────────────

function md_to_html(string $md): string
{
    // Normalise line endings
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    // 1) Pull out fenced code blocks first, replace with placeholders
    $codeBlocks = [];
    $md = preg_replace_callback('/```([a-z0-9_-]*)\n(.*?)```/is', function ($m) use (&$codeBlocks) {
        $idx = count($codeBlocks);
        $codeBlocks[$idx] = '<pre><code>' . htmlspecialchars($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</code></pre>';
        return "@@CB:{$idx}@@";
    }, $md);

    // 2) Pull out GFM tables (block of |...| lines with a |---| separator)
    $tables = [];
    $md = preg_replace_callback(
        '/(^\|.+\|\s*\n\|[\s:|\-]+\|\s*\n(?:\|.*\|\s*\n?)+)/m',
        function ($m) use (&$tables) {
            $idx = count($tables);
            $tables[$idx] = render_gfm_table(trim($m[0]));
            return "\n@@TBL:{$idx}@@\n";
        },
        $md
    );

    // 3) Now escape everything else
    $s = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

    // 4) Headings (H4 first so deeper ## doesn't grab them)
    $s = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $s);
    $s = preg_replace('/^###\s+(.*)$/m',  '<h3>$1</h3>', $s);
    $s = preg_replace('/^##\s+(.*)$/m',   '<h2>$1</h2>', $s);
    $s = preg_replace('/^#\s+(.*)$/m',    '<h1>$1</h1>', $s);

    // 5) Horizontal rule
    $s = preg_replace('/^---+$/m', '<hr>', $s);

    // 6) Blockquotes (one or more leading "> " lines collapse to a single block)
    $s = preg_replace_callback('/(?:^&gt;\s?.*(?:\n|$))+/m', function ($m) {
        $body = preg_replace('/^&gt;\s?/m', '', trim($m[0]));
        return "<blockquote>" . $body . "</blockquote>\n";
    }, $s);

    // 7) Bullet lists (-, *)
    $s = preg_replace_callback('/(?:^[\-\*]\s+.+(?:\n|$))+/m', function ($m) {
        $lines = preg_split('/\n/', trim($m[0]));
        $items = array_map(fn ($l) => '<li>' . preg_replace('/^[\-\*]\s+/', '', $l) . '</li>', $lines);
        return "<ul>\n" . implode("\n", $items) . "\n</ul>\n";
    }, $s);

    // 8) Numbered lists
    $s = preg_replace_callback('/(?:^\d+\.\s+.+(?:\n|$))+/m', function ($m) {
        $lines = preg_split('/\n/', trim($m[0]));
        $items = array_map(fn ($l) => '<li>' . preg_replace('/^\d+\.\s+/', '', $l) . '</li>', $lines);
        return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
    }, $s);

    // 9) Inline: bold, italic, inline code
    $s = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<![\w\*])\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $s);
    $s = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $s);

    // 10) Links [text](url) — only http(s), mailto, fragments, relative
    $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
        $url = $m[2];
        if (!preg_match('~^(https?:|mailto:|/|#)~i', $url)) return $m[0];
        return '<a href="' . $url . '">' . $m[1] . '</a>';
    }, $s);

    // 11) Paragraph wrap on blank-line separation, leaving block elements alone
    $blocks = preg_split('/\n\s*\n/', $s);
    $out = [];
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b === '') continue;
        if (preg_match('~^<(?:h[1-6]|ul|ol|li|pre|blockquote|hr|table)\b~i', $b)
            || preg_match('~^@@(?:CB|TBL):\d+@@$~', $b)) {
            $out[] = $b;
        } else {
            $out[] = '<p>' . str_replace("\n", "<br>", $b) . '</p>';
        }
    }
    $html = implode("\n", $out);

    // 12) Restore code blocks and tables
    $html = preg_replace_callback('/@@CB:(\d+)@@/', fn ($m) => $codeBlocks[(int) $m[1]], $html);
    $html = preg_replace_callback('/@@TBL:(\d+)@@/',  fn ($m) => $tables[(int) $m[1]], $html);

    return $html;
}

function render_gfm_table(string $block): string
{
    $lines = preg_split('/\n/', trim($block));
    if (count($lines) < 2) return '<pre>' . htmlspecialchars($block) . '</pre>';

    $cellsFor = function (string $row): array {
        $row   = trim($row);
        $row   = preg_replace('/^\||\|$/', '', $row);
        $parts = preg_split('/\s*\|\s*/', $row);
        return array_map('trim', $parts);
    };

    $head = $cellsFor($lines[0]);
    $sep  = $cellsFor($lines[1]);

    // Alignment from separator row: ":---" left, "---:" right, ":---:" centre.
    $align = array_map(function ($s) {
        $left  = str_starts_with($s, ':');
        $right = str_ends_with($s, ':');
        if ($left && $right) return 'center';
        if ($right)          return 'right';
        if ($left)           return 'left';
        return null;
    }, $sep);

    $body = array_slice($lines, 2);

    $renderInline = function (string $s): string {
        // Same inline pass as md_to_html for cell contents
        $s = htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<![\w\*])\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $s);
        $s = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $s);
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $url = $m[2];
            if (!preg_match('~^(https?:|mailto:|/|#)~i', $url)) return $m[0];
            return '<a href="' . $url . '">' . $m[1] . '</a>';
        }, $s);
        return $s;
    };

    $thead = '<thead><tr>';
    foreach ($head as $i => $c) {
        $a = $align[$i] ?? null;
        $thead .= '<th' . ($a ? ' style="text-align:' . $a . '"' : '') . '>' . $renderInline($c) . '</th>';
    }
    $thead .= '</tr></thead>';

    $tbody = '<tbody>';
    foreach ($body as $row) {
        if (trim($row) === '') continue;
        $cells = $cellsFor($row);
        $tbody .= '<tr>';
        foreach ($cells as $i => $c) {
            $a = $align[$i] ?? null;
            $tbody .= '<td' . ($a ? ' style="text-align:' . $a . '"' : '') . '>' . $renderInline($c) . '</td>';
        }
        $tbody .= '</tr>';
    }
    $tbody .= '</tbody>';

    return '<table class="md-table">' . $thead . $tbody . '</table>';
}

// ─────────────────────────────────────────────────────────────────────
// Document shell + print CSS
// ─────────────────────────────────────────────────────────────────────

function render_document_html(string $body, array $meta): string
{
    $title    = htmlspecialchars($meta['title']    ?? 'Document', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $subtitle = htmlspecialchars($meta['subtitle'] ?? '',         ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $date     = htmlspecialchars($meta['date']     ?? '',         ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $css = <<<CSS
@page { margin: 22mm 18mm 22mm 18mm; }

body {
  font-family: "DejaVu Sans", sans-serif;
  font-size: 10.5pt;
  line-height: 1.55;
  color: #1f2329;
}

/* Cover */
.cover { text-align: center; padding: 40mm 10mm 0 10mm; page-break-after: always; }
.cover .brand { font-size: 13pt; letter-spacing: 4px; color: #2a6df4; font-weight: 700; }
.cover h1 { font-size: 32pt; margin: 14mm 0 6mm 0; color: #131722; line-height: 1.15; }
.cover .sub { font-size: 12pt; color: #555c67; margin-bottom: 30mm; }
.cover .date { font-size: 10pt; color: #8a92a0; }
.cover .rule { width: 60mm; height: 3px; background: #2a6df4; margin: 14mm auto; }

/* Body headings */
h1 { font-size: 20pt; color: #131722; margin: 14mm 0 4mm; padding-bottom: 3mm; border-bottom: 2px solid #e3e7ee; page-break-before: always; }
h1:first-of-type { page-break-before: avoid; }
h2 { font-size: 14.5pt; color: #1f2937; margin: 10mm 0 3mm; }
h3 { font-size: 12pt; color: #2a6df4; margin: 7mm 0 2mm; font-weight: 700; }
h4 { font-size: 10.5pt; color: #1f2937; margin: 5mm 0 1.5mm; font-weight: 700; }

p  { margin: 0 0 3mm 0; text-align: justify; }
ul, ol { margin: 0 0 4mm 7mm; padding: 0; }
li { margin: 0 0 1.5mm 0; }

a { color: #2a6df4; text-decoration: none; }

/* Inline code & code blocks */
code {
  background: #f1f3f7;
  border-radius: 3px;
  padding: 0 4px;
  font-family: "DejaVu Sans Mono", monospace;
  font-size: 9.5pt;
  color: #c62b6d;
}
pre {
  background: #f6f8fb;
  border: 1px solid #e3e7ee;
  border-radius: 4px;
  padding: 4mm;
  overflow: hidden;
  margin: 0 0 4mm 0;
}
pre code { background: none; padding: 0; color: #1f2937; font-size: 9pt; line-height: 1.4; }

/* Blockquote — used for "Tip" / "Important" notes */
blockquote {
  border-left: 4px solid #2a6df4;
  background: #f3f7ff;
  padding: 3mm 4mm;
  margin: 0 0 4mm 0;
  color: #2c3a55;
  border-radius: 0 4px 4px 0;
}
blockquote p:last-child { margin-bottom: 0; }

/* Tables */
table.md-table {
  width: 100%;
  border-collapse: collapse;
  margin: 0 0 5mm 0;
  font-size: 9.5pt;
}
table.md-table th,
table.md-table td {
  border: 1px solid #d8dde6;
  padding: 2mm 3mm;
  vertical-align: top;
}
table.md-table th {
  background: #eef2f9;
  color: #1f2937;
  text-align: left;
  font-weight: 700;
}
table.md-table tr:nth-child(even) td { background: #fafbfd; }

hr { border: 0; border-top: 1px solid #e3e7ee; margin: 6mm 0; }

/* Reduce orphan/widow lines visually */
h1, h2, h3, h4 { page-break-after: avoid; }
li, tr { page-break-inside: avoid; }
CSS;

    return <<<HTML
<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<title>{$title}</title>
<style>{$css}</style>
</head>
<body>

<div class="cover">
  <div class="brand">TPT AGGREGATOR</div>
  <h1>{$title}</h1>
  <div class="rule"></div>
  <div class="sub">{$subtitle}</div>
  <div class="date">{$date}</div>
</div>

{$body}

</body></html>
HTML;
}

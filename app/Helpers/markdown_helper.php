<?php

if (!function_exists('tpt_md_render')) {
    /**
     * Tiny Markdown → safe HTML renderer.
     *
     * Supports: H1–H4, **bold**, *italic*, `inline code`, fenced ```code blocks```,
     * - / 1. lists (single level), > blockquote, [text](url), --- horizontal rule,
     * paragraph breaks on blank line. Input is HTML-escaped FIRST so any raw
     * tags become literal text (no XSS).
     */
    function tpt_md_render(string $md): string
    {
        // Escape everything first — no raw HTML allowed
        $s = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        // Fenced code blocks
        $s = preg_replace_callback('/```([a-z0-9_-]*)\n(.*?)```/is', function ($m) {
            return '<pre><code>' . $m[2] . '</code></pre>';
        }, $s);

        // Horizontal rule
        $s = preg_replace('/^---+$/m', '<hr>', $s);

        // Headings
        $s = preg_replace('/^####\s+(.*)$/m', '<h4>$1</h4>', $s);
        $s = preg_replace('/^###\s+(.*)$/m',  '<h3>$1</h3>', $s);
        $s = preg_replace('/^##\s+(.*)$/m',   '<h2>$1</h2>', $s);
        $s = preg_replace('/^#\s+(.*)$/m',    '<h1>$1</h1>', $s);

        // Blockquotes (single line)
        $s = preg_replace('/^&gt;\s*(.*)$/m', '<blockquote>$1</blockquote>', $s);

        // Bullet list (-, *) — collapse consecutive lines into one <ul>
        $s = preg_replace_callback('/(?:^[\-\*]\s+.+(?:\n|$))+/m', function ($m) {
            $lines = preg_split('/\n/', trim($m[0]));
            $items = array_map(fn ($l) => '<li>' . preg_replace('/^[\-\*]\s+/', '', $l) . '</li>', $lines);
            return "<ul>\n" . implode("\n", $items) . "\n</ul>\n";
        }, $s);

        // Numbered list
        $s = preg_replace_callback('/(?:^\d+\.\s+.+(?:\n|$))+/m', function ($m) {
            $lines = preg_split('/\n/', trim($m[0]));
            $items = array_map(fn ($l) => '<li>' . preg_replace('/^\d+\.\s+/', '', $l) . '</li>', $lines);
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        }, $s);

        // Bold ** **
        $s = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $s);
        // Italic * * (single asterisk, not part of **)
        $s = preg_replace('/(?<![\w\*])\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $s);
        // Inline code
        $s = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $s);

        // Links [text](url) — only http(s), mailto, or relative
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $url = $m[2];
            if (!preg_match('#^(https?:|mailto:|/|#)#i', $url)) return $m[0];
            return '<a href="' . $url . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
        }, $s);

        // Paragraph wrap — split on blank lines, wrap text blocks in <p>, leave block elements alone
        $blocks = preg_split('/\n\s*\n/', $s);
        $out = [];
        foreach ($blocks as $b) {
            $b = trim($b);
            if ($b === '') continue;
            if (preg_match('#^<(?:h[1-6]|ul|ol|li|pre|blockquote|hr)\b#', $b)) {
                $out[] = $b;
            } else {
                $out[] = '<p>' . str_replace("\n", "<br>", $b) . '</p>';
            }
        }
        return implode("\n", $out);
    }
}

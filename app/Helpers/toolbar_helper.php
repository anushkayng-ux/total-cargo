<?php

if (!function_exists('tpt_toolbar')) {
    /**
     * Renders the retro-demo style icon toolbar (New/Edit/Save/Cancel/Delete/
     * Print/Close). Mirrors total-cargo-demo's shell_toolbar(): any button
     * whose href/form isn't supplied renders muted/inert instead of being
     * omitted, so the toolbar's shape never jumps between pages.
     *
     * Options:
     *   new_href, edit_href, close_href — plain links (cancel_href is
     *   accepted but ignored — every caller pointed it at the exact same
     *   URL as close_href, so the two buttons were always duplicates; Close
     *   alone now covers it, wearing Cancel's old x-circle icon)
     *   save_form   — id of a <form> elsewhere on the page; renders a submit button bound to it
     *   delete_href — renders as its own POST+CSRF form with a confirm prompt
     *   delete_confirm — confirm text (default provided)
     *   extra       — raw HTML appended at the right (search box, filters, etc.)
     *   auth        — the current \App\Libraries\Auth (pass $auth from the view) — when
     *                 given alongside new_href, "New" becomes a dropdown offering this
     *                 page's own create action FIRST, then every other record type the
     *                 user can create (the same list as the banner's global New button),
     *                 so "New" means the same thing everywhere instead of two different
     *                 buttons behaving differently.
     *   new_item_label — label for the page's own item inside that dropdown (defaults to "New")
     */
    function tpt_toolbar(array $opts = []): string
    {
        $btn = function (string $icon, string $label, ?string $href, array $more = []) {
            if (!$href) {
                return '<div class="retro-tbtn retro-tbtn-off"><i class="bi bi-' . $icon . '"></i>' . esc($label) . '</div>';
            }
            $cls = 'retro-tbtn';
            if (!empty($more['danger'])) $cls .= ' retro-danger';
            if (!empty($more['primary'])) $cls .= ' retro-primary';

            if (!empty($more['form_submit'])) {
                return '<button type="submit" form="' . esc($more['form_submit']) . '" class="' . $cls . '"><i class="bi bi-' . $icon . '"></i>' . esc($label) . '</button>';
            }
            if (!empty($more['post'])) {
                return '<form method="post" action="' . esc($href) . '" class="d-inline" data-confirm="' . esc($more['confirm'] ?? 'Are you sure?') . '">'
                    . csrf_field()
                    . '<button type="submit" class="' . $cls . '"><i class="bi bi-' . $icon . '"></i>' . esc($label) . '</button>'
                    . '</form>';
            }
            return '<a class="' . $cls . '" href="' . esc($href) . '"><i class="bi bi-' . $icon . '"></i>' . esc($label) . '</a>';
        };

        // "New" — a plain button normally, but a dropdown (this page's own
        // create action + every other record type) when an Auth is passed.
        $newBtn = $btn('plus-circle-fill', 'New', $opts['new_href'] ?? null, ['primary' => true]);
        if (!empty($opts['new_href']) && !empty($opts['auth'])) {
            $others = array_values(array_filter(
                tpt_quick_add_items($opts['auth']),
                fn ($qa) => rtrim($qa['url'], '/') !== rtrim($opts['new_href'], '/')
            ));
            if ($others) {
                $newBtn = '<div class="dropdown d-inline-block">'
                    . '<button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>'
                    . '<ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">'
                    . '<li><a class="dropdown-item d-flex align-items-center gap-2" href="' . esc($opts['new_href']) . '"><i class="bi bi-plus-circle"></i> ' . esc($opts['new_item_label'] ?? 'New') . '</a></li>'
                    . '<li><hr class="dropdown-divider"></li>'
                    . '<li><h6 class="dropdown-header">Other…</h6></li>';
                foreach ($others as $qa) {
                    $newBtn .= '<li><a class="dropdown-item d-flex align-items-center gap-2" href="' . esc($qa['url']) . '"><i class="bi bi-' . esc($qa['icon']) . '"></i> ' . esc($qa['label']) . '</a></li>';
                }
                $newBtn .= '</ul></div>';
            }
        }

        $stickyClass = ($opts['sticky'] ?? true) ? ' is-sticky' : '';
        $html = '<div class="retro-toolbar' . $stickyClass . '">';
        $html .= $newBtn;
        $html .= $btn('pencil-fill', 'Edit', $opts['edit_href'] ?? null);
        $html .= $btn('save-fill', 'Save', $opts['save_form'] ?? null, ['form_submit' => $opts['save_form'] ?? null, 'primary' => true]);
        $html .= $btn('trash-fill', 'Delete', $opts['delete_href'] ?? null, ['post' => true, 'confirm' => $opts['delete_confirm'] ?? 'Delete this record? It can be restored later if needed.']);
        $html .= '<button type="button" class="retro-tbtn" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>';
        $html .= $btn('x-circle', 'Close', $opts['close_href'] ?? null);
        if (!empty($opts['extra'])) {
            $html .= '<div class="retro-toolbar-extra">' . $opts['extra'] . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}

"""
Convert FEATURES.md -> a professional TPT-Aggregator-Feature-Specification-v1.1.docx.

Features:
  - Cover page (title, subtitle, version, date, logo-free corporate look)
  - Native Word Table of Contents field (auto-populates on first open in Word)
  - H1 / H2 / H3 heading styles tuned for a corporate brief
  - Real Word tables (preserved from markdown pipe tables)
  - Fenced code blocks rendered in Consolas monospace on a light grey shaded cell
  - Inline code + **bold** supported in paragraphs and table cells
  - Bullet and numbered lists
  - Horizontal rules and block quotes
  - Page numbers in footer
"""

import re
import sys
from pathlib import Path
from datetime import date

from docx import Document
from docx.shared import Pt, Inches, RGBColor, Cm
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.enum.style import WD_STYLE_TYPE
from docx.oxml.ns import qn
from docx.oxml import OxmlElement


MD_PATH  = Path(r"C:\xampp\htdocs\tpt\FEATURES.md")
OUT_PATH = Path(r"C:\xampp\htdocs\tpt\TPT-Aggregator-Feature-Specification-v1.1.docx")

# ------------------------------ document setup ------------------------------

doc = Document()

# US Letter, 1" margins
for section in doc.sections:
    section.page_width  = Inches(8.5)
    section.page_height = Inches(11)
    section.top_margin    = Inches(0.9)
    section.bottom_margin = Inches(0.9)
    section.left_margin   = Inches(0.9)
    section.right_margin  = Inches(0.9)

# Base colors
DARK_BLUE   = RGBColor(0x1F, 0x36, 0x5D)
BLUE        = RGBColor(0x2E, 0x4F, 0x7C)
ACCENT      = RGBColor(0x36, 0x36, 0x36)
MUTED       = RGBColor(0x66, 0x66, 0x66)
CODE_TEXT   = RGBColor(0x11, 0x11, 0x11)
TABLE_HEAD_FILL = "2E4F7C"
CODE_FILL   = "F3F4F6"

# Normal font
style_normal = doc.styles["Normal"]
style_normal.font.name = "Calibri"
style_normal.font.size = Pt(11)
style_normal.paragraph_format.space_after = Pt(6)
style_normal.paragraph_format.line_spacing = 1.3

# Heading styles
for lvl, size, color in [(1, 22, DARK_BLUE), (2, 16, BLUE), (3, 13, ACCENT), (4, 12, ACCENT)]:
    st = doc.styles[f"Heading {lvl}"]
    st.font.name = "Calibri"
    st.font.size = Pt(size)
    st.font.bold = True
    st.font.color.rgb = color
    st.paragraph_format.space_before = Pt(18 if lvl == 1 else 12)
    st.paragraph_format.space_after  = Pt(6)
    st.paragraph_format.keep_with_next = True

# Custom Code style
if "Code" not in [s.name for s in doc.styles]:
    code_style = doc.styles.add_style("Code", WD_STYLE_TYPE.PARAGRAPH)
    code_style.font.name = "Consolas"
    code_style.font.size = Pt(9.5)
    code_style.font.color.rgb = CODE_TEXT
    code_style.paragraph_format.space_before = Pt(0)
    code_style.paragraph_format.space_after  = Pt(0)
    code_style.paragraph_format.line_spacing = 1.15

# Caption style already exists; we use Normal for most content.

# ------------------------------ helpers ------------------------------

def set_cell_shading(cell, fill_hex: str):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill_hex)
    tc_pr.append(shd)


def set_cell_borders(cell, color="BFBFBF", size=4):
    tc_pr = cell._tc.get_or_add_tcPr()
    tcBorders = OxmlElement("w:tcBorders")
    for edge in ("top", "left", "bottom", "right"):
        b = OxmlElement(f"w:{edge}")
        b.set(qn("w:val"), "single")
        b.set(qn("w:sz"), str(size))
        b.set(qn("w:space"), "0")
        b.set(qn("w:color"), color)
        tcBorders.append(b)
    tc_pr.append(tcBorders)


def add_inline_runs(paragraph, text: str, bold=False, italic=False, base_font=None, base_size=None, base_color=None):
    """
    Parse a markdown snippet into runs with inline formatting:
      **bold**, *italic*, `code`, [text](url)
    Recursion is avoided by processing one token at a time with regex dispatch.
    """
    if text is None or text == "":
        return

    # Token pattern: each of these ALTERNATIVES matches one inline token.
    pattern = re.compile(
        r"(\*\*[^*]+\*\*)"                       # bold
        r"|(`[^`]+`)"                            # inline code
        r"|(\[[^\]]+\]\([^)]+\))"                # link
        r"|(\*[^*\s][^*]*?\*)"                   # italic (single-star)
    )
    pos = 0
    for m in pattern.finditer(text):
        start, end = m.span()
        if start > pos:
            _run(paragraph, text[pos:start], bold, italic, base_font, base_size, base_color)
        tok = m.group()
        if tok.startswith("**"):
            _run(paragraph, tok[2:-2], True, italic, base_font, base_size, base_color)
        elif tok.startswith("`"):
            r = paragraph.add_run(tok[1:-1])
            r.font.name = "Consolas"
            r.font.size = Pt((base_size or 10.5) - 0.5)
            shd = OxmlElement("w:shd")
            shd.set(qn("w:val"), "clear"); shd.set(qn("w:color"), "auto"); shd.set(qn("w:fill"), CODE_FILL)
            r._element.get_or_add_rPr().append(shd)
        elif tok.startswith("["):
            # [label](url)
            mm = re.match(r"\[([^\]]+)\]\(([^)]+)\)", tok)
            label, url = mm.group(1), mm.group(2)
            _add_hyperlink(paragraph, url, label)
        elif tok.startswith("*"):
            _run(paragraph, tok[1:-1], bold, True, base_font, base_size, base_color)
        pos = end
    if pos < len(text):
        _run(paragraph, text[pos:], bold, italic, base_font, base_size, base_color)


def _run(paragraph, text, bold, italic, base_font, base_size, base_color):
    r = paragraph.add_run(text)
    if base_font: r.font.name = base_font
    if base_size: r.font.size = Pt(base_size)
    if base_color: r.font.color.rgb = base_color
    if bold:   r.bold = True
    if italic: r.italic = True


def _add_hyperlink(paragraph, url, label):
    part = paragraph.part
    r_id = part.relate_to(
        url,
        "http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink",
        is_external=True,
    )
    hyperlink = OxmlElement("w:hyperlink")
    hyperlink.set(qn("r:id"), r_id)
    new_run = OxmlElement("w:r")
    rPr = OxmlElement("w:rPr")
    col = OxmlElement("w:color"); col.set(qn("w:val"), "2E4F7C"); rPr.append(col)
    u = OxmlElement("w:u"); u.set(qn("w:val"), "single"); rPr.append(u)
    new_run.append(rPr)
    t = OxmlElement("w:t"); t.text = label
    new_run.append(t)
    hyperlink.append(new_run)
    paragraph._p.append(hyperlink)


def add_toc(d):
    p = d.add_paragraph()
    run = p.add_run()
    for val, text in (("begin", None), (None, "TOC"), ("separate", None),
                      (None, "Right-click here and choose \"Update Field\" after opening in Word."),
                      ("end", None)):
        pass
    # Build TOC field
    fldChar_begin = OxmlElement("w:fldChar"); fldChar_begin.set(qn("w:fldCharType"), "begin")
    instrText = OxmlElement("w:instrText"); instrText.set(qn("xml:space"), "preserve")
    instrText.text = 'TOC \\o "1-3" \\h \\z \\u'
    fldChar_sep = OxmlElement("w:fldChar"); fldChar_sep.set(qn("w:fldCharType"), "separate")
    placeholder = OxmlElement("w:t"); placeholder.text = "Right-click the Table of Contents and choose \"Update Field\" to populate it."
    fldChar_end = OxmlElement("w:fldChar"); fldChar_end.set(qn("w:fldCharType"), "end")

    r = run._r
    r.append(fldChar_begin); r.append(instrText); r.append(fldChar_sep)
    r.append(placeholder); r.append(fldChar_end)


def add_page_break(d):
    d.add_paragraph().add_run().add_break(WD_BREAK.PAGE)


def add_page_number_footer(d):
    for section in d.sections:
        footer = section.footer
        p = footer.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r = p.add_run()
        fld_begin = OxmlElement("w:fldChar"); fld_begin.set(qn("w:fldCharType"), "begin")
        instr = OxmlElement("w:instrText"); instr.text = "PAGE"
        fld_sep = OxmlElement("w:fldChar"); fld_sep.set(qn("w:fldCharType"), "separate")
        placeholder = OxmlElement("w:t"); placeholder.text = "1"
        fld_end = OxmlElement("w:fldChar"); fld_end.set(qn("w:fldCharType"), "end")
        r._r.append(fld_begin); r._r.append(instr); r._r.append(fld_sep); r._r.append(placeholder); r._r.append(fld_end)
        r2 = p.add_run(" · TPT Aggregator — Feature Specification v1.1")
        r2.font.size = Pt(9)
        r2.font.color.rgb = MUTED


def set_repeat_header_row(row):
    trPr = row._tr.get_or_add_trPr()
    tblHeader = OxmlElement("w:tblHeader")
    tblHeader.set(qn("w:val"), "true")
    trPr.append(tblHeader)


# ------------------------------ cover page ------------------------------

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
for _ in range(6):
    p.add_run().add_break()

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("TPT AGGREGATOR")
r.font.name = "Calibri"
r.font.size = Pt(40)
r.font.bold = True
r.font.color.rgb = DARK_BLUE

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("Transport Aggregator CRM · Procurement · Operations · Billing · Tracking")
r.font.size = Pt(14)
r.font.color.rgb = BLUE

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(80)
r = p.add_run("Feature Specification")
r.font.size = Pt(22)
r.font.bold = True
r.font.color.rgb = ACCENT

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("Version 1.1")
r.font.size = Pt(14)
r.font.color.rgb = MUTED

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(40)
r = p.add_run(date.today().strftime("%B %Y"))
r.font.size = Pt(12)
r.font.color.rgb = MUTED

for _ in range(10):
    doc.add_paragraph()

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("Prepared for client review")
r.font.size = Pt(10)
r.font.italic = True
r.font.color.rgb = MUTED

add_page_break(doc)

# ------------------------------ ToC page ------------------------------

toc_head = doc.add_paragraph()
r = toc_head.add_run("Table of Contents")
r.font.size = Pt(22); r.font.bold = True; r.font.color.rgb = DARK_BLUE
toc_head.paragraph_format.space_after = Pt(18)

add_toc(doc)

add_page_break(doc)

# ------------------------------ markdown parser / renderer ------------------------------

md_text = MD_PATH.read_text(encoding="utf-8")
lines = md_text.split("\n")

# State machine
i = 0
in_code = False
code_lines = []


def flush_code_block():
    """Render accumulated code lines as a shaded monospace block (1-col table for the shading)."""
    global code_lines
    if not code_lines:
        return
    # Use a single-cell table so we can shade the background
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.LEFT
    cell = tbl.rows[0].cells[0]
    cell.width = Inches(6.7)
    set_cell_shading(cell, CODE_FILL)
    set_cell_borders(cell, color="E0E0E0", size=4)
    # Remove default paragraph and add code lines
    if cell.paragraphs and not cell.paragraphs[0].text:
        cell._tc.remove(cell.paragraphs[0]._p)
    for cl in code_lines:
        cp = cell.add_paragraph(style="Code")
        cp.paragraph_format.space_before = Pt(0)
        cp.paragraph_format.space_after = Pt(0)
        r = cp.add_run(cl)
        r.font.name = "Consolas"
        r.font.size = Pt(9.5)
    # Bottom margin
    sp = doc.add_paragraph()
    sp.paragraph_format.space_after = Pt(6)
    code_lines = []


def parse_table(start_idx: int):
    """Collect a contiguous block of | ... | lines. Returns (rows, next_index)."""
    rows = []
    j = start_idx
    while j < len(lines) and lines[j].lstrip().startswith("|"):
        rows.append(lines[j].strip())
        j += 1
    return rows, j


def render_table(raw_rows):
    # Remove the separator row like |---|---|
    cleaned = []
    for r in raw_rows:
        # strip leading/trailing |
        cells = [c.strip() for c in r.strip().strip("|").split("|")]
        if all(re.match(r"^:?-+:?$", c) for c in cells if c != ""):
            continue
        cleaned.append(cells)
    if not cleaned:
        return
    cols = max(len(r) for r in cleaned)
    # Normalize rows to same length
    for r in cleaned:
        while len(r) < cols:
            r.append("")

    tbl = doc.add_table(rows=len(cleaned), cols=cols)
    tbl.alignment = WD_TABLE_ALIGNMENT.LEFT
    tbl.autofit = True
    # Even cell width
    total_w = 6.7  # inches of content area after margins roughly
    col_w = Inches(total_w / cols)
    for row_idx, row in enumerate(cleaned):
        for col_idx, text in enumerate(row):
            cell = tbl.rows[row_idx].cells[col_idx]
            cell.width = col_w
            set_cell_borders(cell, color="BFBFBF", size=4)
            # Clear default empty paragraph
            if cell.paragraphs and not cell.paragraphs[0].text:
                p = cell.paragraphs[0]
            else:
                p = cell.add_paragraph()
            if row_idx == 0:
                set_cell_shading(cell, TABLE_HEAD_FILL)
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                add_inline_runs(p, text, bold=True, base_size=10.5, base_color=RGBColor(0xFF, 0xFF, 0xFF))
            else:
                add_inline_runs(p, text, base_size=10.5)
        if row_idx == 0:
            set_repeat_header_row(tbl.rows[0])
    # Breathing room after table
    sp = doc.add_paragraph()
    sp.paragraph_format.space_after = Pt(6)


while i < len(lines):
    line = lines[i]
    stripped = line.rstrip()

    # Fenced code block
    if stripped.startswith("```"):
        if in_code:
            flush_code_block()
            in_code = False
        else:
            in_code = True
        i += 1
        continue
    if in_code:
        code_lines.append(line)
        i += 1
        continue

    # Blank line — paragraph break
    if stripped.strip() == "":
        i += 1
        continue

    # Horizontal rule — skip (visual breaks come from headings)
    if re.match(r"^\s*---+\s*$", stripped):
        i += 1
        continue

    # Block quote (">")
    if stripped.startswith("> "):
        text = stripped[2:]
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Inches(0.3)
        p.paragraph_format.space_before = Pt(4)
        p.paragraph_format.space_after = Pt(4)
        # left border via pPr
        pPr = p._p.get_or_add_pPr()
        pBdr = OxmlElement("w:pBdr")
        left = OxmlElement("w:left")
        left.set(qn("w:val"), "single"); left.set(qn("w:sz"), "12"); left.set(qn("w:space"), "8"); left.set(qn("w:color"), "2E4F7C")
        pBdr.append(left)
        pPr.append(pBdr)
        add_inline_runs(p, text, italic=True, base_color=BLUE)
        i += 1
        continue

    # Headings
    h_match = re.match(r"^(#{1,6})\s+(.*)$", stripped)
    if h_match:
        level = len(h_match.group(1))
        text = h_match.group(2).strip()
        level = min(level, 4)
        if level == 1:
            # Start new top-level section on a new page (but not the very first one)
            # Check if there's already content above — if the previous non-empty element was a page break
            # we don't add another. Cheapest heuristic: always add a page break before H1 except when
            # this is the very first H1 we emit.
            if getattr(render_table, "_first_h1_done", False):
                add_page_break(doc)
            render_table._first_h1_done = True
        p = doc.add_paragraph(style=f"Heading {level}")
        add_inline_runs(p, text)
        i += 1
        continue

    # Table block
    if stripped.lstrip().startswith("|"):
        rows, new_i = parse_table(i)
        if rows:
            render_table(rows)
            i = new_i
            continue

    # Unordered list
    ul_match = re.match(r"^(\s*)([-*])\s+(.*)$", line)
    if ul_match:
        indent_spaces = len(ul_match.group(1))
        text = ul_match.group(3)
        p = doc.add_paragraph(style="List Bullet")
        p.paragraph_format.left_indent = Inches(0.25 + (indent_spaces // 2) * 0.25)
        p.paragraph_format.space_after = Pt(2)
        add_inline_runs(p, text)
        i += 1
        continue

    # Ordered list
    ol_match = re.match(r"^(\s*)\d+\.\s+(.*)$", line)
    if ol_match:
        indent_spaces = len(ol_match.group(1))
        text = ol_match.group(2)
        p = doc.add_paragraph(style="List Number")
        p.paragraph_format.left_indent = Inches(0.25 + (indent_spaces // 2) * 0.25)
        p.paragraph_format.space_after = Pt(2)
        add_inline_runs(p, text)
        i += 1
        continue

    # Plain paragraph — fold subsequent non-empty, non-block lines together
    para_lines = [stripped]
    j = i + 1
    while j < len(lines):
        nxt = lines[j]
        if nxt.strip() == "":
            break
        if re.match(r"^\s*(#{1,6}\s|[-*]\s|\d+\.\s|\|)", nxt):
            break
        if nxt.startswith("```"):
            break
        para_lines.append(nxt.rstrip())
        j += 1
    text = " ".join(para_lines).strip()
    if text:
        p = doc.add_paragraph()
        add_inline_runs(p, text)
    i = j + 1  # skip the blank line consumed

# Footer with page numbers
add_page_number_footer(doc)

# Save
OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
doc.save(str(OUT_PATH))
print(f"Wrote {OUT_PATH}  ({OUT_PATH.stat().st_size:,} bytes)")

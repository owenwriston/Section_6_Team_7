<?php
require_once __DIR__ . '/lib/fpdf/fpdf.php';

//convert Windows-1252 to  UTF-8 text before printing
function pdf_text($s): string
{
    $s = (string) $s;
    $out = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return $out === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $out;
}

function pdf_label(?string $s): string
{
    return ucwords(str_replace('_', ' ', (string) $s));
}

function pdf_date(?string $s): string
{
    if ($s === null || $s === '' || $s === '0000-00-00 00:00:00') {
        return '-';
    }
    $t = strtotime($s);
    return $t ? date('M j, Y g:i A', $t) : $s;
}

class ProjectReportPDF extends FPDF
{
    public string $projectName = '';

    public function Header(): void
    {
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120);
        $this->Cell(0, 5, pdf_text('PunchDash project report - ' . $this->projectName), 0, 0, 'L');
        $this->Cell(0, 5, pdf_text('Generated ' . date('M j, Y g:i A')), 0, 1, 'R');
        $this->SetDrawColor(200);
        $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
        $this->Ln(4);
        $this->SetTextColor(0);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120);
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        $this->SetTextColor(0);
    }

    //start new page
    public function ensureSpace(float $mm): void
    {
        if ($this->GetY() + $mm > $this->PageBreakTrigger) {
            $this->AddPage();
        }
    }

    public function h1(string $s): void
    {
        $this->SetFont('Helvetica', 'B', 20);
        $this->MultiCell(0, 9, pdf_text($s));
        $this->Ln(1);
    }

    public function h2(string $s): void
    {
        $this->ensureSpace(25);
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetFillColor(235, 238, 242);
        $this->Cell(0, 8, pdf_text($s), 0, 1, 'L', true);
        $this->Ln(2);
    }

    public function kv(string $k, string $v): void
    {
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(38, 5.5, pdf_text($k), 0, 0);
        $this->SetFont('Helvetica', '', 10);
        $this->MultiCell(0, 5.5, pdf_text($v));
    }

    public function para(string $s, float $h = 5): void
    {
        $this->SetFont('Helvetica', '', 10);
        $this->MultiCell(0, $h, pdf_text($s));
    }
}

//Build report and return size
function build_project_pdf(array $r): string
{
    $p = $r['project'];
    $title = $p['name'] !== '' ? $p['name'] : '(Untitled)';

    $pdf = new ProjectReportPDF('P', 'mm', 'Letter');
    $pdf->projectName = $title;
    $pdf->AliasNbPages();
    $pdf->SetAutoPageBreak(true, 16);
    $pdf->SetMargins(16, 16, 16);
    $pdf->SetTitle($title . ' - project report', true);
    $pdf->SetCreator('PunchDash');
    $pdf->AddPage();

    //Project Overview
    $pdf->h1($title);
    $pdf->kv('Location', $p['location']);
    $pdf->kv('Status', pdf_label($p['status']));
    $pdf->kv('Owner', $p['owner'] . ' (' . $p['owner_email'] . ')');
    $pdf->kv('Created', pdf_date($p['created']));
    $pdf->kv('Last updated', pdf_date($p['updated']));
    $pdf->Ln(3);

    //Summary
    $c = $r['counts'];
    $pdf->h2('Summary');
    $pdf->kv('Floorplans', (string) $c['floorplans']);
    $pdf->kv('Tasks', (string) $c['tasks']);
    foreach ($c['by_status'] as $st => $n) {
        $pdf->kv('   ' . pdf_label($st), (string) $n);
    }
    $pdf->kv('Attachments', (string) $c['attachments']);
    $pdf->Ln(3);

    //Members
    $pdf->h2('Assigned members');
    if (count($r['members']) === 0) {
        $pdf->para('No one has been assigned to this project.');
    } else {
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(245);
        $pdf->Cell(45, 6, 'Name', 0, 0, 'L', true);
        $pdf->Cell(60, 6, 'Email', 0, 0, 'L', true);
        $pdf->Cell(35, 6, 'Role', 0, 0, 'L', true);
        $pdf->Cell(0, 6, 'Assigned', 0, 1, 'L', true);
        $pdf->SetFont('Helvetica', '', 9);
        foreach ($r['members'] as $m) {
            $pdf->ensureSpace(8);
            $pdf->Cell(45, 5.5, pdf_text(mb_strimwidth($m['name'], 0, 26, '...')), 0, 0);
            $pdf->Cell(60, 5.5, pdf_text(mb_strimwidth($m['email'], 0, 36, '...')), 0, 0);
            $pdf->Cell(35, 5.5, pdf_text($m['role']), 0, 0);
            $pdf->Cell(0, 5.5, pdf_text(pdf_date($m['assigned'])), 0, 1);
        }
    }

    //Floorplans and attachments
    if (count($r['floorplans']) === 0) {
        $pdf->Ln(4);
        $pdf->h2('Floorplans');
        $pdf->para('This project has no floorplans.');
    }

    foreach ($r['floorplans'] as $f) {
        $pdf->AddPage();
        $pdf->h2('Floorplan ' . $f['number']);
        $pdf->kv('Uploaded', pdf_date($f['uploaded']));
        $pdf->kv('Assigned to', count($f['assigned']) ? implode(', ', $f['assigned']) : 'No one');
        $pdf->kv('Tasks', (string) count($f['tasks']));
        $pdf->Ln(3);

        if (count($f['tasks']) === 0) {
            $pdf->para('No tasks on this floorplan.');
            continue;
        }

        foreach ($f['tasks'] as $t) {
            $pdf->ensureSpace(45);

            //task header
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetFillColor(247);
            $pdf->Cell(130, 7, pdf_text(mb_strimwidth('#' . $t['tid'] . '  ' . $t['title'], 0, 70, '...')), 0, 0, 'L', true);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(0, 7, pdf_text(strtoupper(pdf_label($t['status']))), 0, 1, 'R', true);

            $pdf->SetFont('Helvetica', '', 9);
            $line = [pdf_label($t['type']), 'PDF page ' . $t['page']];
            if ($t['room'] !== '')     { $line[] = 'Room ' . $t['room']; }
            if ($t['initials'] !== '') { $line[] = 'Stamp ' . $t['initials']; }
            if ($t['x'] !== null && $t['y'] !== null) {
                $line[] = 'Position (' . rtrim(rtrim((string) $t['x'], '0'), '.') . ', ' . rtrim(rtrim((string) $t['y'], '0'), '.') . ')';
            }
            $pdf->SetTextColor(90);
            $pdf->MultiCell(0, 5, pdf_text(implode('  |  ', $line)));
            $pdf->SetTextColor(0);

            $pdf->kv('Assigned to', count($t['assigned']) ? implode(', ', $t['assigned']) : 'No one');
            if ($t['start'] !== null || $t['due'] !== null) {
                $pdf->kv('Schedule', 'Start ' . pdf_date($t['start']) . '   Due ' . pdf_date($t['due']));
            }
            if ($t['root_cause'] !== '') {
                $pdf->kv('Root cause', $t['root_cause']);
            }
            $pdf->kv('Created', pdf_date($t['created']) . ($t['creator'] !== '' ? ' by ' . $t['creator'] : ''));
            $pdf->kv('Last edited', pdf_date($t['updated']) . ($t['editor'] !== '' ? ' by ' . $t['editor'] : ''));

            if ($t['description'] !== '') {
                $pdf->ensureSpace(22);
                $pdf->Ln(1);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->Cell(0, 5.5, 'Description', 0, 1);
                $pdf->para($t['description']);
            }

            if (count($t['attachments'])) {
                $pdf->ensureSpace(18);
                $pdf->Ln(1);
                $pdf->SetFont('Helvetica', 'B', 10);
                $pdf->Cell(0, 5.5, 'Attachments (' . count($t['attachments']) . ')', 0, 1);
                foreach ($t['attachments'] as $a) {
                    $pdf->SetFont('Helvetica', '', 9);
                    $pdf->MultiCell(0, 5, pdf_text('  - ' . $a['name'] . ' (' . pdf_date($a['uploaded']) . ($a['by'] !== '' ? ', ' . $a['by'] : '') . ')'));
                }
            }

            $pdf->Ln(2);
            $pdf->SetDrawColor(220);
            $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetPageWidth() - 16, $pdf->GetY());
            $pdf->Ln(4);
        }
    }

    return $pdf->Output('S');
}

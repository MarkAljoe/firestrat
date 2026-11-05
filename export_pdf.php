<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/libs/fpdf.php';
requireLogin();

$db  = getDB();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Main incident
$sql = "SELECT i.*, b.name AS barangay_name
        FROM incidents i
        JOIN barangays b ON b.id = i.barangay_id
        WHERE i.approval_status = 'approved'";
if ($id > 0) $sql .= " AND i.id = $id";
$sql .= " ORDER BY i.created_at DESC";
$incidents = $db->query($sql)->fetchAll();

class IncidentPDF extends FPDF {
    function Header() {
        $this->SetFillColor(224, 62, 26);
        $this->Rect(0, 0, 210, 22, 'F');
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->SetY(5);
        $this->Cell(0, 12, 'FireStrat - Incident Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(255, 220, 210);
        $this->SetY(14);
        $this->Cell(0, 6, 'BFP Virac, Catanduanes   |   Generated: ' . date('F j, Y  g:i A'), 0, 1, 'C');
        $this->Ln(6);
        $this->SetTextColor(0, 0, 0);
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'FireStrat - BFP Virac, Catanduanes   |   Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(40, 40, 50);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 7, '  ' . strtoupper($title), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    function FieldRow($label, $value, $half = false) {
        $w = $half ? 95 : 190;
        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell($w, 5, strtoupper($label), 0, $half ? 0 : 1);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(30, 30, 30);
        $this->Cell($w, 6, $value ?: '-', 0, 1);
        $this->Ln(1);
    }
}

$pdf = new IncidentPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 18);

foreach ($incidents as $inc) {
    $pdf->AddPage();

    $ticket = $inc['ticket_number'] ?? ('TKT-' . str_pad($inc['id'], 5, '0', STR_PAD_LEFT));

    // ── Incident Header ──
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(224, 62, 26);
    $pdf->Cell(0, 8, $ticket, 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(20, 20, 20);
    $pdf->Cell(0, 7, $inc['barangay_name'], 0, 1);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Date: ' . date('F j, Y g:i A', strtotime($inc['created_at'])) .
        '   Risk: ' . ucfirst($inc['risk_level']) .
        '   Status: ' . ucwords(str_replace('_', ' ', $inc['status'])), 0, 1);
    $pdf->Ln(3);

    // ── Original Report ──
    $pdf->SectionTitle('Original Report');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->MultiCell(0, 6, 'Details: ' . ($inc['notes'] ?: 'No details provided.'), 0, 'L');
    $pdf->Ln(1);
    $pdf->Cell(95, 6, 'Reporter: ' . ($inc['reporter_name'] ?: '-'), 0, 0);
    $pdf->Cell(95, 6, 'Contact: ' . ($inc['reporter_contact'] ?: '-'), 0, 1);
    $pdf->Cell(0, 6, 'Source: ' . ucfirst($inc['reported_by']), 0, 1);
    $pdf->Ln(4);

    // ── On-Spot Investigation Report ──
    $onspots = $db->prepare('SELECT * FROM onspot_reports WHERE incident_id = ? ORDER BY created_at DESC');
    $onspots->execute([$inc['id']]);
    $onspotRows = $onspots->fetchAll();

    if ($onspotRows) {
        $pdf->SectionTitle('On-Spot Investigation Report');
        foreach ($onspotRows as $r) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(30, 30, 30);

            $pdf->Cell(95, 6, 'Investigator: ' . ($r['investigator_name'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Position: ' . ($r['position'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Date: ' . ($r['report_date'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Time: ' . ($r['report_time'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Location: ' . ($r['location'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Risk Level: ' . ucfirst($r['risk_level'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Est. Cost Damage: ' . ($r['estimated_cost_damage'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Building Type: ' . ($r['building_type'] ?: '-'), 0, 1);
            $pdf->Cell(63, 6, 'Deployed: ' . ($r['personnel_deployed'] ?? 0), 0, 0);
            $pdf->Cell(63, 6, 'Inj. Personnel: ' . ($r['personnel_injured'] ?? 0), 0, 0);
            $pdf->Cell(64, 6, 'Inj. Civilians: ' . ($r['civilians_injured'] ?? 0), 0, 1);
            $pdf->Cell(0, 6, 'Deaths: ' . ($r['deaths'] ?? 0), 0, 1);
            $pdf->Ln(2);
            $pdf->Cell(95, 6, 'Witness: ' . ($r['witness_name'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Witness Address: ' . ($r['witness_address'] ?: '-'), 0, 1);
            $pdf->Ln(1);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'TESTIMONY', 0, 1);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(0, 6, $r['testimony'] ?: '-', 0, 'L');
            $pdf->Ln(1);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'CONCLUSION OF ORIGIN', 0, 1);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(0, 6, $r['conclusion_of_origin'] ?: '-', 0, 'L');
            $pdf->Ln(4);
        }
    }

    // ── Post Incident Report ──
    $posts = $db->prepare('SELECT * FROM post_incident_reports WHERE incident_id = ? ORDER BY created_at DESC');
    $posts->execute([$inc['id']]);
    $postRows = $posts->fetchAll();

    if ($postRows) {
        $pdf->SectionTitle('Post Incident Report');
        foreach ($postRows as $r) {
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(30, 30, 30);

            $pdf->Cell(95, 6, 'Investigator: ' . ($r['investigator_name'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Position: ' . ($r['position'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Date: ' . ($r['report_date'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Time: ' . ($r['report_time'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Location: ' . ($r['location'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Risk Level: ' . ucfirst($r['risk_level'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Final Cost Damage: ' . ($r['estimated_final_cost_damage'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Building Type: ' . ($r['building_type'] ?: '-'), 0, 1);
            $pdf->Cell(95, 6, 'Incident Scale: ' . ($r['incident_scale'] ?: '-'), 0, 0);
            $pdf->Cell(95, 6, 'Deployed: ' . ($r['personnel_deployed'] ?? 0), 0, 1);
            $pdf->Cell(63, 6, 'Inj. Personnel: ' . ($r['personnel_injured'] ?? 0), 0, 0);
            $pdf->Cell(63, 6, 'Inj. Civilians: ' . ($r['civilians_injured'] ?? 0), 0, 0);
            $pdf->Cell(64, 6, 'Deaths: ' . ($r['deaths'] ?? 0), 0, 1);
            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'DESCRIPTIVE NARRATION', 0, 1);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(0, 6, $r['descriptive_narration'] ?: '-', 0, 'L');
            $pdf->Ln(4);
        }
    }

    if (!$onspotRows && !$postRows) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 6, 'No investigation reports filed for this incident.', 0, 1);
    }
}

$filename = 'firestrat_incident_' . ($id ?: 'all') . '_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit;
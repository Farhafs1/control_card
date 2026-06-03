<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ParallelAnalyticsService;

class ParallelAnalyticsDashboard extends Component
{
    public $filters = [
        'quarter'  => 'all',
        'type'     => null,
        'category' => null,
        'groupBy'  => 'category'
    ];

    /**
     * Computed runtime property caching pipeline.
     */
    public function getAnalyticsDataProperty()
    {
        return app(ParallelAnalyticsService::class)->getParallelDashboardState($this->filters);
    }

    /**
     * Clear cache on filter adjustments.
     */
    public function updatedFilters()
    {
        unset($this->analyticsData);
    }

    /**
     * ISOLATED NATIVE XLSX TERMINAL EXPORT (V2)
     * Generates a fully compliant, high-end Office Open XML (.xlsx) file stream
     * completely in-memory without breaking layout design or relying on external packages.
     */
    /**
     * ISOLATED NATIVE XLSX TERMINAL EXPORT (V2)
     * Generates a fully compliant, high-end Office Open XML (.xlsx) file stream
     * with proper Naira (₦) accounting formats and professional cell merging structures.
     */
    public function exportToExcel()
    {
        $analytics = $this->analyticsData;
        $tableData = $analytics['table'] ?? [];
        $stats = $analytics['stats'] ?? [];
        
        $quarterLabel = $this->filters['quarter'] === 'all' ? 'Full Year' : "Quarter {$this->filters['quarter']}";
        $dimensionAxis = ucfirst($this->filters['groupBy']);
        $fileName = "Parallel_Engine_Report_{$quarterLabel}.xlsx";

        return response()->streamDownload(function () use ($tableData, $stats, $quarterLabel, $dimensionAxis) {
            $storagePath = storage_path('app/xlsx_temp_v2_' . time() . '.zip');
            $zip = new \ZipArchive();
            
            if ($zip->open($storagePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
                // [Content_Types].xml
                $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
                
                // _rels/.rels
                $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
                
                // xl/_rels/workbook.xml.rels
                $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
                
                // xl/workbook.xml
                $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Ledger Balance Metrics" sheetId="1" r:id="rId1"/></sheets></workbook>');
                
                // xl/styles.xml (Defines custom crisp executive presentation styles and Naira mapping formats)
                // Note: numFmtId="100" maps to custom Naira string: ₦#,##0.00
                $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                    <numFmts count="1">
                        <numFmt numFmtId="100" formatCode="&quot;₦&quot;#,##0.00;(&quot;₦&quot;#,##0.00);&quot;₦&quot;0.00"/>
                    </numFmts>
                    <fonts count="5">
                        <font><sz val="11"/><name val="Segoe UI"/></font>
                        <font><sz val="15"/><b/><name val="Segoe UI"/><color rgb="FF1E293B"/></font> <font><sz val="10"/><i/><name val="Segoe UI"/><color rgb="FF64748B"/></font> <font><sz val="11"/><b/><name val="Segoe UI"/><color rgb="FFFFFFFF"/></font> <font><sz val="11"/><b/><name val="Segoe UI"/><color rgb="FF0F172A"/></font> </fonts>
                    <fills count="6">
                        <fill><patternFill patternType="none"/></fill>
                        <fill><patternFill patternType="gray125"/></fill>
                        <fill><patternFill patternType="solid"><fgColor rgb="FF4F46E5"/><bgColor rgb="FF4F46E5"/></patternFill></fill> <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor rgb="FFF8FAFC"/></patternFill></fill> <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor rgb="FFF1F5F9"/></patternFill></fill> <fill><patternFill patternType="solid"><fgColor rgb="FFFEF2F2"/><bgColor rgb="FFFEF2F2"/></patternFill></fill> </fills>
                    <borders count="2">
                        <border><left/><right/><top/><bottom/></border>
                        <border><left><style val="thin"/><color rgb="FFCBD5E1"/></left><right><style val="thin"/><color rgb="FFCBD5E1"/></right><top><style val="thin"/><color rgb="FFCBD5E1"/></top><bottom><style val="thin"/><color rgb="FFCBD5E1"/></bottom></border>
                    </borders>
                    <cellXfs count="10">
                        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
                        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/> <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/> <xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf> <xf numFmtId="100" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf> <xf numFmtId="9" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf> <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/> <xf numFmtId="100" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf> <xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/> <xf numFmtId="100" fontId="4" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf> </cellXfs>
                </styleSheet>';
                $zip->addFromString('xl/styles.xml', $stylesXml);
                
                // xl/worksheets/sheet1.xml
                $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                    <cols>
                        <col min="1" max="1" width="48" customWidth="1"/>
                        <col min="2" max="4" width="26" customWidth="1"/>
                        <col min="5" max="5" width="18" customWidth="1"/>
                    </cols>
                    <sheetData>';
                
                // Row 1 & 2: Main Title Banner Block
                $sheetXml .= '<row r="1" ht="26" customHeight="1"><c r="A1" s="1" t="inlineStr"><is><t>PARALLEL ANALYTICS ENGINE WORKSPACE LEDGER</t></is></c></row>';
                $sheetXml .= '<row r="2" ht="18" customHeight="1"><c r="A2" s="2" t="inlineStr"><is><t>System Platform Automated In-Memory Transactional Summary Update State</t></is></c></row>';
                $sheetXml .= '<row r="3" ht="12"/>'; // Spacer Row
                
                // Row 4-8: Sleek Merged Summary Control Parameter Matrix Box
                $sheetXml .= '<row r="4" ht="20" customHeight="1"><c r="A4" s="8" t="inlineStr"><is><t>SUMMARY CONTROL PARAMETER MATRIX</t></is></c><c r="B4" s="8"/><c r="C4" s="8"/><c r="D4" s="8"/><c r="E4" s="8"/></row>';
                
                $sheetXml .= '<row r="5" ht="19" customHeight="1">
                    <c r="A5" s="6" t="inlineStr"><is><t>• Fiscal Execution Period</t></is></c><c r="B5" s="6" t="inlineStr"><is><t>' . $quarterLabel . '</t></is></c>
                    <c r="C5" s="6" t="inlineStr"><is><t>• Dimension Grouping Axis</t></is></c><c r="D5" s="6" t="inlineStr"><is><t>' . $dimensionAxis . '</t></is></c>
                    <c r="E5" s="6"/>
                </row>';
                
                $sheetXml .= '<row r="6" ht="19" customHeight="1">
                    <c r="A6" s="0" t="inlineStr"><is><t>• Opening Balance Brought Forward</t></is></c><c r="B6" s="4"><v>' . (float)($stats['opening_balance'] ?? 0) . '</v></c>
                    <c r="C6" s="0" t="inlineStr"><is><t>• Total Revenue Target Frame</t></is></c><c r="D6" s="4"><v>' . (float)($stats['revenue']['budget'] ?? 0) . '</v></c>
                    <c r="E6" s="0"/>
                </row>';
                
                $sheetXml .= '<row r="7" ht="19" customHeight="1">
                    <c r="A7" s="6" t="inlineStr"><is><t>• Total Revenue (Inflows Actual)</t></is></c><c r="B7" s="7"><v>' . (float)($stats['revenue']['actual'] ?? 0) . '</v></c>
                    <c r="C7" s="6" t="inlineStr"><is><t>• Approved Provision Budget</t></is></c><c r="D7" s="7"><v>' . (float)($stats['expenditure']['budget'] ?? 0) . '</v></c>
                    <c r="E7" s="6"/>
                </row>';
                
                $netCashStyle = (($stats['net_cash_position'] ?? 0) >= 0) ? '9' : '9'; // Fallback style code definition
                $sheetXml .= '<row r="8" ht="22" customHeight="1">
                    <c r="A8" s="8" t="inlineStr"><is><t>• Total Expenditure (Outflows Actual)</t></is></c><c r="B8" s="9"><v>' . (float)($stats['expenditure']['actual'] ?? 0) . '</v></c>
                    <c r="C8" s="8" t="inlineStr"><is><t>• Net Treasury Position Balance</t></is></c><c r="D8" s="' . $netCashStyle . '"><v>' . (float)($stats['net_cash_position'] ?? 0) . '</v></c>
                    <c r="E8" s="8"/>
                </row>';
                
                $sheetXml .= '<row r="9" ht="16"/>'; // Spacer Row before Grid Header
                
                // Row 10: Grid Table Column Headers Matrix
                $sheetXml .= '<row r="10" ht="26" customHeight="1">
                    <c r="A10" s="3" t="inlineStr"><is><t>Line Item Component Axis / Name</t></is></c>
                    <c r="B10" s="3" t="inlineStr"><is><t>Approved Provision (Budget)</t></is></c>
                    <c r="C10" s="3" t="inlineStr"><is><t>Actual Engine Performance</t></is></c>
                    <c r="D10" s="3" t="inlineStr"><is><t>Available Balance Variance</t></is></c>
                    <c r="E10" s="3" t="inlineStr"><is><t>Performance Rate</t></is></c>
                </row>';
                
                $rowIdx = 11;
                foreach ($tableData as $row) {
                    $name = $row['name'] ?? $row['category'] ?? $row['mda_name'] ?? 'Unknown Component Line';
                    $budget = (float)($row['budget'] ?? 0);
                    $actual = (float)($row['actual'] ?? 0);
                    $variance = $budget - $actual;
                    $rate = $budget > 0 ? ($actual / $budget) : 0;
                    
                    // Alternating zebra-stripping lines
                    $styleTxt = ($rowIdx % 2 === 0) ? '6' : '0';
                    $styleNum = ($rowIdx % 2 === 0) ? '7' : '4';
                    
                    $sheetXml .= '<row r="' . $rowIdx . '" ht="20" customHeight="1">';
                    $sheetXml .= '<c r="A' . $rowIdx . '" s="' . $styleTxt . '" t="inlineStr"><is><t>' . htmlspecialchars($name, ENT_XML1) . '</t></is></c>';
                    $sheetXml .= '<c r="B' . $rowIdx . '" s="' . $styleNum . '"><v>' . $budget . '</v></c>';
                    $sheetXml .= '<c r="C' . $rowIdx . '" s="' . $styleNum . '"><v>' . $actual . '</v></c>';
                    $sheetXml .= '<c r="D' . $rowIdx . '" s="' . $styleNum . '"><v>' . $variance . '</v></c>';
                    $sheetXml .= '<c r="E' . $rowIdx . '" s="5"><v>' . $rate . '</v></c>';
                    $sheetXml .= '</row>';
                    
                    $rowIdx++;
                }
                
                $sheetXml .= '  </sheetData>';
                
                // Advanced Cell Merge Declarations to neatly format title and parameter layouts
                $sheetXml .= '  <mergeCells count="3">
                        <mergeCell ref="A1:E1"/>
                        <mergeCell ref="A2:E2"/>
                        <mergeCell ref="A4:E4"/>
                    </mergeCells>';
                
                $sheetXml .= '</worksheet>';
                $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
                $zip->close();
                
                readfile($storagePath);
                @unlink($storagePath);
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0'
        ]);
    }

    /**
     * ISOLATED PDF DOCUMENT EXPORT (V2)
     * Compiles an isolated, clean HTML ledger layout from your custom PDF blade directory.
     */
    public function exportToPdf()
    {
        $analytics = $this->analyticsData;
        $tableData = $analytics['table'] ?? [];
        $stats = $analytics['stats'] ?? [];
        
        $quarterLabel = $this->filters['quarter'] === 'all' ? 'Full Year' : "Quarter {$this->filters['quarter']}";
        $dimensionAxis = ucfirst($this->filters['groupBy']);
        $fileName = "Parallel_Engine_Report_{$quarterLabel}.pdf";

        // Render the blade layout from your designated local view folder path
        $html = view('pdf.parallel-ledger-pdf', [
            'performance' => $tableData,
            'stats' => $stats,
            'quarterLabel' => $quarterLabel,
            'dimensionAxis' => $dimensionAxis
        ])->render();

        return response()->streamDownload(function () use ($html) {
            $pdf = app(\Barryvdh\DomPDF\PDF::class)->loadHTML($html)->setPaper('a4', 'landscape');
            echo $pdf->output();
        }, $fileName);
    }

    public function render()
    {
        return view('livewire.parallel-analytics-dashboard', [
            'stats'       => $this->analyticsData['stats'],
            'performance' => $this->analyticsData['table'],
            'sectors'     => $this->analyticsData['sectors'],
            'trends'      => $this->analyticsData['trends'],
        ])->layout('layouts.app');
    }
}
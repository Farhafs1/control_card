<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Shape\Drawing\File as Drawing;
use Carbon\Carbon;
use Exception;

class AnalyticsExportController extends Controller
{
    /**
     * PDF EXPORT: Handles the detailed document with AI Insights
     */
    public function export(Request $request)
    {
        try {
            $query = Release::with(['mda', 'subhead.category'])
                ->when($request->search, function($q) use ($request) {
                    $q->where(function($sub) use ($request) {
                        $sub->where('reference_no', 'like', "%{$request->search}%")
                            ->orWhere('narration', 'like', "%{$request->search}%");
                    });
                })
                ->when($request->startDate, fn($q) => $q->whereDate('release_date', '>=', $request->startDate))
                ->when($request->endDate, fn($q) => $q->whereDate('release_date', '<=', $request->endDate))
                ->when($request->minAmount, fn($q) => $q->where('amount', '>=', $request->minAmount))
                ->when($request->categoryId, function($q) use ($request) {
                    $q->whereHas('subhead', fn($sub) => $sub->where('category_id', $request->categoryId));
                });

            $releases = $query->latest('release_date')->get();

            // Prepare AI Text for PDF (Convert Markdown to simple HTML)
            $aiAnalysis = $request->ai_text;
            if ($aiAnalysis) {
                $aiAnalysis = preg_replace('/^#+\s*(.*)$/m', '<h4>$1</h4>', $aiAnalysis);
                $aiAnalysis = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $aiAnalysis);
                $aiAnalysis = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $aiAnalysis);
            }

            $data = [
                'releases'   => $releases,
                'total'      => $releases->sum('amount'),
                'date'       => now()->format('d/m/Y H:i'),
                'aiAnalysis' => $aiAnalysis,
                'filters'    => $request->all()
            ];

            $pdf = Pdf::loadView('exports.expenditure-pdf', $data)->setPaper('a4', 'portrait');
            return $pdf->download('Katsina_Budget_Summary_'.now()->format('Y-m-d').'.pdf');

        } catch (Exception $e) {
            // If it fails, this will show the actual error message instead of a generic 500
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    /**
     * PPT EXPORT: Transforms the AI text into a presentation
     */
    public function generateAIPpt(Request $request)
    {
        try {
            $aiText = $request->ai_text ?? "No Content Provided";
            $presentation = new PhpPresentation();

            // 0. Set Document Properties
            $presentation->getDocumentProperties()->setCompany('MOBEP Systems')
                ->setTitle('Katsina State Expenditure Brief')
                ->setSubject('Executive Audit');

            // 1. Title Slide
            $currentSlide = $presentation->getActiveSlide();
            $this->addBrandingAndWatermark($currentSlide);
            $this->createSlideTitle($currentSlide, "Katsina State Expenditure Brief", "Executive Audit Summary - " . now()->format('M Y'));

            // 2. Parse AI Text into Slides (split by double newline)
            $sections = explode("\n\n", $aiText);
            
            foreach ($sections as $index => $content) {
                if (empty(trim($content)) || $index > 14) continue;

                $slide = $presentation->createSlide();
                $this->addBrandingAndWatermark($slide);

                $title = "Financial Insight " . ($index + 1);

                // Extract Bolded headers from Gemini text as slide titles
                if (preg_match('/\*\*(.*?)\*\*/', $content, $matches)) {
                    $title = $matches[1];
                    $content = str_replace($matches[0], '', $content);
                }

                $this->addContentSlide($slide, $title, trim($content));
            }

            // 3. Download
            $filename = "Katsina_Executive_Briefing_" . now()->format('Ymd') . ".pptx";
            $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
            
            // Clean the output buffer to prevent 500 errors from extra whitespace
            if (ob_get_length()) ob_end_clean();

            header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
            header('Content-Disposition: attachment;filename="'. $filename .'"');
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    /**
     * Helper: Adds Branding and Watermark
     */
    private function addBrandingAndWatermark($slide)
    {
        $crestPath = public_path('assets/images/katsina-crest.png');
        if (file_exists($crestPath)) {
            $shape = $slide->createDrawingShape(); // Use the slide to create the shape
            $shape->setName('Katsina Crest')
                ->setPath($crestPath)
                ->setHeight(70)
                ->setOffsetX(880)
                ->setOffsetY(630);
}
        $shape = $slide->createRichTextShape()
            ->setHeight(300)
            ->setWidth(800)
            ->setOffsetX(100)
            ->setOffsetY(200)
            ->setRotation(-30);

        $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $textRun = $shape->createTextRun("INTERNAL GOVERNMENT USE ONLY - CONFIDENTIAL");
        $textRun->getFont()
            ->setSize(40)
            ->setBold(true)
            ->setItalic(true)
            ->setColor(new Color('FFe2e8f0'));
    }

    private function createSlideTitle($slide, $title, $subtitle)
    {
        $shape = $slide->createRichTextShape()->setHeight(200)->setWidth(900)->setOffsetX(50)->setOffsetY(150);
        $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $textRun = $shape->createTextRun($title);
        $textRun->getFont()->setBold(true)->setSize(45)->setColor(new Color('FF006400'));

        $shape = $slide->createRichTextShape()->setHeight(100)->setWidth(900)->setOffsetX(50)->setOffsetY(350);
        $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $shape->createTextRun($subtitle)->getFont()->setSize(24);
    }

    private function addContentSlide($slide, $title, $body)
    {
        $shape = $slide->createRichTextShape()->setHeight(100)->setWidth(900)->setOffsetX(50)->setOffsetY(30);
        $shape->createTextRun($title)->getFont()->setBold(true)->setSize(32)->setColor(new Color('FF006400'));

        $shape = $slide->createRichTextShape()->setHeight(500)->setWidth(900)->setOffsetX(50)->setOffsetY(150);
        $shape->createTextRun($body)->getFont()->setSize(18);
    }
}
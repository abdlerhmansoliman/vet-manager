<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\ClinicalCase;
use App\Models\Tenant;
use App\Models\Visit;
use App\Services\Reports\VisitReportPresenter;
use App\Services\Tenancy\TenantManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PdfController extends Controller
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Generate, stream, or download the official Single Clinical Visit Report PDF.
     */
    public function visit(Visit $visit, Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        $visit->load([
            'case.patient.client',
            'case.diagnoses',
            'case.primaryVeterinarian',
            'progressNotes.veterinarian',
            'doseAdministrations.product',
            'images',
        ]);

        abort_if(! $visit->case?->patient, 422, 'Visit has no linked patient record.');

        $case = $visit->case;
        $patient = $case->patient;
        $client = $patient->client;

        $presenter = new VisitReportPresenter(config('app.timezone', 'UTC'));
        $this->configureDomPdf();

        $pdf = Pdf::loadView('pdf.clinical-visit-report', [
            'visit' => $visit,
            'case' => $case,
            'patient' => $patient,
            'client' => $client,
            'tenant' => $tenant,
            'fmt' => $presenter,
            'generatedBy' => $request->user()?->name ?? 'Attending Clinician',
        ]);

        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_text(480, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.44, 0.5, 0.59]);

        $safeName = Str::slug(Str::ascii((string) ($patient->name_or_tag ?? 'patient')));
        $filename = "clinical-visit-{$visit->id}-".($safeName !== '' ? $safeName : 'patient').'.pdf';

        return $this->buildPdfResponse((string) $dompdf->output(), $filename, $request->boolean('download'));
    }

    /**
     * Generate, stream, or download the comprehensive Medical Case & Multi-Visit Dossier PDF.
     */
    public function case(ClinicalCase $case, Request $request): Response
    {
        $tenant = $this->resolveTenant($request);

        $case->load([
            'patient.client',
            'diagnoses',
            'primaryVeterinarian',
            'visits' => function ($query): void {
                $query->orderBy('visit_date', 'asc')->with([
                    'progressNotes.veterinarian',
                    'doseAdministrations.product',
                    'images',
                ]);
            },
        ]);

        abort_if(! $case->patient, 422, 'Case has no linked patient record.');

        $patient = $case->patient;
        $client = $patient->client;

        $presenter = new VisitReportPresenter(config('app.timezone', 'UTC'));
        $this->configureDomPdf();

        $pdf = Pdf::loadView('pdf.clinical-case-report', [
            'case' => $case,
            'patient' => $patient,
            'client' => $client,
            'visits' => $case->visits,
            'tenant' => $tenant,
            'fmt' => $presenter,
            'generatedBy' => $request->user()?->name ?? 'Attending Clinician',
        ]);

        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $dompdf->getCanvas()->page_text(480, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0.44, 0.5, 0.59]);

        $safeName = Str::slug(Str::ascii((string) ($patient->name_or_tag ?? 'patient')));
        $caseIdentifier = Str::slug((string) ($case->case_number ?? $case->id));
        $filename = "clinical-case-{$caseIdentifier}-".($safeName !== '' ? $safeName : 'patient').'.pdf';

        return $this->buildPdfResponse((string) $dompdf->output(), $filename, $request->boolean('download'));
    }

    private function resolveTenant(Request $request): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantManager->getTenant();
        abort_if(! $tenant, 404, 'Active tenant could not be resolved.');

        return $tenant;
    }

    private function configureDomPdf(): void
    {
        Pdf::setOption([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'isFontSubsettingEnabled' => true,
        ]);
    }

    private function buildPdfResponse(string $content, string $filename, bool $download): Response
    {
        $disposition = $download ? 'attachment' : 'inline';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

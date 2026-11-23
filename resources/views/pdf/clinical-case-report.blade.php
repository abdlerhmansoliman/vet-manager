<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Comprehensive Clinical Case Dossier - {{ $case->case_number ?? 'Case' }}</title>
    <style>
        @page {
            margin: 14mm 14mm 18mm 14mm;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #1a202c;
            font-size: 8.5pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .clinic-brand {
            font-size: 15pt;
            font-weight: bold;
            color: #2b6cb0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .clinic-subtitle {
            font-size: 8pt;
            color: #718096;
            margin-top: 2px;
        }
        .report-badge-box {
            text-align: right;
        }
        .report-title {
            font-size: 12pt;
            font-weight: bold;
            color: #2d3748;
            margin: 0;
        }
        .report-meta {
            font-size: 7.5pt;
            color: #718096;
            margin-top: 2px;
        }
        .section-header {
            font-size: 9.5pt;
            font-weight: bold;
            color: #2b6cb0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-top: 12px;
            margin-bottom: 6px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .grid-table td {
            vertical-align: top;
            padding: 2px 4px;
        }
        .card {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        .label {
            font-size: 7pt;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .value {
            font-size: 8.5pt;
            color: #2d3748;
        }
        .value-strong {
            font-weight: bold;
            color: #1a202c;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-signed {
            background-color: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        .badge-draft {
            background-color: #feebc8;
            color: #744210;
            border: 1px solid #fbd38d;
        }
        .badge-allergy {
            background-color: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
            font-weight: bold;
        }
        .badge-species {
            background-color: #e2e8f0;
            color: #2d3748;
        }
        .visit-timeline-card {
            background-color: #ffffff;
            border: 1px solid #cbd5e0;
            border-left: 4px solid #2b6cb0;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .vitals-bar {
            background-color: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 3px;
            padding: 4px 6px;
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .vitals-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .vitals-table td {
            padding: 1px 3px;
            border-right: 1px solid #bee3f8;
        }
        .vitals-table td:last-child {
            border-right: none;
        }
        .vitals-num {
            font-size: 9.5pt;
            font-weight: bold;
            color: #2b6cb0;
        }
        .vitals-unit {
            font-size: 6.5pt;
            color: #718096;
        }
        .soap-block {
            margin-bottom: 5px;
            border-left: 2px solid #cbd5e0;
            padding-left: 6px;
        }
        .soap-letter {
            font-size: 8pt;
            font-weight: bold;
            color: #2b6cb0;
            display: inline-block;
            width: 14px;
        }
        .soap-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #4a5568;
            text-transform: uppercase;
        }
        .soap-text {
            font-size: 8pt;
            color: #2d3748;
            margin-top: 1px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 8px;
        }
        .data-table th {
            background-color: #edf2f7;
            color: #4a5568;
            font-size: 7pt;
            text-transform: uppercase;
            font-weight: bold;
            padding: 4px 6px;
            border: 1px solid #cbd5e0;
            text-align: left;
        }
        .data-table td {
            font-size: 8pt;
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f7fafc;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            margin-top: 14px;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <div class="clinic-brand">{{ $fmt->text($tenant->name, 'Veterinary Clinic') }}</div>
                <div class="clinic-subtitle">Comprehensive Clinical Case Dossier & Medical Record</div>
            </td>
            <td class="report-badge-box" style="width: 45%; vertical-align: middle;">
                <h1 class="report-title">MEDICAL CASE DOSSIER</h1>
                <div class="report-meta">
                    <strong>Case #:</strong> {{ $case->case_number ?? $case->id }} | 
                    <strong>Visits:</strong> {{ $visits->count() }} Encounters<br>
                    <strong>Admitted:</strong> {{ $fmt->date($case->admitted_at ?: $case->created_at, 'Y-m-d') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Tier 1: Patient & Client Signalment --}}
    <table class="grid-table">
        <tr>
            {{-- Patient Box --}}
            <td style="width: 50%; padding-left: 0;">
                <div class="card">
                    <div style="font-weight: bold; color: #2b6cb0; font-size: 8pt; border-bottom: 1px solid #edf2f7; padding-bottom: 2px; margin-bottom: 4px;">
                        PATIENT SIGNALMENT
                    </div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; padding: 1px 0;">
                                <div class="label">Name / Tag</div>
                                <div class="value-strong">{{ $fmt->text($patient->name_or_tag, 'Unknown') }}</div>
                            </td>
                            <td style="width: 50%; padding: 1px 0;">
                                <div class="label">Species / Breed</div>
                                <div class="value">
                                    <span class="badge badge-species">{{ $fmt->text($patient->species, 'Animal') }}</span>
                                    {{ $fmt->text($patient->breed, '') }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0;">
                                <div class="label">Gender</div>
                                <div class="value">{{ $fmt->enum($patient->gender, 'Unknown') }}</div>
                            </td>
                            <td style="padding: 1px 0;">
                                <div class="label">Age</div>
                                <div class="value">{{ $patient->age !== null ? $patient->age . ' yrs' : 'Unknown' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0;">
                                <div class="label">Weight</div>
                                <div class="value">{{ $patient->current_weight_kg ? $patient->current_weight_kg . ' kg' : '—' }}</div>
                            </td>
                            <td style="padding: 1px 0;">
                                <div class="label">Microchip / Tag</div>
                                <div class="value">{{ $fmt->text($patient->microchip_number ?: $patient->ear_tag_id, 'None') }}</div>
                            </td>
                        </tr>
                        @if(!empty($patient->allergies))
                        <tr>
                            <td colspan="2" style="padding-top: 3px;">
                                <span class="badge badge-allergy">⚠ ALLERGIES:</span>
                                <span style="font-size: 7.5pt; color: #c53030; font-weight: bold;">
                                    {{ $fmt->list($patient->allergies) }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>

            {{-- Client / Owner Box --}}
            <td style="width: 50%; padding-right: 0;">
                <div class="card">
                    <div style="font-weight: bold; color: #2b6cb0; font-size: 8pt; border-bottom: 1px solid #edf2f7; padding-bottom: 2px; margin-bottom: 4px;">
                        OWNER / CLIENT
                    </div>
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="2" style="padding: 1px 0;">
                                <div class="label">Name</div>
                                <div class="value-strong">{{ $fmt->text($client?->name, 'Walk-in Client') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding: 1px 0;">
                                <div class="label">Phone</div>
                                <div class="value">{{ $fmt->text($client?->phone_primary, '—') }}</div>
                            </td>
                            <td style="width: 50%; padding: 1px 0;">
                                <div class="label">Email</div>
                                <div class="value">{{ $fmt->text($client?->email, '—') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 1px 0;">
                                <div class="label">Address</div>
                                <div class="value">{{ $fmt->text($client?->address, 'On file') }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tier 2: Case Overview Ribbon --}}
    <div class="card" style="background-color: #edf2f7; border-color: #cbd5e0; margin-bottom: 10px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 25%;">
                    <div class="label">Case Number</div>
                    <div class="value-strong" style="color: #2b6cb0;">{{ $fmt->text($case->case_number, 'N/A') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Primary Clinician</div>
                    <div class="value-strong">{{ $fmt->text($case->primaryVeterinarian?->name, 'Attending Vet') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Case Status</div>
                    <div class="value">{{ $fmt->enum($case->status, 'Open') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Priority</div>
                    <div class="value">{{ $fmt->enum($case->priority, 'Routine') }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 4px;">
                    <div class="label">Chief Complaint</div>
                    <div class="value">{{ $fmt->text($case->chief_complaint, 'Clinical case evaluation') }}</div>
                </td>
                <td colspan="2" style="padding-top: 4px;">
                    <div class="label">Final / Provisional Diagnosis</div>
                    <div class="value-strong" style="color: #2c5282;">
                        {{ $fmt->text($case->final_diagnosis ?: $case->provisional_diagnosis, 'Under clinical evaluation') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Tier 3: Chronological Visits Timeline --}}
    <div class="section-header">Chronological Clinical Encounters ({{ $visits->count() }} Visits)</div>
    @forelse($visits as $index => $visit)
        <div class="visit-timeline-card">
            {{-- Visit Heading --}}
            <table style="width: 100%; border-bottom: 1px solid #edf2f7; padding-bottom: 4px; margin-bottom: 6px;">
                <tr>
                    <td style="width: 70%;">
                        <strong style="color: #2b6cb0; font-size: 9.5pt;">Visit #{{ $index + 1 }}: {{ $fmt->text($visit->title, 'Clinical Encounter') }}</strong>
                        <span style="font-size: 7.5pt; color: #718096; margin-left: 6px;">({{ $fmt->enum($visit->visit_type, 'Routine') }})</span>
                    </td>
                    <td style="width: 30%; text-align: right;">
                        <span style="font-size: 8pt; color: #4a5568;">{{ $fmt->date($visit->visit_date) }}</span>
                        @if($visit->is_signed)
                            <span class="badge badge-signed" style="margin-left: 4px;">Signed</span>
                        @else
                            <span class="badge badge-draft" style="margin-left: 4px;">Draft</span>
                        @endif
                    </td>
                </tr>
            </table>

            {{-- Reason for Visit --}}
            @if($visit->reason_for_visit)
                <div style="font-size: 8pt; color: #4a5568; margin-bottom: 5px;">
                    <strong>Reason for visit:</strong> {{ $visit->reason_for_visit }}
                </div>
            @endif

            {{-- Vitals for this visit --}}
            @php $vitalsNote = $visit->progressNotes->sortByDesc('created_at')->first(); @endphp
            @if($vitalsNote)
                <div class="vitals-bar">
                    <table class="vitals-table">
                        <tr>
                            <td><span class="vitals-unit">Temp:</span> <span class="vitals-num">{{ $vitalsNote->temperature_c ?? '—' }}°C</span></td>
                            <td><span class="vitals-unit">HR:</span> <span class="vitals-num">{{ $vitalsNote->heart_rate_bpm ?? '—' }} bpm</span></td>
                            <td><span class="vitals-unit">RR:</span> <span class="vitals-num">{{ $vitalsNote->respiratory_rate ?? '—' }} rpm</span></td>
                            <td><span class="vitals-unit">BP:</span> <span class="vitals-num">{{ $vitalsNote->systolic_bp ? $vitalsNote->systolic_bp . '/' . $vitalsNote->diastolic_bp : '—' }}</span></td>
                            <td><span class="vitals-unit">Weight:</span> <span class="vitals-num">{{ $vitalsNote->weight_kg ?? '—' }} kg</span></td>
                        </tr>
                    </table>
                </div>
            @endif

            {{-- SOAP Notes --}}
            @foreach($visit->progressNotes as $note)
                @if($note->subjective_notes)
                    <div class="soap-block" style="border-left-color: #4299e1;">
                        <span class="soap-letter">S</span><span class="soap-title">Subjective</span>
                        <div class="soap-text">{!! nl2br(e($note->subjective_notes)) !!}</div>
                    </div>
                @endif
                @if($note->objective_notes)
                    <div class="soap-block" style="border-left-color: #48bb78;">
                        <span class="soap-letter">O</span><span class="soap-title">Objective</span>
                        <div class="soap-text">{!! nl2br(e($note->objective_notes)) !!}</div>
                    </div>
                @endif
                @if($note->assessment_notes)
                    <div class="soap-block" style="border-left-color: #ed8936;">
                        <span class="soap-letter">A</span><span class="soap-title">Assessment</span>
                        <div class="soap-text">{!! nl2br(e($note->assessment_notes)) !!}</div>
                    </div>
                @endif
                @if($note->plan_notes)
                    <div class="soap-block" style="border-left-color: #9f7aea;">
                        <span class="soap-letter">P</span><span class="soap-title">Plan</span>
                        <div class="soap-text">{!! nl2br(e($note->plan_notes)) !!}</div>
                    </div>
                @endif
            @endforeach

            {{-- Doses administered in this visit --}}
            @if($visit->doseAdministrations->isNotEmpty())
                <div style="font-size: 7.5pt; font-weight: bold; color: #4a5568; margin-top: 4px; text-transform: uppercase;">Administered Medications:</div>
                <ul style="margin: 2px 0 0 16px; padding: 0; font-size: 7.5pt; color: #2d3748;">
                    @foreach($visit->doseAdministrations as $dose)
                        <li>
                            <strong>{{ $fmt->text($dose->product?->name, 'Pharmaceutical') }}</strong>:
                            {{ $fmt->dose($dose->dose_amount_base_units, $dose->product?->base_unit ?? $dose->dose_unit) }}
                            via {{ strtoupper($fmt->enum($dose->route_administered ?: $dose->route, 'N/A')) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <div style="font-size: 8.5pt; color: #718096; font-style: italic; padding: 6px;">
            No clinical visits recorded under this case yet.
        </div>
    @endforelse

    {{-- Case Diagnostic Media Registry --}}
    @php
        $allImages = $visits->flatMap->images;
    @endphp
    @if($allImages->isNotEmpty())
        <div class="section-header">Cumulative Case Diagnostic Images ({{ $allImages->count() }})</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Image / Scan Title</th>
                    <th style="width: 30%;">Modality / Type</th>
                    <th style="width: 30%;">Upload Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allImages as $img)
                <tr>
                    <td><strong>{{ $fmt->text($img->title, 'Diagnostic Media') }}</strong></td>
                    <td>{{ $fmt->enum($img->image_type, 'Diagnostic Scan') }}</td>
                    <td>{{ $fmt->date($img->created_at) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Footer --}}
    <table class="footer-table">
        <tr>
            <td style="vertical-align: top; width: 60%; font-size: 7.5pt; color: #718096;">
                <strong>CONFIDENTIAL MEDICAL RECORD DOSSIER</strong><br>
                Practice: <strong>{{ $tenant->name ?? 'Veterinary Clinic' }}</strong> (<code>{{ $tenant->slug ?? 'clinic' }}</code>)<br>
                Certified extraction by: <strong>{{ $generatedBy }}</strong> on {{ $fmt->date(now(), 'Y-m-d H:i:s T') }}
            </td>
            <td style="vertical-align: top; width: 40%; text-align: right; font-size: 7.5pt; color: #718096;">
                Primary Clinician: <strong>{{ $fmt->text($case->primaryVeterinarian?->name, 'Attending Clinician') }}</strong><br>
                Case Status: <strong>{{ $fmt->enum($case->status, 'Open') }}</strong>
            </td>
        </tr>
    </table>

</body>
</html>

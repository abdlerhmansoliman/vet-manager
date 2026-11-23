<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Clinical Visit Report - {{ $fmt->text($visit->title, 'Encounter') }}</title>
    <style>
        @page {
            margin: 14mm 14mm 18mm 14mm;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #1a202c;
            font-size: 9pt;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        .draft-watermark {
            position: fixed;
            top: 35%;
            left: 12%;
            transform: rotate(-35deg);
            font-size: 65pt;
            color: #f7fafc;
            border: 8px solid #edf2f7;
            padding: 10px 40px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 10px;
            z-index: -1000;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2b6cb0;
            padding-bottom: 10px;
            margin-bottom: 12px;
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
            font-size: 13pt;
            font-weight: bold;
            color: #2d3748;
            margin: 0;
        }
        .report-meta {
            font-size: 8pt;
            color: #718096;
            margin-top: 3px;
        }
        .section-header {
            font-size: 9.5pt;
            font-weight: bold;
            color: #2b6cb0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .grid-table td {
            vertical-align: top;
            padding: 3px 5px;
        }
        .card {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 6px;
        }
        .label {
            font-size: 7.5pt;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 1px;
        }
        .value {
            font-size: 9pt;
            color: #2d3748;
        }
        .value-strong {
            font-weight: bold;
            color: #1a202c;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7.5pt;
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
        .vitals-bar {
            background-color: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
        }
        .vitals-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }
        .vitals-table td {
            padding: 2px 4px;
            border-right: 1px solid #bee3f8;
        }
        .vitals-table td:last-child {
            border-right: none;
        }
        .vitals-num {
            font-size: 11pt;
            font-weight: bold;
            color: #2b6cb0;
        }
        .vitals-unit {
            font-size: 7pt;
            color: #718096;
        }
        .soap-block {
            margin-bottom: 8px;
            border-left: 3px solid #cbd5e0;
            padding-left: 8px;
        }
        .soap-letter {
            font-size: 9pt;
            font-weight: bold;
            color: #2b6cb0;
            display: inline-block;
            width: 16px;
        }
        .soap-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #4a5568;
            text-transform: uppercase;
        }
        .soap-text {
            font-size: 8.5pt;
            color: #2d3748;
            margin-top: 2px;
            white-space: pre-line;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #edf2f7;
            color: #4a5568;
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
            padding: 6px;
            border: 1px solid #cbd5e0;
            text-align: left;
        }
        .data-table td {
            font-size: 8.5pt;
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            color: #2d3748;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f7fafc;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #e2e8f0;
            margin-top: 16px;
            padding-top: 8px;
        }
        .signature-box {
            border: 1px dashed #cbd5e0;
            border-radius: 4px;
            padding: 8px 12px;
            background-color: #fffaf0;
            width: 260px;
            float: right;
            text-align: left;
        }
        .stamp-title {
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: bold;
            color: #c05621;
        }
        .stamp-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #2d3748;
            margin-top: 2px;
        }
        .stamp-time {
            font-size: 7.5pt;
            color: #718096;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    @if(!$visit->is_signed)
        <div class="draft-watermark">DRAFT</div>
    @endif

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                <div class="clinic-brand">{{ $fmt->text($tenant->name, 'Veterinary Clinic') }}</div>
                <div class="clinic-subtitle">Professional Clinical Summary & Encounter Record</div>
            </td>
            <td class="report-badge-box" style="width: 45%; vertical-align: middle;">
                <h1 class="report-title">CLINICAL VISIT REPORT</h1>
                <div class="report-meta">
                    <strong>Report ID:</strong> {{ $visit->id }}<br>
                    <strong>Encounter Date:</strong> {{ $fmt->date($visit->visit_date) }}
                </div>
                <div style="margin-top: 4px;">
                    @if($visit->is_signed)
                        <span class="badge badge-signed">✓ Certified & Locked Record</span>
                    @else
                        <span class="badge badge-draft">Draft Encounter</span>
                    @endif
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
                    <div style="font-weight: bold; color: #2b6cb0; font-size: 8.5pt; border-bottom: 1px solid #edf2f7; padding-bottom: 3px; margin-bottom: 5px;">
                        PATIENT SIGNALMENT
                    </div>
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 50%; padding: 2px 0;">
                                <div class="label">Name / Tag</div>
                                <div class="value-strong">{{ $fmt->text($patient->name_or_tag, 'Unknown') }}</div>
                            </td>
                            <td style="width: 50%; padding: 2px 0;">
                                <div class="label">Species / Breed</div>
                                <div class="value">
                                    <span class="badge badge-species">{{ $fmt->text($patient->species, 'Animal') }}</span>
                                    {{ $fmt->text($patient->breed, '') }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">
                                <div class="label">Gender</div>
                                <div class="value">{{ $fmt->enum($patient->gender, 'Unknown') }}</div>
                            </td>
                            <td style="padding: 2px 0;">
                                <div class="label">Age / DOB</div>
                                <div class="value">
                                    @if($patient->age !== null)
                                        {{ $patient->age }} yrs
                                    @elseif($patient->date_of_birth)
                                        {{ $fmt->date($patient->date_of_birth, 'Y-m-d') }}
                                    @else
                                        Unknown
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">
                                <div class="label">Recorded Weight</div>
                                <div class="value">
                                    {{ $patient->current_weight_kg ? $patient->current_weight_kg . ' kg' : '—' }}
                                </div>
                            </td>
                            <td style="padding: 2px 0;">
                                <div class="label">Identification</div>
                                <div class="value">{{ $fmt->text($patient->microchip_number ?: $patient->ear_tag_id, 'None') }}</div>
                            </td>
                        </tr>
                        @if(!empty($patient->allergies))
                        <tr>
                            <td colspan="2" style="padding-top: 4px;">
                                <span class="badge badge-allergy">⚠ ALLERGIES:</span>
                                <span style="font-size: 8pt; color: #c53030; font-weight: bold;">
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
                    <div style="font-weight: bold; color: #2b6cb0; font-size: 8.5pt; border-bottom: 1px solid #edf2f7; padding-bottom: 3px; margin-bottom: 5px;">
                        CLIENT / RESPONSIBLE PARTY
                    </div>
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="2" style="padding: 2px 0;">
                                <div class="label">Full Name</div>
                                <div class="value-strong">{{ $fmt->text($client?->name, 'Walk-in Client') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%; padding: 2px 0;">
                                <div class="label">Phone</div>
                                <div class="value">{{ $fmt->text($client?->phone_primary, '—') }}</div>
                            </td>
                            <td style="width: 50%; padding: 2px 0;">
                                <div class="label">Email</div>
                                <div class="value">{{ $fmt->text($client?->email, '—') }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 2px 0;">
                                <div class="label">Address</div>
                                <div class="value">{{ $fmt->text($client?->address, 'On file') }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tier 2: Medical Case Umbrella --}}
    <div class="card" style="margin-top: 2px; margin-bottom: 10px; background-color: #edf2f7; border-color: #cbd5e0;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 25%;">
                    <div class="label">Case Number</div>
                    <div class="value-strong" style="color: #2b6cb0;">{{ $fmt->text($case->case_number, 'N/A') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Encounter Type</div>
                    <div class="value">{{ $fmt->enum($visit->visit_type, 'Routine') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Priority</div>
                    <div class="value">{{ $fmt->enum($case->priority, 'Routine') }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Status</div>
                    <div class="value">{{ $fmt->enum($case->status, 'Open') }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 4px;">
                    <div class="label">Chief Complaint</div>
                    <div class="value">{{ $fmt->text($case->chief_complaint, 'General consultation') }}</div>
                </td>
                <td colspan="2" style="padding-top: 4px;">
                    <div class="label">Diagnosis</div>
                    <div class="value-strong">{{ $fmt->text($case->final_diagnosis ?: $case->provisional_diagnosis, 'Under clinical evaluation') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Tier 3: Clinical Vitals Ribbon --}}
    @php
        $latestNote = $visit->progressNotes->sortByDesc('created_at')->first();
    @endphp
    @if($latestNote)
    <div class="vitals-bar">
        <table class="vitals-table">
            <tr>
                <td>
                    <div class="label">Temperature</div>
                    <div class="vitals-num">{{ $latestNote->temperature_c ?? '—' }} <span class="vitals-unit">°C</span></div>
                </td>
                <td>
                    <div class="label">Heart Rate</div>
                    <div class="vitals-num">{{ $latestNote->heart_rate_bpm ?? '—' }} <span class="vitals-unit">bpm</span></div>
                </td>
                <td>
                    <div class="label">Respiratory Rate</div>
                    <div class="vitals-num">{{ $latestNote->respiratory_rate ?? '—' }} <span class="vitals-unit">rpm</span></div>
                </td>
                <td>
                    <div class="label">Blood Pressure</div>
                    <div class="vitals-num">
                        @if($latestNote->systolic_bp || $latestNote->diastolic_bp)
                            {{ $latestNote->systolic_bp ?? '—' }}/{{ $latestNote->diastolic_bp ?? '—' }}
                        @else
                            —
                        @endif
                        <span class="vitals-unit">mmHg</span>
                    </div>
                </td>
                <td>
                    <div class="label">Mucous Memb.</div>
                    <div class="vitals-num" style="font-size: 9.5pt;">{{ $fmt->enum($latestNote->mucous_membrane, 'Normal') }}</div>
                </td>
                <td>
                    <div class="label">Visit Weight</div>
                    <div class="vitals-num">{{ $latestNote->weight_kg ?? ($patient->current_weight_kg ?? '—') }} <span class="vitals-unit">kg</span></div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Tier 3: SOAP Examination Notes --}}
    <div class="section-header">Clinical Examination & Progress Notes (SOAP)</div>
    @forelse($visit->progressNotes as $note)
        <div style="margin-bottom: 8px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px;">
            <div style="font-size: 7.5pt; color: #718096; margin-bottom: 6px;">
                Recorded by <strong>{{ $fmt->text($note->veterinarian?->name, 'Attending Clinician') }}</strong> on {{ $fmt->date($note->created_at) }}
                @if($note->is_signed)
                    <span class="badge badge-signed" style="margin-left: 6px;">Signed</span>
                @endif
            </div>

            @if($note->subjective_notes)
            <div class="soap-block" style="border-left-color: #4299e1;">
                <span class="soap-letter">S</span><span class="soap-title">Subjective / History</span>
                <div class="soap-text">{!! nl2br(e($note->subjective_notes)) !!}</div>
            </div>
            @endif

            @if($note->objective_notes)
            <div class="soap-block" style="border-left-color: #48bb78;">
                <span class="soap-letter">O</span><span class="soap-title">Objective / Physical Examination</span>
                <div class="soap-text">{!! nl2br(e($note->objective_notes)) !!}</div>
            </div>
            @endif

            @if($note->assessment_notes)
            <div class="soap-block" style="border-left-color: #ed8936;">
                <span class="soap-letter">A</span><span class="soap-title">Assessment / Differential Interpretation</span>
                <div class="soap-text">{!! nl2br(e($note->assessment_notes)) !!}</div>
            </div>
            @endif

            @if($note->plan_notes)
            <div class="soap-block" style="border-left-color: #9f7aea;">
                <span class="soap-letter">P</span><span class="soap-title">Plan / Treatments & Prescriptions</span>
                <div class="soap-text">{!! nl2br(e($note->plan_notes)) !!}</div>
            </div>
            @endif
        </div>
    @empty
        <div style="font-size: 8.5pt; color: #718096; font-style: italic; padding: 6px;">
            {{ $fmt->text($visit->reason_for_visit, 'Routine encounter with no additional clinical notes recorded.') }}
        </div>
    @endforelse

    {{-- Tier 3: Administered Medications & Pharmacy eMAR --}}
    @if($visit->doseAdministrations->isNotEmpty())
        <div class="section-header">Medications & Administered Treatments (eMAR)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Medication / Pharmaceutical</th>
                    <th style="width: 20%;">Dose Given</th>
                    <th style="width: 15%;">Route</th>
                    <th style="width: 30%;">Time Administered</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visit->doseAdministrations as $dose)
                <tr>
                    <td>
                        <strong>{{ $fmt->text($dose->product?->name, 'Pharmaceutical Agent') }}</strong>
                    </td>
                    <td>{{ $fmt->dose($dose->dose_amount_base_units, $dose->product?->base_unit ?? $dose->dose_unit) }}</td>
                    <td>{{ strtoupper($fmt->enum($dose->route_administered ?: $dose->route, 'N/A')) }}</td>
                    <td>{{ $fmt->date($dose->administered_at ?: $dose->created_at) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Tier 3: Diagnostic Images & Attachments --}}
    @if($visit->images->isNotEmpty())
        <div class="section-header">Diagnostic Media & Imaging Attachments</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Image / Scan Title</th>
                    <th style="width: 30%;">Modality / Type</th>
                    <th style="width: 30%;">Upload Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($visit->images as $img)
                <tr>
                    <td><strong>{{ $fmt->text($img->title, 'Diagnostic Media') }}</strong></td>
                    <td>{{ $fmt->enum($img->image_type, 'Diagnostic Scan') }}</td>
                    <td>{{ $fmt->date($img->created_at) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Footer & Legal Certification Box --}}
    <table class="footer-table">
        <tr>
            <td style="vertical-align: top; width: 50%; font-size: 7.5pt; color: #718096;">
                <strong>CONFIDENTIAL MEDICAL RECORD</strong><br>
                This clinical summary was generated directly from the certified VetManager database.<br>
                Practice Tenant: <code>{{ $tenant->slug ?? 'clinic' }}</code><br>
                Generated by: <strong>{{ $generatedBy }}</strong> on {{ $fmt->date(now(), 'Y-m-d H:i:s T') }}
            </td>
            <td style="vertical-align: top; width: 50%;">
                <div class="signature-box">
                    @if($visit->is_signed && $visit->signed_at)
                        <div class="stamp-title" style="color: #22543d;">✓ DIGITALLY CERTIFIED & LOCKED</div>
                        <div class="stamp-name">
                            Authorized: {{ $fmt->text($visit->signed_by, $case->primaryVeterinarian?->name ?? 'Licensed Attending Clinician') }}
                        </div>
                        <div class="stamp-time">
                            Certified At: {{ $fmt->date($visit->signed_at, 'Y-m-d H:i:s T') }}
                        </div>
                    @else
                        <div class="stamp-title" style="color: #c05621;">• DRAFT RECORD - NOT CERTIFIED</div>
                        <div class="stamp-name" style="font-size: 8pt; color: #718096;">
                            Pending final clinical signature & locking.
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>

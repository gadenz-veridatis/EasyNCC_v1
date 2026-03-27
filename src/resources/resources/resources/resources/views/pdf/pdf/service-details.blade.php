<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            color: #0d6efd;
            margin-bottom: 3px;
        }
        .header .subtitle {
            font-size: 11px;
            color: #666;
        }
        .section {
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #fff;
            background-color: #0d6efd;
            padding: 4px 8px;
            margin-bottom: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        table td.label {
            font-weight: bold;
            color: #555;
            width: 35%;
        }
        .two-col {
            width: 100%;
        }
        .two-col td {
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            border: 1px solid #dee2e6;
            padding: 6px;
            margin-bottom: 6px;
        }
        .highlight {
            background-color: #f0f7ff;
            padding: 6px;
            border-left: 3px solid #0d6efd;
            margin-bottom: 8px;
        }
        .stops-table {
            border: 1px solid #dee2e6;
        }
        .stops-table th {
            background-color: #e9ecef;
            padding: 3px 6px;
            font-size: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .stops-table td {
            padding: 3px 6px;
            font-size: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #dee2e6;
            padding-top: 6px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-primary {
            background-color: #0d6efd;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DETTAGLI SERVIZIO</h1>
        <div class="subtitle">
            Rif. {{ $service->reference_number ?? 'N/D' }} &mdash; ID #{{ $service->id }}
        </div>
    </div>

    {{-- INFORMAZIONI GENERALI --}}
    <div class="section">
        <div class="section-title">INFORMAZIONI GENERALI</div>
        <table>
            <tr>
                <td class="label">Tipo Servizio</td>
                <td>{{ $service->service_type ?? 'N/D' }}</td>
                <td class="label">Stato</td>
                <td>{{ $service->status->name ?? 'N/D' }}</td>
            </tr>
            <tr>
                <td class="label">Riferimento</td>
                <td>{{ $service->reference_number ?? 'N/D' }}</td>
                <td class="label">Prezzo</td>
                <td>&euro; {{ number_format($service->service_price ?? 0, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- PICKUP & DROPOFF --}}
    <div class="section">
        <div class="section-title">PICKUP & DROPOFF</div>
        <table class="two-col">
            <tr>
                <td>
                    <div class="info-box">
                        <table>
                            <tr>
                                <td class="label">Data/Ora Pickup</td>
                                <td>{{ $service->pickup_datetime ? \Carbon\Carbon::parse($service->pickup_datetime)->format('d/m/Y H:i') : 'N/D' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Indirizzo Pickup</td>
                                <td>{{ $service->pickup_address ?? 'N/D' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Uscita Mezzo</td>
                                <td>{{ $service->vehicle_departure_datetime ? \Carbon\Carbon::parse($service->vehicle_departure_datetime)->format('d/m/Y H:i') : 'N/D' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <table>
                            <tr>
                                <td class="label">Data/Ora Dropoff</td>
                                <td>{{ $service->dropoff_datetime ? \Carbon\Carbon::parse($service->dropoff_datetime)->format('d/m/Y H:i') : 'N/D' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Indirizzo Dropoff</td>
                                <td>{{ $service->dropoff_address ?? 'N/D' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Rientro Mezzo</td>
                                <td>{{ $service->vehicle_return_datetime ? \Carbon\Carbon::parse($service->vehicle_return_datetime)->format('d/m/Y H:i') : 'N/D' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- FERMATE INTERMEDIE --}}
    @if($service->stops && $service->stops->count() > 0)
    <div class="section">
        <div class="section-title">FERMATE INTERMEDIE</div>
        <table class="stops-table">
            <thead>
                <tr>
                    <th>Fermata</th>
                    <th>Indirizzo</th>
                    <th>Ora Inizio</th>
                    <th>Ora Fine</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @foreach($service->stops as $stop)
                <tr>
                    <td>{{ $stop->name ?? '-' }}</td>
                    <td>{{ $stop->address ?? '-' }}</td>
                    <td>{{ $stop->start_time ?? '-' }}</td>
                    <td>{{ $stop->end_time ?? '-' }}</td>
                    <td>{{ $stop->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- VEICOLO --}}
    <div class="section">
        <div class="section-title">VEICOLO</div>
        <table>
            <tr>
                <td class="label">Targa</td>
                <td>{{ $service->vehicle->license_plate ?? 'N/D' }}</td>
                <td class="label">Marca/Modello</td>
                <td>{{ ($service->vehicle->brand ?? '') }} {{ ($service->vehicle->model ?? '') }}</td>
            </tr>
        </table>
    </div>

    {{-- PASSEGGERI --}}
    @if($service->passengers && $service->passengers->count() > 0)
    <div class="section">
        <div class="section-title">PASSEGGERI</div>
        <table class="stops-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Provenienza</th>
                </tr>
            </thead>
            <tbody>
                @foreach($service->passengers as $passenger)
                <tr>
                    <td>{{ $passenger->name ?? '-' }}</td>
                    <td>{{ $passenger->phone ?? '-' }}</td>
                    <td>{{ $passenger->email ?? '-' }}</td>
                    <td>{{ $passenger->origin ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- BAGAGLI --}}
    <div class="section">
        <div class="section-title">BAGAGLI & DOTAZIONI</div>
        <table>
            <tr>
                <td class="label">Bagagli Grandi</td>
                <td>{{ $service->large_bags ?? 0 }}</td>
                <td class="label">Bagagli Medi</td>
                <td>{{ $service->medium_bags ?? 0 }}</td>
                <td class="label">Bagagli Piccoli</td>
                <td>{{ $service->small_bags ?? 0 }}</td>
            </tr>
            <tr>
                <td class="label">Babyseat Ovetto</td>
                <td>{{ $service->babyseat_egg ?? 0 }}</td>
                <td class="label">Babyseat Standard</td>
                <td>{{ $service->babyseat_standard ?? 0 }}</td>
                <td class="label">Babyseat Booster</td>
                <td>{{ $service->babyseat_booster ?? 0 }}</td>
            </tr>
        </table>
    </div>

    {{-- DRESS CODE --}}
    @if($service->dress_code)
    <div class="section">
        <div class="section-title">DRESS CODE</div>
        <div class="highlight">{{ $service->dress_code }}</div>
    </div>
    @endif

    {{-- NOTE --}}
    @if($service->notes)
    <div class="section">
        <div class="section-title">NOTE</div>
        <div class="highlight">{{ $service->notes }}</div>
    </div>
    @endif

    <div class="footer">
        Documento generato automaticamente il {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>

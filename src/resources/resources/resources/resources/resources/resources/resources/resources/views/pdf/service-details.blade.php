<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        @@page {
            margin: 6mm 8mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }
        .half-section {
            height: 49%;
            overflow: hidden;
        }
        .separator {
            border-top: 2px dashed #999;
            margin: 4px 0;
        }

        /* ===== HEADER ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .stamp-cell {
            width: 32%;
            text-align: center;
            padding: 10px;
            height: 90px;
        }
        .stamp-cell img {
            max-width: 180px;
            max-height: 80px;
        }
        .title-area {
            width: 68%;
            vertical-align: top;
            padding: 6px 0;
        }
        .title-area h1 {
            font-size: 20px;
            font-weight: bold;
            color: #1a3764;
            text-align: right;
            margin: 0 0 8px 0;
        }
        .title-info {
            width: 100%;
            border-collapse: collapse;
        }
        .title-info td {
            padding: 5px 8px;
            font-size: 10px;
            border: 1px solid #ccc;
        }
        .title-info .lbl {
            font-weight: bold;
            color: #2a6cb6;
        }

        /* ===== SECTION HEADERS ===== */
        .section-header {
            background-color: #5b9bd5;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 8px;
            margin-top: 8px;
            margin-bottom: 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* ===== DATA TABLES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: middle;
            border: 1px solid #ccc;
        }
        .data-table .lbl {
            font-weight: bold;
            color: #2a6cb6;
            font-size: 9px;
            white-space: nowrap;
            width: 22%;
        }
        .data-table .val {
            font-size: 10px;
            color: #333;
            width: 28%;
        }

        /* ===== KM BOX ===== */
        .km-box {
            border: 1px solid #aaa;
            padding: 2px 12px;
            min-width: 90px;
            display: inline-block;
            min-height: 16px;
            background: #fafafa;
        }

        /* ===== SIGNATURE ===== */
        .signature-img {
            max-height: 45px;
            max-width: 160px;
        }
    </style>
</head>
<body>
    @for($copy = 0; $copy < 2; $copy++)
    <div class="half-section">

        {{-- HEADER: Timbro + Titolo --}}
        <table class="header-table">
            <tr>
                <td class="stamp-cell">
                    @if($stampUrl)
                        <img src="{{ $stampUrl }}" alt="Timbro">
                    @endif
                </td>
                <td class="title-area">
                    <h1>FOGLIO di SERVIZIO NCC</h1>
                    <table class="title-info">
                        <tr>
                            <td class="lbl">Nr. Servizio</td>
                            <td class="val">{{ $service->id }}</td>
                            <td class="lbl">Rif.</td>
                            <td class="val">{{ $service->reference_number ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl" colspan="2">DATA:</td>
                            <td class="val" colspan="2">{{ $dataServizio }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- VEICOLO e CONDUCENTE --}}
        <div class="section-header">VEICOLO e CONDUCENTE</div>
        <table class="data-table">
            <tr>
                <td class="lbl">NOME CONDUCENTE:</td>
                <td class="val">{{ $driverName }}</td>
                <td class="lbl">TARGA VEICOLO:</td>
                <td class="val">{{ $service->vehicle->license_plate ?? '' }}</td>
            </tr>
        </table>

        {{-- GENERALI --}}
        <div class="section-header">GENERALI</div>
        <table class="data-table">
            <tr>
                <td class="lbl">DATA INIZIO SERVIZIO:</td>
                <td class="val">{{ $dataPickup }}</td>
                <td class="lbl">DATA FINE SERVIZIO:</td>
                <td class="val">{{ $dataDropoff }}</td>
            </tr>
            <tr>
                <td class="lbl">ORARIO INIZIO SERVIZIO:</td>
                <td class="val">{{ $oraPickup }}</td>
                <td class="lbl">ORARIO FINE SERVIZIO:</td>
                <td class="val">{{ $oraDropoff }}</td>
            </tr>
            <tr>
                <td class="lbl">INDIRIZZO di PARTENZA:</td>
                <td class="val">{{ $service->pickup_address ?? '' }}</td>
                <td class="lbl">INDIRIZZO di ARRIVO:</td>
                <td class="val">{{ $service->dropoff_address ?? '' }}</td>
            </tr>
            <tr>
                <td class="lbl">SOSTE INTERMEDIE:</td>
                <td class="val" colspan="3">
                    @if($service->stops && $service->stops->count() > 0)
                        @foreach($service->stops as $stop)
                            {{ $stop->name ?? $stop->address ?? '' }}
                            @if(!$loop->last), @endif
                        @endforeach
                    @endif
                    @if($service->activities && $service->activities->count() > 0)
                        @if($service->stops && $service->stops->count() > 0)
                            —
                        @endif
                        @foreach($service->activities as $activity)
                            {{ $activity->name ?? '' }}
                            @if($activity->start_time)
                                ({{ \Carbon\Carbon::parse($activity->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($activity->end_time)->format('H:i') }})
                            @endif
                            @if(!$loop->last), @endif
                        @endforeach
                    @endif
                </td>
            </tr>
            <tr>
                <td class="lbl">Km di PARTENZA:</td>
                <td class="val"><span class="km-box">{{ $kmVeicolo }}</span></td>
                <td class="lbl">Km di ARRIVO:</td>
                <td class="val"><span class="km-box"></span></td>
            </tr>
        </table>

        {{-- PASSEGGERI --}}
        <div class="section-header">PASSEGGERI</div>
        <table class="data-table">
            <tr>
                <td class="lbl">NOME PASSEGGERO:</td>
                <td class="val">{{ $nomePasseggero }}</td>
                <td class="lbl">CONTATTO:</td>
                <td class="val">{{ $telefonoPasseggero }}</td>
            </tr>
        </table>

        {{-- FIRMA --}}
        <table class="data-table" style="margin-top: 8px;">
            <tr>
                <td class="lbl" style="width: 28%;">FIRMA del CONDUCENTE:</td>
                <td class="val" style="text-align: center; width: 72%; height: 38px;">
                    @if($firmaDriverUrl)
                        <img src="{{ $firmaDriverUrl }}" alt="Firma" class="signature-img">
                    @endif
                </td>
            </tr>
        </table>

    </div>

    @if($copy === 0)
        <div class="separator"></div>
    @endif
    @endfor
</body>
</html>

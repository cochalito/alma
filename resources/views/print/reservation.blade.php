<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Reserva #{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            padding: 20px;
            background: #fff;
            max-width: 800px;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        
        .header-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            margin-top: 10px;
        }
        
        .info-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .table-box {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table-box th {
            border-bottom: 1px solid #000;
            background-color: #f5f5f5;
            padding: 6px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .table-box td {
            padding: 6px 10px;
            vertical-align: top;
        }

        .border-right {
            border-right: 1px solid #000;
        }

        .grid-2 {
            display: flex;
            gap: 15px;
        }

        .grid-2 > div {
            width: 50%;
        }

        .footer-note {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            font-size: 11px;
            color: #333;
        }

        .label-cell {
            width: 130px;
            display: inline-block;
        }

        .product-row td {
            padding: 4px 10px;
        }

        /* Ocultar elementos innecesarios al imprimir */
        @media print {
            body {
                width: 100%;
                padding: 10px;
                margin: 0;
                max-width: none;
            }
            .table-box th {
                background-color: #e0e0e0 !important;
                -webkit-print-color-adjust: exact;
            }
            @page {
                size: A4 portrait;
                margin: 1cm;
            }
        }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 300)">

    @php
        $statusText = [
            '1' => 'CONFIRMADA',
            '2' => 'CHECKIN',
            '3' => 'CHECKOUT',
            '4' => 'CANCELADA',
        ][$reservation->status] ?? 'DESCONOCIDO';
        
        $checkIn = \Carbon\Carbon::parse($reservation->check_in);
        $checkOut = \Carbon\Carbon::parse($reservation->check_out);
        $noches = $checkIn->diffInDays($checkOut);
        if ($noches == 0) $noches = 1;

        $tarifa = $noches > 0 ? $reservation->total_stay_cost / $noches : $reservation->total_stay_cost;
    @endphp
    
    <div class="header-title uppercase">
        NOTA DE RESERVA
    </div>
    
    <div class="info-header uppercase">
        <span><span class="bold">Nro. Reserva :</span> {{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</span>
        <span><span class="bold">Estado:</span> {{ $statusText }}</span>
    </div>

    <!-- DATOS DE ESTADIA -->
    <table class="table-box uppercase">
        <tr>
            <th colspan="2">DATOS DE ESTADIA</th>
        </tr>
        <tr>
            <td class="border-right" style="width: 50%;">
                <div class="mb-2"><span class="label-cell">Departamento</span>: {{ empty($reservation->departament->code) ? '' : $reservation->departament->code }}</div>
                <div class="mb-2"><span class="label-cell">Huésped</span>: {{ empty($reservation->customer) ? '' : ($reservation->customer->firstname . ' ' . $reservation->customer->lastname) }}</div>
                <div class="mb-2"><span class="label-cell">Fecha Llegada</span>: {{ $checkIn->format('d/m/Y H:i') }}</div>
                <div class="mb-2"><span class="label-cell">Fecha Salida</span>: {{ $checkOut->format('d/m/Y H:i') }}</div>
                
                <div style="margin-top: 15px;">
                    <div class="mb-2">Solicitudes : {{ $reservation->requests ?: '' }}</div>
                    <div>Comentarios &nbsp;: <span style="white-space: pre-wrap;">{{ $reservation->comments ?: '' }}</span></div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="mb-2">CHECK IN: {{ $reservation->employee ? $reservation->employee->name : '' }}</div>
                @if($reservation->status === '3')
                <div class="mb-2">CHECK OUT</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="grid-2">
        <!-- MONTO DE ESTADIA -->
        <div>
            <table class="table-box uppercase">
                <tr>
                    <th colspan="2">MONTO DE ESTADIA</th>
                </tr>
                <tr>
                    <td>Noches :</td>
                    <td class="text-right">{{ $noches }}</td>
                </tr>
                <tr>
                    <td>Tarifa por Noche :</td>
                    <td class="text-right">{{ number_format($tarifa, 2) }} Bs.</td>
                </tr>
                <tr>
                    <td class="bold">TOTAL ESTADIA :</td>
                    <td class="text-right bold">{{ number_format($reservation->total_stay_cost, 2) }} Bs.</td>
                </tr>
            </table>
        </div>

        <!-- MONTO DE SERVICIOS -->
        <div>
            <table class="table-box uppercase">
                <tr>
                    <th colspan="2">MONTO DE SERVICIOS</th>
                </tr>
                @if($reservation->products && $reservation->products->count() > 0)
                    @foreach($reservation->products as $product)
                        <tr class="product-row">
                            <td>{{ $product->pivot->quantity }} {{ $product->name }}</td>
                            <td class="text-right">{{ number_format($product->pivot->subtotal, 2) }} Bs.</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="bold" style="padding-top: 15px;">TOTAL SERVICIOS :</td>
                        <td class="text-right bold" style="padding-top: 15px;">{{ number_format($reservation->total_extra_cost, 2) }} Bs.</td>
                    </tr>
                @else
                    <tr class="product-row">
                        <td colspan="2" class="text-center" style="font-size: 11px; padding: 10px; text-transform: none; font-style: italic;">
                            (Sin servicios adicionales)
                        </td>
                    </tr>
                    <tr>
                        <td class="bold" style="padding-top: 15px;">TOTAL SERVICIOS :</td>
                        <td class="text-right bold" style="padding-top: 15px;">0.00 Bs.</td>
                    </tr>
                @endif
                <tr>
                    <td class="bold" style="font-size: 14px; padding-top: 15px;">TOTAL COBRO :</td>
                    <td class="text-right bold" style="font-size: 14px; padding-top: 15px;">{{ number_format($reservation->total_stay_cost + $reservation->total_extra_cost, 2) }} Bs.</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-note">
        <div>
            Esta impresión fue realizada el {{ \Carbon\Carbon::now()->timezone('America/La_Paz')->format('d/m/Y') }} a las {{ \Carbon\Carbon::now()->timezone('America/La_Paz')->format('H:i') }}
        </div>
        <div class="bold">
            www.almudenahotel.com
        </div>
    </div>

</body>
</html>

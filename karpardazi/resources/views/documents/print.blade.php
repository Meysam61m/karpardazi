<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>سند هزینه شعبه {{ $document->branch?->name }} {{ $document->document_number ? fa($document->document_number) : '' }}</title>
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('{{ asset('vendor/vazirmatn/Vazirmatn.woff2') }}') format('woff2');
            font-weight: 100 900;
        }
        @page { size: A4; margin: 7mm 8mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
        }
        table { border-collapse: collapse; width: 100%; }
        .center { text-align: center; }
        .fw { font-weight: 700; }

        /* ─── سربرگ ─── */
        table.hdr { table-layout: fixed; margin-bottom: 1.5mm; }
        table.hdr td { border: 0.6pt solid #000; padding: 1mm 1.5mm; vertical-align: top; }
        td.c-logo { width: 17mm; text-align: center; vertical-align: middle; }
        td.c-logo img { max-width: 14mm; max-height: 15mm; }
        td.c-org { width: 62mm; font-size: 8pt; line-height: 1.5; }
        td.c-title { text-align: center; vertical-align: middle; }
        td.c-title .doc-title { font-size: 12.5pt; font-weight: 800; }
        td.c-title .branch-name { font-size: 10pt; font-weight: 700; margin-top: 1mm; }
        td.c-num { width: 56mm; padding: 0 !important; }
        table.meta { table-layout: fixed; height: 100%; }
        table.meta td { border: 0; border-bottom: 0.6pt solid #000; padding: 1mm 1.5mm; font-size: 8pt; vertical-align: middle; }
        table.meta tr:last-child td { border-bottom: 0; }
        table.meta td.lbl { width: 20mm; font-weight: 700; border-left: 0.6pt solid #000; }
        .num-line { display: flex; align-items: stretch; }
        .num-line .v { flex: 1; font-weight: 700; letter-spacing: .5px; }
        .num-line .code {
            border-right: 0.6pt solid #000;
            min-width: 9mm; text-align: center; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }

        /* ─── جدول اقلام ─── */
        table.items { table-layout: fixed; }
        table.items th, table.items td { border: 0.6pt solid #000; padding: 0.8mm 1mm; }
        table.items thead th { text-align: center; font-size: 8.5pt; }
        table.items thead { display: table-header-group; }
        col.w-row { width: 8mm; }
        col.w-date { width: 19mm; }
        col.w-ins { width: 25mm; }
        col.w-tax { width: 25mm; }
        col.w-amt { width: 29mm; }
        td.money, th.money { text-align: center; }
        td.rownum { text-align: center; }
        td.date { text-align: center; white-space: nowrap; }
        td.desc { word-wrap: break-word; }
        tr.totals td { font-weight: 700; font-size: 9pt; }
        tr.totals td.statement { font-weight: 400; font-size: 8pt; }
        .statement-inner { display: flex; justify-content: space-between; align-items: center; gap: 2mm; }
        .statement-inner .tlabel { font-weight: 800; white-space: nowrap; }

        /* ─── متن درخواست و امضاء کارپرداز ─── */
        .request { margin-top: 2.5mm; }
        .request .text { line-height: 2; }

        /* ─── کادرها ─── */
        .box { border: 0.7pt solid #000; margin-top: 2mm; padding: 1.5mm 2mm 2mm; break-inside: avoid; }
        .fill { display: inline-block; border-bottom: 0.6pt dotted #000; min-width: 22mm; }
        .fill.sm { min-width: 14mm; }
        .fill.lg { min-width: 45mm; }
        .dline { display: flex; align-items: baseline; gap: 1.5mm; }
        .dots-fill { flex: 1; border-bottom: 0.6pt dotted #000; height: 1em; }
        table.sig { margin-top: 2mm; }
        table.sig td { border: 0.6pt solid #000; text-align: center; padding: 1mm; font-size: 8.5pt; height: 13mm; vertical-align: top; }
        .no-break { break-inside: avoid; }

        /* ─── ابزار روی صفحه (چاپ نمی‌شود) ─── */
        .toolbar { position: fixed; top: 10px; left: 10px; z-index: 99; display: flex; gap: 8px; }
        .toolbar button, .toolbar a {
            font-family: inherit; font-size: 13px; padding: 8px 16px;
            border: 0; border-radius: 6px; cursor: pointer; text-decoration: none;
            background: #0d6efd; color: #fff;
        }
        .toolbar a { background: #6c757d; }
        @media print { .toolbar { display: none; } }

        .watermark {
            position: fixed; inset: 0; display: flex; align-items: center; justify-content: center;
            z-index: 50; pointer-events: none;
        }
        .watermark span { font-size: 90pt; font-weight: 900; color: rgba(200, 0, 0, 0.10); transform: rotate(-30deg); }
        @media screen { body { width: 210mm; margin: 0 auto; padding: 10mm; box-shadow: 0 0 8px #bbb; } html { background: #e9ecef; } }
    </style>
</head>
<body>
@if($document->isDraft())
    <div class="watermark"><span>پیش‌نویس</span></div>
@endif

<div class="toolbar">
    <button onclick="window.print()">چاپ</button>
    <a href="{{ route('documents.show', $document) }}">بازگشت</a>
</div>

{{-- سربرگ --}}
<table class="hdr">
    <tr>
        <td class="c-logo"><img src="{{ asset('images/logo.png') }}" alt=""></td>
        <td class="c-org">
            <div class="fw">{{ setting('org_name', 'شرکت انبارهای عمومی و خدمات گمرکی ایران') }}</div>
            <div class="fw">{{ setting('complex_name', 'مجتمع خراسان') }}</div>
            <div>ش ملی: {{ fa(setting('national_id', '')) }}</div>
            <div>کد اقتصادی: {{ fa(setting('economic_code', '')) }}</div>
        </td>
        <td class="c-title">
            <div class="doc-title">سند هزینه شعبه</div>
            <div class="branch-name">{{ $document->branch?->name }}</div>
        </td>
        <td class="c-num">
            <table class="meta">
                <tr>
                    <td class="lbl">تاریخ:</td>
                    <td class="fw">{{ jdate_fa($document->document_date) }}</td>
                </tr>
                <tr>
                    <td class="lbl">مبلغ واریز تنخواه:</td>
                    <td class="fw">{{ fa_money($document->tankhah_deposit_amount) }} ریال</td>
                </tr>
                <tr>
                    <td class="lbl">تاریخ واریز تنخواه:</td>
                    <td class="fw">{{ jdate_fa($document->tankhah_deposit_date) }}</td>
                </tr>
                <tr>
                    <td class="lbl">شماره سند:</td>
                    <td>
                        <div class="num-line">
                            <span class="v">{{ $document->document_number ? fa($document->document_number) : '—' }}</span>
                            <span class="code">{{ fa($document->branch?->code) }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">تاریخ بازنگری:</td>
                    <td>
                        <div class="num-line">
                            <span class="v">{{ fa(setting('form_revision_date', '')) }}</span>
                            <span class="code" style="min-width: 24mm; font-weight: 400;">{{ setting('form_code', '') }}</span>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- جدول اقلام --}}
<table class="items">
    <colgroup>
        <col class="w-row"><col class="w-date"><col><col class="w-ins"><col class="w-tax"><col class="w-amt">
    </colgroup>
    <thead>
    <tr>
        <th rowspan="2">ردیف</th>
        <th rowspan="2">تاریخ</th>
        <th rowspan="2">شرح هزینه</th>
        <th colspan="3">مبلغ به (ریال)</th>
    </tr>
    <tr>
        <th>بیمه</th>
        <th>مالیات</th>
        <th>مبلغ</th>
    </tr>
    </thead>
    <tbody>
    @foreach($document->items as $item)
        <tr>
            <td class="rownum">{{ fa($item->row_number) }}</td>
            <td class="date">{{ jdate_fa($item->expense_date) }}</td>
            <td class="desc">{{ $item->description }}</td>
            <td class="money">{{ fa_money($item->insurance) }}</td>
            <td class="money">{{ fa_money($item->tax) }}</td>
            <td class="money">{{ fa_money($item->amount) }}</td>
        </tr>
    @endforeach
    <tr class="totals no-break">
        <td colspan="3" class="statement">
            <div class="statement-inner">
                <span>کلیه موارد فوق با رعایت صرفه و صلاح شرکت و با کمترین بهای ممکن و بهترین کیفیت انجام گرفت.</span>
                <span class="tlabel">جمع کل</span>
            </div>
        </td>
        <td class="money">{{ fa_money($document->total_insurance) }}</td>
        <td class="money">{{ fa_money($document->total_tax) }}</td>
        <td class="money">{{ fa_money($document->total_amount) }}</td>
    </tr>
    </tbody>
</table>

{{-- درخواست صدور چک تنخواه + امضاء کارپرداز --}}
<div class="request no-break">
    <div class="text">احتراماً خواهشمند است در خصوص صدور چک تنخواه شماره <span class="fill"></span> در وجه اینجانب <span class="fill" style="min-width: 35mm;"></span> دستور اقدام مقتضی صادر فرمایند.</div>
    <div style="text-align: left; margin-top: 1mm;">امضاء کارپرداز</div>
</div>

{{-- بررسی امور مالی --}}
<div class="box">
    <div class="fw">بررسی امور مالی</div>
    <div style="margin-top: 1mm;">
        اسناد و مدارک مربوطه شامل تعداد <span class="fill sm"></span> برگ به مبلغ کل <span class="fill lg"></span> ریال بررسی گردید.
    </div>
    <div class="dline" style="margin-top: 1mm;"><span>شرح بررسی</span><span class="dots-fill"></span></div>
    <table class="sig">
        <tr>
            <td style="width: 33%;">کارشناس حسابداری<br><br>تاریخ:</td>
            <td style="width: 34%;">مسئول امور کارکنان<br><br>تاریخ:</td>
            <td style="width: 33%;">مسئول حسابداری<br><br>تاریخ:</td>
        </tr>
    </table>
</div>

{{-- اداری مالی --}}
<div class="box">
    <div class="dline"><span class="fw">اداری مالی</span><span class="dots-fill"></span></div>
    <div class="center" style="margin-top: 2mm;">رئیس مالی و اداری<br><br>تاریخ:</div>
</div>

{{-- دستور صدور چک --}}
<div class="box">
    <div><span class="fw">دستور صدور چک</span>&nbsp;&nbsp; امور مالی با پرداخت مبلغ <span class="fill lg"></span> ریال موافقت می‌شود.</div>
    <div style="display: flex; gap: 25mm; margin-top: 2mm;"><span>مدیریت</span><span>تاریخ:</span></div>
</div>

{{-- صدور و تحویل چک --}}
<div class="box">
    <div style="line-height: 2.2;">
        <span class="fw">صدور و تحویل چک</span>&nbsp;&nbsp; چک شماره <span class="fill"></span> به مبلغ <span class="fill lg"></span> ریال بتاریخ <span class="fill sm"></span>/<span class="fill sm"></span>/<span class="fill sm"></span>
        جاری <span class="fill"></span> بانک <span class="fill"></span>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: baseline;">
        <span>در وجه <span class="fill" style="min-width: 60mm;"></span> صادر و تحویل گردید.</span>
        <span>دریافت کننده</span>
    </div>
    <div style="display: flex; justify-content: space-between; margin-top: 1mm;">
        <span>امور مالی &nbsp;&nbsp;&nbsp; تاریخ:</span>
        <span>تاریخ:</span>
    </div>
</div>

<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>

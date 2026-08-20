<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير {{ $copy->year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #20252b; font-size: 12px; }
        h1 { color: #451952; margin-bottom: 4px; }
        h2 { color: #451952; border-right: 3px solid #451952; padding-right: 8px; margin-top: 24px; }
        .meta { color: #68737d; margin-bottom: 20px; }
        .stats { width: 100%; border-collapse: collapse; margin: 12px 0; }
        .stats td { border: 1px solid #e3e7eb; padding: 9px; width: 25%; }
        .label { color: #68737d; display: block; font-size: 10px; }
        .value { color: #451952; font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e3e7eb; padding: 6px; text-align: right; }
        th { background: #f5f6f8; }
    </style>
</head>
<body>
    <h1>تقرير {{ $copy->label ?? ('نسخة ' . $copy->year) }}</h1>
    <div class="meta">الفترة: {{ $copy->start_date }} إلى {{ $copy->end_date }} | تاريخ الإنشاء: {{ $generatedAt }}</div>

    <table class="stats">
        <tr>
            <td><span class="label">الزوار</span><span class="value">{{ number_format($visitorStats['total']) }}</span></td>
            <td><span class="label">الإيرادات</span><span class="value">{{ number_format($bookingStats['revenue'], 2) }}</span></td>
            <td><span class="label">الأجنحة المحجوزة</span><span class="value">{{ $bookingStats['booked'] }}</span></td>
            <td><span class="label">الأجنحة المتاحة</span><span class="value">{{ $bookingStats['available'] }}</span></td>
        </tr>
    </table>

    <h2>الزوار حسب اليوم</h2>
    <table>
        <tr><th>اليوم</th><th>العدد</th></tr>
        @foreach($visitorStats['byDay'] as $item)
            <tr><td>{{ $item['day'] }}</td><td>{{ $item['count'] }}</td></tr>
        @endforeach
    </table>

    <h2>الزوار حسب الاهتمام</h2>
    <table>
        <tr><th>الاهتمام</th><th>النسبة</th></tr>
        @foreach($visitorStats['byInterest'] as $item)
            <tr><td>{{ $item['name'] }}</td><td>{{ $item['value'] }}%</td></tr>
        @endforeach
    </table>

    <h2>الإيرادات الشهرية</h2>
    <table>
        <tr><th>الشهر</th><th>الإيراد</th><th>المستهدف</th></tr>
        @foreach($revenueTimeline as $item)
            <tr><td>{{ $item['month'] }}</td><td>{{ number_format($item['revenue'], 2) }}</td><td>{{ number_format($item['target'], 2) }}</td></tr>
        @endforeach
    </table>
</body>
</html>

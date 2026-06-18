<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; text-align: right; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>

<h1>{{ $report->title }}</h1>
<p>{{ $report->description }}</p>

<h3>الفترة: {{ $report->period }}</h3>
<h3>الجناح: {{ $report->booth_name }}</h3>
<h3>المعرض: {{ $report->exhibition_name }}</h3>

<h2>{{ $report->main_label }}: {{ $report->main_value }}</h2>
<h3>نسبة التغير: {{ $report->trend }}%</h3>

@if($report->sparkline_data)
    <h3>البيانات اليومية:</h3>
    <table>
        <tr>
            <th>اليوم</th>
            <th>القيمة</th>
        </tr>
        @foreach($report->sparkline_data as $i => $value)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </table>
@endif

</body>
</html>

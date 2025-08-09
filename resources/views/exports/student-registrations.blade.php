<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Registrations Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #2563eb;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Registrations Report</h1>
        <p>Grisiti Academy - Agricultural Training Program</p>
        <p>Generated on: {{ $exportDate }}</p>
    </div>

    <div class="summary">
        <p><strong>Total Records:</strong> {{ $totalRecords }}</p>
        <p><strong>Report Type:</strong> Student Registration Data</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                @foreach($fields as $field)
                <td>{{ $record[$field] }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the Grisiti Academy Management System.</p>
        <p>For questions or support, please contact the administration team.</p>
    </div>
</body>
</html>

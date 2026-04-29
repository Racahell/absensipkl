<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        h2 { margin: 0 0 8px; }
        p { margin: 0 0 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #999; padding: 6px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Attendance Report</h2>
    <p>Generated: {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>Label</th>
                <th>Present</th>
                <th>Leave</th>
                <th>Sick</th>
                <th>Absent</th>
                <th>Pending</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['izin'] }}</td>
                    <td>{{ $row['sakit'] }}</td>
                    <td>{{ $row['alpha'] }}</td>
                    <td>{{ $row['pending'] }}</td>
                    <td>{{ $row['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

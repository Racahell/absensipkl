@extends('layouts.app', ['title' => 'Laporan Diagram'])

@section('content')
    <div class="card">
        <h3 style="margin-top:0;">Laporan Kehadiran</h3>
        <label for="chartType">Tipe Diagram</label>
        <select id="chartType" style="margin:6px 0 12px; padding:8px; border:1px solid #fdba74; border-radius:8px;">
            <option value="bar">Batang</option>
            <option value="line">Line</option>
            <option value="pie">Pie</option>
        </select>

        <div style="display:grid; grid-template-columns:1fr; gap:18px;">
            <div>
                <h4>Mingguan</h4>
                <canvas id="weeklyChart" height="110"></canvas>
            </div>
            <div>
                <h4>Bulanan</h4>
                <canvas id="monthlyChart" height="110"></canvas>
            </div>
            <div>
                <h4>Tahunan</h4>
                <canvas id="yearlyChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const weekly = { labels: @json($weeklyLabels), data: @json($weeklyTotals) };
        const monthly = { labels: @json($monthlyLabels), data: @json($monthlyTotals) };
        const yearly = { labels: @json($yearlyLabels), data: @json($yearlyTotals) };

        let charts = [];
        function draw(type) {
            charts.forEach((chart) => chart.destroy());
            charts = [];
            const config = (dataset) => ({
                type,
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        label: 'Total',
                        data: dataset.data,
                        borderColor: '#ea580c',
                        backgroundColor: 'rgba(234, 88, 12, 0.35)',
                        borderWidth: 2
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            charts.push(new Chart(document.getElementById('weeklyChart'), config(weekly)));
            charts.push(new Chart(document.getElementById('monthlyChart'), config(monthly)));
            charts.push(new Chart(document.getElementById('yearlyChart'), config(yearly)));
        }

        const typeSelect = document.getElementById('chartType');
        typeSelect.addEventListener('change', () => draw(typeSelect.value));
        draw(typeSelect.value);
    </script>
@endsection

@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .report-filter-actions {
            align-items: flex-end;
        }

        .report-filter-actions select,
        .report-filter-actions .btn {
            height: 44px;
        }

        .report-filter-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            white-space: nowrap;
            min-width: 112px;
        }
    </style>

    <div class="card mb-14">
        <h3 class="mt-0 text-primary">Laporan Kehadiran</h3>
        <form method="GET" action="" class="flex gap-8 wrap report-filter-actions">
            @if (! empty($isDepartmentScoped))
                <div>
                    <label for="jurusan">Jurusan</label>
                    <select id="jurusan" name="jurusan" required {{ !empty($isKajur) ? 'disabled' : '' }}>
                        <option value="">Pilih Jurusan</option>
                        @foreach (($departmentOptions ?? []) as $department)
                            <option value="{{ $department }}" {{ ($selectedDepartment ?? '') === $department ? 'selected' : '' }}>
                                {{ $department }}
                            </option>
                        @endforeach
                    </select>
                    @if (! empty($isKajur))
                        <input type="hidden" name="jurusan" value="{{ $selectedDepartment ?? '' }}">
                    @endif
                </div>
                <div>
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" {{ empty($selectedDepartment) ? 'disabled' : '' }}>
                        <option value="">Semua Kelas</option>
                        @foreach (($classOptions ?? []) as $className)
                            <option value="{{ $className }}" {{ ($selectedClass ?? '') === $className ? 'selected' : '' }}>
                                {{ $className }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <input type="hidden" id="period" name="period" value="{{ $period }}">
            <div>
                <label for="chart_type">Tipe Diagram</label>
                <select id="chart_type" name="chart_type">
                    <option value="bar" {{ $chartType === 'bar' ? 'selected' : '' }}>Batang</option>
                    <option value="line" {{ $chartType === 'line' ? 'selected' : '' }}>Line</option>
                    <option value="pie" {{ $chartType === 'pie' ? 'selected' : '' }}>Pie</option>
                </select>
            </div>
            <a class="btn btn-ghost" href="{{ route('reports.export.excel', ['period' => $period, 'jurusan' => $selectedDepartment ?? null, 'kelas' => $selectedClass ?? null]) }}">Export Excel</a>
            <a class="btn btn-ghost" href="{{ route('reports.export.pdf', ['period' => $period, 'jurusan' => $selectedDepartment ?? null, 'kelas' => $selectedClass ?? null]) }}">Export PDF</a>
            <a class="btn btn-ghost" href="{{ route('reports.print', ['period' => $period, 'jurusan' => $selectedDepartment ?? null, 'kelas' => $selectedClass ?? null]) }}" target="_blank">Print</a>
        </form>
        @if (! empty($isKesiswaan) && empty($selectedDepartment))
            <div class="alert alert-error mt-10">Pilih jurusan terlebih dahulu untuk menampilkan data.</div>
        @endif
    </div>

    <div class="card">
        <canvas id="attendanceChart" height="120"></canvas>
    </div>
    <div class="card mt-10">
        <h4 class="mt-0 text-primary">Ringkasan Data</h4>
        @php
            $totalHadir = collect($hadirData ?? [])->sum();
            $totalIzin = collect($izinData ?? [])->sum();
            $totalSakit = collect($sakitData ?? [])->sum();
            $totalAlpha = collect($alphaData ?? [])->sum();
            $totalPending = collect($pendingData ?? [])->sum();
        @endphp
        <div class="table-wrap">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Hadir</th>
                        <th>Izin</th>
                        <th>Sakit</th>
                        <th>Absent</th>
                        <th>Awaiting</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $totalHadir }}</td>
                        <td>{{ $totalIzin }}</td>
                        <td>{{ $totalSakit }}</td>
                        <td>{{ $totalAlpha }}</td>
                        <td>{{ $totalPending }}</td>
                        <td>{{ $totalHadir + $totalIzin + $totalSakit + $totalAlpha + $totalPending }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($labels);
        const hadirData = @json($hadirData);
        const izinData = @json($izinData);
        const sakitData = @json($sakitData);
        const alphaData = @json($alphaData);
        const pendingData = @json($pendingData);
        const chartType = @json($chartType);

        function getChartLabels() {
            const uiLang = window.localStorage.getItem('ui_lang') || 'id';
            return uiLang === 'en'
                ? { present: 'Present', leave: 'Leave', sick: 'Sick', absent: 'Absent', pending: 'Pending', total: 'Total' }
                : { present: 'Hadir', leave: 'Izin', sick: 'Sakit', absent: 'Alpha', pending: 'Menunggu', total: 'Total' };
        }

        function buildDatasets(chartLabels) {
            return [
                { label: chartLabels.present, data: hadirData, backgroundColor: '#16a34a', borderColor: '#16a34a' },
                { label: chartLabels.leave, data: izinData, backgroundColor: '#0284c7', borderColor: '#0284c7' },
                { label: chartLabels.sick, data: sakitData, backgroundColor: '#ca8a04', borderColor: '#ca8a04' },
                { label: chartLabels.absent, data: alphaData, backgroundColor: '#dc2626', borderColor: '#dc2626' },
                { label: chartLabels.pending, data: pendingData, backgroundColor: '#9333ea', borderColor: '#9333ea' },
            ];
        }

        const attendanceCanvas = document.getElementById('attendanceChart');
        let attendanceChart = null;

        function renderAttendanceChart() {
            if (!attendanceCanvas) return;
            const chartLabels = getChartLabels();
            const datasets = buildDatasets(chartLabels);
            if (attendanceChart) {
                attendanceChart.destroy();
            }
            attendanceChart = new Chart(attendanceCanvas, {
                type: chartType,
                data: {
                    labels,
                    datasets: chartType === 'pie'
                        ? [{
                            label: chartLabels.total,
                            data: [
                                hadirData.reduce((a,b)=>a+b,0),
                                izinData.reduce((a,b)=>a+b,0),
                                sakitData.reduce((a,b)=>a+b,0),
                                alphaData.reduce((a,b)=>a+b,0),
                                pendingData.reduce((a,b)=>a+b,0),
                            ],
                            backgroundColor: ['#16a34a','#0284c7','#ca8a04','#dc2626','#9333ea'],
                        }]
                        : datasets,
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true },
                    },
                },
            });
        }

        renderAttendanceChart();
        window.addEventListener('ui-language-changed', renderAttendanceChart);

        (function () {
            const form = document.querySelector('.report-filter-actions');
            if (!form) return;

            const periodSelect = document.getElementById('period');
            const chartTypeSelect = document.getElementById('chart_type');
            const jurusanSelect = document.getElementById('jurusan');
            const kelasSelect = document.getElementById('kelas');

            function submitAuto() {
                form.submit();
            }

            if (periodSelect) {
                periodSelect.addEventListener('change', submitAuto);
            }
            if (chartTypeSelect) {
                chartTypeSelect.addEventListener('change', submitAuto);
            }
            if (jurusanSelect) {
                jurusanSelect.addEventListener('change', function () {
                    if (kelasSelect) {
                        kelasSelect.value = '';
                    }
                    submitAuto();
                });
            }
            if (kelasSelect) {
                kelasSelect.addEventListener('change', submitAuto);
            }
        })();
    </script>
@endsection

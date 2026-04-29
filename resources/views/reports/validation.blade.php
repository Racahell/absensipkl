@php
    $instrukturLike = in_array($role, ['instruktur', 'superadmin'], true);
    $formatStatus = static function (?string $status): string {
        $raw = strtolower(trim((string) $status));
        return match (true) {
            $raw === '', $raw === '-' => '-',
            $raw === 'pending_pembimbing' => 'pending pembimbing sekolah',
            $raw === 'pending_instruktur' => 'approved pembimbing sekolah',
            $raw === 'pending_kajur' => 'approved instruktur',
            $raw === 'hadir',
            $raw === 'approved_final',
            str_starts_with($raw, 'approved'),
            str_starts_with($raw, 'reviewed_') => 'approved',
            default => str_replace('_', ' ', $raw),
        };
    };
@endphp

@extends('layouts.app', ['title' => $title])

@section('content')
    <style>
        .validation-table-wrap {
            border: 1px solid #fed7aa;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .validation-table {
            width: 100%;
            border-collapse: collapse;
        }

        .validation-table th,
        .validation-table td {
            border-bottom: 1px solid #ffedd5;
            padding: 10px 12px;
            vertical-align: middle;
        }

        .validation-table th {
            background: #fff7ed;
            color: #9a3412;
            text-align: left;
            font-weight: 700;
            white-space: nowrap;
        }

        .validation-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .validation-table .name-cell strong {
            color: #7c2d12;
        }

        .validation-table .name-cell small {
            color: #6b7280;
        }

        .detail-btn {
            border: 1px solid #fdba74;
            background: #fff;
            color: #9a3412;
            border-radius: 10px;
            padding: 8px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .detail-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 14px;
        }

        .detail-modal.is-open {
            display: flex;
        }

        .detail-card {
            width: min(980px, 100%);
            max-height: 92vh;
            overflow: auto;
            border: 1px solid #fdba74;
            border-radius: 14px;
            background: #fff;
        }

        .detail-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid #fed7aa;
            position: sticky;
            top: 0;
            background: #fff7ed;
            z-index: 2;
        }

        .detail-body {
            padding: 14px;
            display: grid;
            gap: 12px;
        }

        .detail-info p {
            margin: 0 0 8px;
        }

        .validation-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            align-items: stretch;
        }

        .validation-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 100%;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            padding: 10px;
            background: #fffaf5;
        }

        .validation-form textarea {
            margin-bottom: 0 !important;
        }

        .validation-form .action-btn {
            align-self: flex-start;
            margin-top: auto;
            min-width: 110px;
        }

        @media (max-width: 900px) {
            .validation-table {
                min-width: 720px;
            }

            .validation-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if (session('success'))
        <div class="card alert mb-16">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card alert alert-error mb-16">
            <strong>Terjadi kesalahan:</strong>
            <ul class="mt-10">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <h3 class="mt-0">Daftar Laporan Harian Menunggu Review Anda</h3>
        @if ($items->isEmpty())
            <p>Belum ada laporan harian yang menunggu review Anda.</p>
        @else
            <div class="validation-table-wrap">
                <table class="validation-table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>NIS</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td class="name-cell"><strong>{{ $item->attendance?->user?->name ?? '-' }}</strong></td>
                                <td>{{ $item->attendance?->user?->nis ?? '-' }}</td>
                                <td>{{ optional($item->attendance?->attendance_date)->format('Y-m-d') }}</td>
                                <td>{{ $formatStatus($item->review_status) }}</td>
                                <td>
                                    <button type="button" class="detail-btn" data-open-modal="report-detail-{{ $item->id }}">Detail</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @foreach ($items as $item)
                    <div class="detail-modal" id="report-detail-{{ $item->id }}" aria-hidden="true">
                        <div class="detail-card">
                            <div class="detail-head">
                                <h4 style="margin:0; color:#9a3412;">Detail Laporan</h4>
                                <button type="button" class="detail-btn" data-close-modal="report-detail-{{ $item->id }}">Close</button>
                            </div>
                            <div class="detail-body">
                                <div class="detail-info">
                                    <p><strong>{{ $item->attendance?->user?->name ?? '-' }}</strong> (NIS: {{ $item->attendance?->user?->nis ?? '-' }})</p>
                                    <p>Tanggal: {{ optional($item->attendance?->attendance_date)->format('Y-m-d') }}</p>
                                    <p>Status review: <strong>{{ $formatStatus($item->review_status) }}</strong></p>
                                    <p>Rencana: {{ $item->plan_work }}</p>
                                    <p>Realisasi: {{ $item->actual_work }}</p>
                                </div>

                                <div class="validation-actions">
                                    <form action="{{ route('validasi.laporan.approve', $item) }}" method="POST" class="validation-form">
                                        @csrf
                                        <textarea name="note" rows="2" placeholder="{{ $instrukturLike ? 'Catatan review instruktur (opsional)' : 'Catatan validasi pembimbing (opsional)' }}"></textarea>
                                        <button type="submit" class="btn-success action-btn">
                                            {{ $instrukturLike ? 'Simpan Review' : 'Setujui' }}
                                        </button>
                                    </form>

                                    <form action="{{ route('validasi.laporan.revisi', $item) }}" method="POST" class="validation-form">
                                        @csrf
                                        <textarea name="note" rows="2" placeholder="{{ $instrukturLike ? 'Catatan koreksi instruktur (wajib)' : 'Catatan revisi (wajib)' }}" required></textarea>
                                        <button type="submit" class="btn-danger action-btn">
                                            {{ $instrukturLike ? 'Kembalikan ke Siswa' : 'Minta Revisi' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
        @endif
    </div>

    <script>
        (function () {
            const openButtons = document.querySelectorAll('[data-open-modal]');
            const closeButtons = document.querySelectorAll('[data-close-modal]');

            function openModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            }

            openButtons.forEach((button) => {
                button.addEventListener('click', () => openModal(button.dataset.openModal));
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal(button.dataset.closeModal));
            });
        })();
    </script>
@endsection


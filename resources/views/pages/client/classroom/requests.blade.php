<?php
use Livewire\Volt\Component;
use App\Models\BEMS\ClassroomBooking;

new class extends Component {
    public function requests() {
        return ClassroomBooking::query()->with('classroom.building')
            ->where('user_id', auth()->id())->latest()->get();
    }
}; ?>
<div>
    <x-header title="My Classroom Requests" subtitle="Status permintaan booking ruang kelas yang telah dikirim" separator />
    <x-card>
        <x-table :headers="[
            ['key'=>'classroom','label'=>'Ruangan'],
            ['key'=>'date','label'=>'Tanggal'],
            ['key'=>'time','label'=>'Waktu'],
            ['key'=>'purpose','label'=>'Keperluan'],
            ['key'=>'status','label'=>'Status'],
            ['key'=>'reason','label'=>'Keterangan'],
        ]" :rows="$this->requests()">
            @scope('cell_classroom',$row)
                <div class="font-semibold">{{ $row->classroom?->name ?? '-' }}</div>
                <div class="text-xs text-base-content/50">{{ $row->classroom?->building?->name ?? '' }}</div>
            @endscope
            @scope('cell_time',$row) {{ $row->time_start }} – {{ $row->time_end }} @endscope
            @scope('cell_status',$row)
                @if($row->status==='pending') <x-badge value="Menunggu" class="badge-warning" />
                @elseif($row->status==='approved') <x-badge value="Disetujui" class="badge-success" />
                @else <x-badge value="Ditolak" class="badge-error" /> @endif
            @endscope
            @scope('cell_reason',$row)
                @if($row->status==='rejected' && $row->reject_reason)
                    <span class="text-error text-xs">{{ $row->reject_reason }}</span>
                @else <span class="text-base-content/30 text-xs">—</span> @endif
            @endscope
        </x-table>
    </x-card>
</div>

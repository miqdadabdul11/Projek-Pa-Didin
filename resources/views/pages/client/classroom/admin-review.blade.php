<?php
use Livewire\Volt\Component;
use App\Models\BEMS\ClassroomBooking;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public string $filter = 'all';
    public bool $rejectModal = false;
    public ?int $rejectId = null;
    public string $rejectReason = '';

    public function requests() {
        return ClassroomBooking::query()->with('classroom.building','user')
            ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->latest()->get();
    }
    public function approve(int $id): void {
        ClassroomBooking::findOrFail($id)->update(['status'=>'approved']);
        $this->success('Request disetujui.');
    }
    public function openReject(int $id): void {
        $this->rejectId = $id; $this->rejectReason = ''; $this->rejectModal = true;
    }
    public function submitReject(): void {
        $this->validate(['rejectReason'=>'required|min:5']);
        ClassroomBooking::findOrFail($this->rejectId)->update(['status'=>'rejected','reject_reason'=>$this->rejectReason]);
        $this->rejectModal = false;
        $this->warning('Request ditolak.');
    }
}; ?>
<div>
    <x-header title="Classroom Booking Requests" subtitle="Review dan approve/reject permintaan booking ruang kelas" separator />
    <div class="flex gap-2 mb-4">
        @foreach(['all'=>'Semua','pending'=>'Pending','approved'=>'Disetujui','rejected'=>'Ditolak'] as $val=>$label)
            <x-button :label="$label" wire:click="$set('filter','{{ $val }}')" class="btn-sm {{ $filter===$val ? 'btn-primary' : 'btn-ghost' }}" />
        @endforeach
    </div>
    <x-card>
        <x-table :headers="[
            ['key'=>'user','label'=>'Pemohon'],
            ['key'=>'classroom','label'=>'Ruangan'],
            ['key'=>'date','label'=>'Tanggal'],
            ['key'=>'time','label'=>'Waktu'],
            ['key'=>'purpose','label'=>'Keperluan'],
            ['key'=>'status','label'=>'Status'],
            ['key'=>'action','label'=>'Action'],
        ]" :rows="$this->requests()">
            @scope('cell_user',$row)
                <div class="font-semibold">{{ $row->user?->name ?? '-' }}</div>
                <div class="text-xs text-base-content/50">{{ $row->user?->email ?? '' }}</div>
            @endscope
            @scope('cell_classroom',$row)
                <div class="font-semibold">{{ $row->classroom?->name ?? '-' }}</div>
                <div class="text-xs text-base-content/50">{{ $row->classroom?->building?->name ?? '' }}</div>
            @endscope
            @scope('cell_time',$row) {{ $row->time_start }} – {{ $row->time_end }} @endscope
            @scope('cell_status',$row)
                @if($row->status==='pending') <x-badge value="Menunggu" class="badge-warning" />
                @elseif($row->status==='approved') <x-badge value="Disetujui" class="badge-success" />
                @else
                    <x-badge value="Ditolak" class="badge-error" />
                    @if($row->reject_reason) <div class="text-xs text-error mt-1">{{ $row->reject_reason }}</div> @endif
                @endif
            @endscope
            @scope('cell_action',$row)
                @if($row->status==='pending')
                    <div class="flex gap-2">
                        <x-button icon="o-check" class="btn-sm btn-success" wire:click="approve({{ $row->id }})" wire:confirm="Setujui request ini?" />
                        <x-button icon="o-x-mark" class="btn-sm btn-error" wire:click="openReject({{ $row->id }})" />
                    </div>
                @else <span class="text-base-content/30 text-xs">—</span> @endif
            @endscope
        </x-table>
    </x-card>
    <x-modal wire:model="rejectModal" title="Tolak Request Booking" separator>
        <x-form wire:submit="submitReject">
            <x-textarea label="Alasan Penolakan" wire:model="rejectReason" placeholder="cth: Kelas sudah ada yang memesan pada jam tersebut" required />
            <x-slot:actions>
                <x-button label="Batal" @click="$wire.rejectModal = false" />
                <x-button label="Tolak Request" type="submit" class="btn-error" icon="o-x-mark" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>

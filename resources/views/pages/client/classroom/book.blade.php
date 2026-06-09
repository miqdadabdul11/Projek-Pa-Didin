<?php
use Livewire\Volt\Component;
use App\Models\BEMS\Classroom;
use App\Models\BEMS\ClassroomBooking;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;
    public string $search = '';
    public bool $bookModal = false;
    public ?int $selectedClassroomId = null;
    public string $bookDate = '';
    public string $bookTimeStart = '';
    public string $bookTimeEnd = '';
    public string $bookPurpose = '';

    public function classrooms() {
        return Classroom::query()->with('building')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get();
    }
    public function openBook(int $id): void {
        $this->selectedClassroomId = $id;
        $this->bookDate = $this->bookTimeStart = $this->bookTimeEnd = $this->bookPurpose = '';
        $this->bookModal = true;
    }
    public function submitBooking(): void {
        $this->validate([
            'bookDate'      => 'required|date|after_or_equal:today',
            'bookTimeStart' => 'required',
            'bookTimeEnd'   => 'required',
            'bookPurpose'   => 'required|min:5',
        ]);
        ClassroomBooking::create([
            'classroom_id' => $this->selectedClassroomId,
            'user_id'      => auth()->id(),
            'date'         => $this->bookDate,
            'time_start'   => $this->bookTimeStart,
            'time_end'     => $this->bookTimeEnd,
            'purpose'      => $this->bookPurpose,
            'status'       => 'pending',
        ]);
        $this->bookModal = false;
        $this->success('Request berhasil dikirim! Menunggu persetujuan admin.');
    }
}; ?>
<div>
    <x-header title="Book a Classroom" subtitle="Pilih ruangan dan kirim permintaan ke admin untuk disetujui" separator />
    <x-input placeholder="Cari ruangan..." wire:model.live="search" icon="o-magnifying-glass" class="mb-4" />
    <x-card>
        <x-table :headers="[
            ['key'=>'name','label'=>'Ruangan'],
            ['key'=>'building','label'=>'Gedung'],
            ['key'=>'capacity','label'=>'Kapasitas'],
            ['key'=>'action','label'=>'Action'],
        ]" :rows="$this->classrooms()">
            @scope('cell_building',$row) {{ $row->building?->name ?? '-' }} @endscope
            @scope('cell_capacity',$row) {{ $row->capacity ?? '-' }} orang @endscope
            @scope('cell_action',$row)
                <x-button label="Request" icon="o-calendar-plus" class="btn-sm btn-primary" wire:click="openBook({{ $row->id }})" />
            @endscope
        </x-table>
    </x-card>
    <x-modal wire:model="bookModal" title="Request Booking Ruangan" separator>
        <x-form wire:submit="submitBooking">
            <x-input label="Tanggal" type="date" wire:model="bookDate" required />
            <div class="grid grid-cols-2 gap-3">
                <x-input label="Jam Mulai" type="time" wire:model="bookTimeStart" required />
                <x-input label="Jam Selesai" type="time" wire:model="bookTimeEnd" required />
            </div>
            <x-textarea label="Keperluan / Nama Kelas" wire:model="bookPurpose" placeholder="cth: Praktikum Jaringan Komputer kelas XI TKJ A" required />
            <x-slot:actions>
                <x-button label="Batal" @click="$wire.bookModal = false" />
                <x-button label="Kirim Request" type="submit" class="btn-primary" icon="o-paper-airplane" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>

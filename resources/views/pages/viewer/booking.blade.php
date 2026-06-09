<?php

use Livewire\Component;
use App\Models\ClassSchedule;
use App\Models\ClassroomBooking;
use App\Models\BEMS\Classroom;
use App\Models\BEMS\Building;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;

    public $selectedDay = '';
    public $selectedBuilding = '';
    public $showBookingModal = false;
    public $selectedSchedule = null;
    public $bookingDate = '';
    public $purpose = '';

    public $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    public function mount()
    {
        $this->selectedDay = $this->days[now()->dayOfWeek - 1] ?? 'Senin';
        $this->bookingDate = now()->format('Y-m-d');
    }

    public function openBooking($scheduleId)
    {
        $this->selectedSchedule = ClassSchedule::with('classroom.building')->find($scheduleId);
        $this->showBookingModal = true;
    }

    public function submitBooking()
    {
        $this->validate([
            'bookingDate' => 'required|date',
            'purpose' => 'required|min:5',
        ]);

        $schedule = $this->selectedSchedule;

        // Cek konflik booking
        $conflict = ClassroomBooking::where('classroom_id', $schedule->classroom_id)
            ->where('date', $this->bookingDate)
            ->where('status', 'approved')
            ->where(function($q) use ($schedule) {
                $q->whereBetween('time_start', [$schedule->time_start, $schedule->time_end])
                  ->orWhereBetween('time_end', [$schedule->time_start, $schedule->time_end]);
            })->exists();

        if ($conflict) {
            $this->error('Slot waktu ini sudah di-booking orang lain!');
            return;
        }

        // Cek apakah user sudah pernah request slot yang sama
        $alreadyRequested = ClassroomBooking::where('classroom_id', $schedule->classroom_id)
            ->where('date', $this->bookingDate)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($alreadyRequested) {
            $this->error('Anda sudah mengirim request untuk slot ini!');
            return;
        }

        ClassroomBooking::create([
            'classroom_id' => $schedule->classroom_id,
            'user_id'      => Auth::id(),
            'date'         => $this->bookingDate,
            'time_start'   => $schedule->time_start,
            'time_end'     => $schedule->time_end,
            'purpose'      => $this->purpose,
            'status'       => 'pending',
        ]);

        $this->showBookingModal = false;
        $this->purpose = '';
        $this->success('Request booking berhasil dikirim! Menunggu persetujuan.');
    }

    public function with(): array
    {
        $clientId = Auth::user()->client_id;

        $buildings = Building::where('client_id', $clientId)->get();

        $schedules = ClassSchedule::with('classroom.building')
            ->whereHas('classroom.building', fn($q) => $q->where('client_id', $clientId))
            ->when($this->selectedDay, fn($q) => $q->where('day', $this->selectedDay))
            ->when($this->selectedBuilding, fn($q) => $q->whereHas('classroom', fn($q2) => $q2->where('building_id', $this->selectedBuilding)))
            ->orderBy('time_start')
            ->get();

        $myBookings = ClassroomBooking::where('user_id', Auth::id())
            ->with('classroom.building')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return [
            'buildings' => $buildings,
            'schedules' => $schedules,
            'myBookings' => $myBookings,
        ];
    }
};
?>

<div>
    <x-header title="Jadwal & Booking Ruangan" subtitle="Lihat jadwal dan ajukan booking ruangan" separator />

    {{-- Filter --}}
    <div class="flex gap-3 mb-4 flex-wrap">
        @foreach($days as $day)
            <button wire:click="$set('selectedDay', '{{ $day }}')"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition
                    {{ $selectedDay === $day ? 'bg-primary text-white' : 'bg-base-200 text-base-content' }}">
                {{ $day }}
            </button>
        @endforeach

        <select wire:model.live="selectedBuilding" class="select select-sm bg-base-200 border-none rounded-xl ml-auto">
            <option value="">Semua Gedung</option>
            @foreach($buildings as $b)
                <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Jadwal --}}
    <div class="bg-base-100 rounded-2xl shadow border border-base-200 mb-6">
        <div class="p-4 border-b border-base-200">
            <h3 class="font-bold">Jadwal {{ $selectedDay }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-xs uppercase text-base-content/50">
                        <th>Jam</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Dosen</th>
                        <th>Ruangan</th>
                        <th>Tipe</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                        <tr class="border-none hover:bg-base-200/50">
                            <td class="font-mono text-sm">
                                {{ substr($schedule->time_start, 0, 5) }} - {{ substr($schedule->time_end, 0, 5) }}
                            </td>
                            <td class="font-semibold">{{ $schedule->subject }}</td>
                            <td>{{ $schedule->sks }} SKS</td>
                            <td class="text-sm text-base-content/70">{{ $schedule->lecturer }}</td>
                            <td class="text-sm">{{ $schedule->classroom->name }}</td>
                            <td>
                                @if($schedule->type === 'praktikum')
                                    <span class="badge badge-warning text-white">Praktikum</span>
                                @else
                                    <span class="badge badge-info text-white">Kuliah</span>
                                @endif
                            </td>
                            <td>
                                <button wire:click="openBooking({{ $schedule->id }})"
                                    class="btn btn-xs btn-primary border-none shadow-none rounded-lg">
                                    Request
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-base-content/40 py-8">
                                Tidak ada jadwal untuk hari {{ $selectedDay }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- My Bookings --}}
    <div class="bg-base-100 rounded-2xl shadow border border-base-200">
        <div class="p-4 border-b border-base-200">
            <h3 class="font-bold">Request Booking Saya</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="text-xs uppercase text-base-content/50">
                        <th>Tanggal</th>
                        <th>Ruangan</th>
                        <th>Jam</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myBookings as $booking)
                        <tr class="border-none">
                            <td>{{ $booking->date }}</td>
                            <td>{{ $booking->classroom->name }}</td>
                            <td class="font-mono text-sm">
                                {{ substr($booking->time_start, 0, 5) }} - {{ substr($booking->time_end, 0, 5) }}
                            </td>
                            <td class="text-sm">{{ $booking->purpose }}</td>
                            <td>
                                @if($booking->status === 'approved')
                                    <span class="badge badge-success text-white">Disetujui</span>
                                @elseif($booking->status === 'rejected')
                                    <span class="badge badge-error text-white">Ditolak</span>
                                @else
                                    <span class="badge badge-warning text-white animate-pulse">Menunggu</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/40 py-8">
                                Belum ada request booking
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Booking --}}
    @if($showBookingModal && $selectedSchedule)
        <x-modal wire:model="showBookingModal" title="Request Booking Ruangan" box-class="rounded-2xl max-w-lg">
            <div class="space-y-3 mb-4">
                <div class="bg-base-200 rounded-xl p-4">
                    <div class="font-bold text-lg">{{ $selectedSchedule->subject }}</div>
                    <div class="text-sm text-base-content/60 mt-1">{{ $selectedSchedule->lecturer }}</div>
                    <div class="flex gap-4 mt-2 text-sm">
                        <span>🏫 {{ $selectedSchedule->classroom->name }}</span>
                        <span>⏰ {{ substr($selectedSchedule->time_start, 0, 5) }} - {{ substr($selectedSchedule->time_end, 0, 5) }}</span>
                        <span>📚 {{ $selectedSchedule->sks }} SKS</span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium mb-1 block">Tanggal</label>
                    <input type="date" wire:model="bookingDate" class="input input-bordered w-full rounded-xl" min="{{ now()->format('Y-m-d') }}" />
                </div>

                <div>
                    <label class="text-sm font-medium mb-1 block">Keperluan / Alasan</label>
                    <textarea wire:model="purpose" class="textarea textarea-bordered w-full rounded-xl" rows="3"
                        placeholder="Contoh: Mengikuti kelas Pemrograman OOP untuk tugas akhir..."></textarea>
                    @error('purpose') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <x-slot:actions>
                <x-button label="Batal" wire:click="$set('showBookingModal', false)" class="btn-ghost border-none" />
                <x-button label="Kirim Request" wire:click="submitBooking" class="btn-primary border-none shadow-none rounded-xl" spinner="submitBooking" />
            </x-slot:actions>
        </x-modal>
    @endif
</div>

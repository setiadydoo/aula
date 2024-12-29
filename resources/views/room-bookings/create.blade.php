@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4>Peminjaman Ruangan: {{ $room->name }}</h4>
        </div>
        <div class="card-body">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('rooms.bookings.store', $room) }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Tanggal Peminjaman</label>
                    <input type="date" name="booking_date" class="form-control" 
                           min="{{ date('Y-m-d') }}" 
                           value="{{ old('booking_date') }}" 
                           required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="start_time" class="form-control" 
                                   value="{{ old('start_time') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="end_time" class="form-control" 
                                   value="{{ old('end_time') }}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tujuan Peminjaman</label>
                    <textarea name="purpose" class="form-control" rows="3" required>{{ old('purpose') }}</textarea>
                </div>

                <div class="mb-4">
                    <h5>Jadwal Peminjaman yang Sudah Ada:</h5>
                    <div id="existing-bookings">
                        @forelse($existingBookings as $booking)
                        <div class="alert alert-info">
                            {{ $booking->booking_date->format('d/m/Y') }} : 
                            {{ $booking->start_time->format('H:i') }} - 
                            {{ $booking->end_time->format('H:i') }}
                            ({{ $booking->user->name }})
                        </div>
                        @empty
                        <p class="text-muted">Belum ada peminjaman untuk ruangan ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('rooms.index') }}" class="btn btn-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelector('input[name="booking_date"]').addEventListener('change', function(e) {
    const date = e.target.value;
    fetch(`{{ route('rooms.bookings.check-availability', $room) }}?date=${date}`)
        .then(response => response.json())
        .then(data => {
            const bookingsDiv = document.getElementById('existing-bookings');
            bookingsDiv.innerHTML = '';
            
            if (data.bookings.length === 0) {
                bookingsDiv.innerHTML = '<p class="text-muted">Tidak ada peminjaman pada tanggal ini.</p>';
                return;
            }

            data.bookings.forEach(booking => {
                const div = document.createElement('div');
                div.className = 'alert alert-info';
                div.textContent = `${booking.start} - ${booking.end}`;
                bookingsDiv.appendChild(div);
            });
        });
});
</script>
@endpush
@endsection 
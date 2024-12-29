@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Manajemen Aktivasi User</h4>
            <div>
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Tanggal Register</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                        <tr>
                            <td>{{ $users->firstItem() + $index }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $user->status === 'pending' ? 'warning' : ($user->status === 'active' ? 'success' : 'danger') }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    @if($user->status !== 'active')
                                    <form action="{{ route('admin.users.update-status', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-success btn-sm" 
                                                onclick="return confirm('Aktifkan user ini?')">
                                            <i class="fas fa-check-circle"></i> Aktivasi
                                        </button>
                                    </form>
                                    @endif
                                    
                                    @if($user->status !== 'inactive')
                                    <form action="{{ route('admin.users.update-status', $user) }}" method="POST" class="d-inline ms-1">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="inactive">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Nonaktifkan user ini?')">
                                            <i class="fas fa-ban"></i> Nonaktifkan
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Tidak ada user yang perlu diaktivasi
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    .table > :not(caption) > * > * {
        padding: 0.75rem;
    }
    .btn-group .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
document.getElementById('statusFilter').addEventListener('change', function() {
    window.location.href = "{{ route('admin.users.index') }}?status=" + this.value;
});

// Set selected option based on current URL
const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status') || 'pending';
document.getElementById('statusFilter').value = status;
</script>
@endpush
@endsection 
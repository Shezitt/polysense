@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@push('styles')
<style>
    .user-table {
        width: 100%;
        border-collapse: collapse;
    }

    .user-table th,
    .user-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .user-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
    }

    .user-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .badge-admin {
        background-color: #fef3c7;
        color: #92400e;
    }

    .badge-user {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-primary {
        background-color: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background-color: #2563eb;
    }

    .btn-danger {
        background-color: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-secondary {
        background-color: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #4b5563;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        color: #374151;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: white;
        padding: 24px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }

    .alert-error {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
</style>
@endpush

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800">Gestión de Usuarios</h1>
        <button onclick="openAddModal()" class="btn btn-primary">
            Agregar Usuario
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-error">
    {{ session('error') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-error">
    <ul style="margin: 0; padding-left: 20px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Usuarios Registrados</h2>
    
    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <span class="badge badge-{{ $user->role }}">
                        {{ $user->role === 'admin' ? 'Admin' : 'Usuario' }}
                    </span>
                </td>
                <td>
                    <button onclick="openRoleModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->role }}')" 
                            class="btn btn-secondary" style="margin-right: 8px;">
                        Cambiar Rol
                    </button>
                    <button onclick="confirmDelete({{ $user->id }}, '{{ $user->name }}')" 
                            class="btn btn-danger">
                        Eliminar
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px; color: #6b7280;">
                    No hay usuarios registrados
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Agregar Usuario -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Agregar Nuevo Usuario</h2>
        <form action="{{ route('usuarios.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" name="name" required value="{{ old('name') }}">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" required>
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Usuario</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="closeAddModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambiar Rol -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Cambiar Rol de Usuario</h2>
        <form id="roleForm" method="POST">
            @csrf
            @method('PUT')
            <p class="mb-4">Cambiar el rol del usuario: <strong id="roleUserName"></strong></p>
            <div class="form-group">
                <label>Nuevo Rol</label>
                <select name="role" id="newRole" required>
                    <option value="user">Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" onclick="closeRoleModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Actualizar Rol
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para eliminar -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('addModal').classList.add('active');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('active');
    }

    function openRoleModal(userId, userName, currentRole) {
        document.getElementById('roleModal').classList.add('active');
        document.getElementById('roleUserName').textContent = userName;
        document.getElementById('newRole').value = currentRole;
        document.getElementById('roleForm').action = '/usuarios/' + userId + '/role';
    }

    function closeRoleModal() {
        document.getElementById('roleModal').classList.remove('active');
    }

    function confirmDelete(userId, userName) {
        if (confirm('¿Estás seguro de eliminar al usuario "' + userName + '"? Esta acción no se puede deshacer.')) {
            const form = document.getElementById('deleteForm');
            form.action = '/usuarios/' + userId;
            form.submit();
        }
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const addModal = document.getElementById('addModal');
        const roleModal = document.getElementById('roleModal');
        if (event.target === addModal) {
            closeAddModal();
        }
        if (event.target === roleModal) {
            closeRoleModal();
        }
    }
</script>
@endpush
@endsection

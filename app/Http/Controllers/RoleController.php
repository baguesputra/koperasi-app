<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    // Role bawaan sistem yang tidak boleh dihapus (banyak logic implisit bergantung ke ini)
    private const ROLE_DILINDUNGI = ['admin', 'bendahara', 'ketua_koperasi', 'anggota'];

    public function index(): Response
    {
        $roles = Role::withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'jumlah_user' => $role->users_count,
                'dilindungi' => in_array($role->name, self::ROLE_DILINDUNGI),
            ]);

        return Inertia::render('Role/Index', ['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'alpha_dash'],
        ]);

        $role = Role::create(['name' => $request->name]);

        AuditLog::catat('tambah_role', "Role baru dibuat: {$role->name}", null, ['name' => $role->name]);

        return redirect()->route('role.edit', $role->id)
            ->with('status', 'Role berhasil dibuat. Silakan atur hak akses.');
    }

    public function edit(Role $role): Response
    {
        $semuaPermission = Permission::orderBy('name')->get()->groupBy(function ($p) {
            return explode('.', $p->name)[0]; // kelompokkan berdasarkan prefix, misal "anggota", "pinjaman"
        });

        return Inertia::render('Role/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'dilindungi' => in_array($role->name, self::ROLE_DILINDUNGI),
            ],
            'permissionTerpilih' => $role->permissions->pluck('name'),
            'semuaPermission' => $semuaPermission,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $permissionLama = $role->permissions->pluck('name')->toArray();
        $role->syncPermissions($request->permissions ?? []);

        AuditLog::catat(
            'update_permission_role',
            "Hak akses role '{$role->name}' diperbarui.",
            ['permissions' => $permissionLama],
            ['permissions' => $request->permissions ?? []]
        );

        return back()->with('status', 'Hak akses role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::ROLE_DILINDUNGI)) {
            return back()->withErrors(['role' => 'Role bawaan sistem tidak dapat dihapus.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['role' => 'Role tidak dapat dihapus karena masih digunakan oleh user.']);
        }

        AuditLog::catat('hapus_role', "Role dihapus: {$role->name}", ['name' => $role->name], null);

        $role->delete();

        return redirect()->route('role.index')->with('status', 'Role berhasil dihapus.');
    }
}
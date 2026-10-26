<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\LaporanAdmin;
use App\Models\Mahasiswa;
use App\Models\PengumpulanTugas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['mahasiswa', 'dosen', 'admin'];

    private function profileRelations(): array
    {
        return ['mahasiswa.dosenWali.user', 'dosen'];
    }

    /**
     * Paginated user list with role filter and search (username, name, email).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['nullable', Rule::in(self::ROLES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::with($this->profileRelations());

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('username', 'like', "%{$q}%")
                    ->orWhere('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return response()->json(
            $query->orderBy('role')->orderBy('nama_lengkap')->paginate((int) $request->query('per_page', 15))
        );
    }

    /**
     * Create an account of any role (plus its mahasiswa/dosen profile row).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:pengguna,username'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:120', 'unique:pengguna,email'],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat_rumah' => ['nullable', 'string', 'max:255'],

            'nrp' => ['required_if:role,mahasiswa', 'nullable', 'string', 'max:14', 'unique:mahasiswa,nrp'],
            'nip_wali' => ['required_if:role,mahasiswa', 'nullable', 'string', 'exists:dosen,nip'],
            'semester_aktif' => ['nullable', 'integer', 'min:1', 'max:14'],

            'nip' => ['required_if:role,dosen', 'nullable', 'string', 'max:32', 'unique:dosen,nip'],
            'departemen' => ['required_if:role,mahasiswa,dosen', 'nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $generated = empty($validated['password']);
        $password = $generated ? Str::password(12, symbols: false) : $validated['password'];

        $user = DB::transaction(function () use ($validated, $password) {
            $user = User::create([
                'user_id' => $this->generateUserId($validated['role']),
                'username' => $validated['username'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'email' => $validated['email'],
                'password_hash' => $password,
                'role' => $validated['role'],
                'nomor_telepon' => $validated['nomor_telepon'] ?? null,
                'alamat_rumah' => $validated['alamat_rumah'] ?? null,
            ]);

            if ($validated['role'] === 'mahasiswa') {
                Mahasiswa::create([
                    'nrp' => $validated['nrp'],
                    'user_id' => $user->user_id,
                    'nip_wali' => $validated['nip_wali'],
                    'departemen' => $validated['departemen'],
                    'semester_aktif' => $validated['semester_aktif'] ?? 1,
                    'status' => $validated['status'] ?? 'Aktif',
                ]);
            } elseif ($validated['role'] === 'dosen') {
                Dosen::create([
                    'nip' => $validated['nip'],
                    'user_id' => $user->user_id,
                    'departemen' => $validated['departemen'],
                    'status' => $validated['status'] ?? 'Aktif',
                ]);
            }

            return $user;
        });

        $response = [
            'message' => 'Akun berhasil dibuat.',
            'user' => $user->load($this->profileRelations()),
        ];

        if ($generated) {
            $response['generated_password'] = $password;
        }

        return response()->json($response, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'user' => User::with($this->profileRelations())->findOrFail($id),
        ]);
    }

    /**
     * Update account data and the role-specific profile (role itself is not changed here).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::with(['mahasiswa', 'dosen'])->findOrFail($id);

        $validated = $request->validate([
            'username' => ['sometimes', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('pengguna', 'username')->ignore($user->user_id, 'user_id')],
            'nama_lengkap' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:120', Rule::unique('pengguna', 'email')->ignore($user->user_id, 'user_id')],
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'alamat_rumah' => ['nullable', 'string', 'max:255'],

            'nip_wali' => ['sometimes', 'string', 'exists:dosen,nip'],
            'semester_aktif' => ['sometimes', 'integer', 'min:1', 'max:14'],
            'departemen' => ['sometimes', 'string', 'max:80'],
            'status' => ['sometimes', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $userUpdates = array_intersect_key($validated, array_flip([
                'username', 'nama_lengkap', 'email', 'nomor_telepon', 'alamat_rumah',
            ]));

            if (! empty($validated['password'])) {
                $userUpdates['password_hash'] = $validated['password'];
                $user->tokens()->delete();
            }

            $user->update($userUpdates);

            if ($user->role === 'mahasiswa' && $user->mahasiswa) {
                $user->mahasiswa->update(array_intersect_key($validated, array_flip([
                    'nip_wali', 'semester_aktif', 'departemen', 'status',
                ])));
            } elseif ($user->role === 'dosen' && $user->dosen) {
                $user->dosen->update(array_intersect_key($validated, array_flip(['departemen', 'status'])));
            }
        });

        return response()->json([
            'message' => 'Akun berhasil diperbarui.',
            'user' => $user->fresh($this->profileRelations()),
        ]);
    }

    /**
     * Delete an account. Refuses (409) when the account still has dependent academic data.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::with(['mahasiswa', 'dosen'])->findOrFail($id);

        if ($user->user_id === $request->user()->user_id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 403);
        }

        if ($reason = $this->dependencyReason($user)) {
            return response()->json(['message' => $reason], 409);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->mahasiswa?->delete();
            $user->dosen?->delete();
            $user->delete();
        });

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
        ]);
    }

    /**
     * Reset a user's password (given, or generated and returned once).
     */
    public function resetPassword(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8', 'max:72'],
        ]);

        $generated = empty($validated['password']);
        $password = $generated ? Str::password(12, symbols: false) : $validated['password'];

        $user->update(['password_hash' => $password]);
        $user->tokens()->delete();

        $response = ['message' => 'Password berhasil direset.'];

        if ($generated) {
            $response['generated_password'] = $password;
        }

        return response()->json($response);
    }

    private function dependencyReason(User $user): ?string
    {
        if (LaporanAdmin::where('user_id', $user->user_id)->exists()) {
            return 'Akun tidak dapat dihapus karena masih memiliki laporan/tiket.';
        }

        if ($user->mahasiswa) {
            if ($user->mahasiswa->krs()->exists()) {
                return 'Mahasiswa tidak dapat dihapus karena masih memiliki data KRS.';
            }
            if (PengumpulanTugas::where('nrp', $user->mahasiswa->nrp)->exists()) {
                return 'Mahasiswa tidak dapat dihapus karena masih memiliki pengumpulan tugas.';
            }
        }

        if ($user->dosen) {
            if ($user->dosen->mahasiswaBimbingan()->exists()) {
                return 'Dosen tidak dapat dihapus karena masih menjadi dosen wali mahasiswa. Pindahkan perwalian terlebih dahulu.';
            }
            if ($user->dosen->classroom()->exists()) {
                return 'Dosen tidak dapat dihapus karena masih mengampu kelas. Ganti dosen pengampu terlebih dahulu.';
            }
        }

        return null;
    }

    private function generateUserId(string $role): string
    {
        do {
            $id = strtoupper(substr($role, 0, 3)).'_'.strtoupper(Str::random(8));
        } while (User::whereKey($id)->exists());

        return $id;
    }
}

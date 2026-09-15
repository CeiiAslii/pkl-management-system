<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMajorRequest;
use App\Http\Requests\Admin\UpdateMajorRequest;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MajorController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Major::class);

        return view('admin.majors.index', ['majors' => Major::query()->withCount('users')->orderBy('code')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Major::class);

        return view('admin.majors.form', ['major' => new Major]);
    }

    public function store(StoreMajorRequest $request): RedirectResponse
    {
        Gate::authorize('create', Major::class);
        Major::query()->create($request->validated());

        return to_route('admin.majors.index')->with('status', 'Jurusan berhasil ditambahkan.');
    }

    public function edit(Major $major): View
    {
        Gate::authorize('update', $major);

        return view('admin.majors.form', compact('major'));
    }

    public function update(UpdateMajorRequest $request, Major $major): RedirectResponse
    {
        Gate::authorize('update', $major);
        $major->update($request->validated());

        return to_route('admin.majors.index')->with('status', 'Jurusan berhasil diperbarui.');
    }

    public function destroy(Major $major): RedirectResponse
    {
        Gate::authorize('delete', $major);

        if ($major->users()->exists()) {
            throw ValidationException::withMessages(['major' => 'Jurusan tidak dapat dihapus karena masih digunakan oleh pengguna.']);
        }

        $major->delete();

        return to_route('admin.majors.index')->with('status', 'Jurusan berhasil dihapus.');
    }
}

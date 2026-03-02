<?php

namespace App\Http\Controllers;

use App\Models\Departament;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Departament::query();

        // Filtering
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'updated_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'code', 'location', 'cost', 'status', 'updated_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $departaments = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Departaments/Index', [
            'departaments' => $departaments,
            'filters' => $request->only(['search', 'sort', 'direction'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Departaments/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:1,0',
        ]);

        Departament::create($validated);

        return redirect()->route('departaments.index')->with('success', 'Departamento creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Departament $departament)
    {
        return Inertia::render('Admin/Departaments/Edit', [
            'departament' => $departament
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Departament $departament)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:1,0',
        ]);

        $departament->update($validated);

        return redirect()->route('departaments.index')->with('success', 'Departamento actualizado exitosamente.');
    }
}

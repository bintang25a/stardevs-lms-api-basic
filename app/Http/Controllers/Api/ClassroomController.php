<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $user = $request->user();
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Classroom::withCount([
      'assistants',
      'students'
    ]);

    if ($user->role !== 'admin') {
      $query->where(function ($q) use ($user) {
        $q->whereHas('assistants', function ($subQuery) use ($user) {
          $subQuery->where('users.id', $user->id);
        })->orWhereHas('students', function ($subQuery) use ($user) {
          $subQuery->where('users.id', $user->id);
        });
      });
    }

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('code', 'LIKE', "%{$search}%");
      });
    }

    $classrooms = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Classrooms retrieved successfully',
      'data'    => $classrooms->through(function ($classroom) {
        return [
          'id'               => $classroom->id,
          'name'             => $classroom->name,
          'code'             => $classroom->code,
          'description'      => $classroom->description,
          'schedule'         => $classroom->schedule,
          'total_assistants' => $classroom->assistants_count,
          'total_students'   => $classroom->students_count,
          'created_at'       => $classroom->created_at,
          'updated_at'       => $classroom->updated_at,
        ];
      })
    ], 200);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name'          => 'required|string|max:255',
      'code'          => 'required|string|max:50|unique:classrooms,code',
      'description'   => 'nullable|string',
      'schedule'      => 'nullable|date',
      'assistant_ids' => 'required|array|min:1',
      'assistant_ids.*' => 'exists:users,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $classroom = Classroom::create([
      'name'        => $request->name,
      'code'        => $request->code,
      'description' => $request->description,
      'schedule'    => $request->schedule,
    ]);

    $classroom->users()->attach($request->assistant_ids, ['role' => 'asisten']);
    $classroom->load('assistants');

    return response()->json([
      'success' => true,
      'message' => 'Classroom created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $classroom = Classroom::with(['assistants:id,name'])
      ->withCount('assistants')
      ->withCount('students')
      ->find($id);

    if (! $classroom) {
      return response()->json([
        'success' => false,
        'message' => 'Classroom not found'
      ], 404);
    }

    $assistants = $classroom->assistants->map(function ($assistant) {
      return [
        'id'    => $assistant->id,
        'name'  => $assistant->name,
      ];
    });

    return response()->json([
      'success' => true,
      'message' => 'Classroom retrieved successfully',
      'data'    => [
        'id'          => $classroom->id,
        'name'        => $classroom->name,
        'code'        => $classroom->code,
        'description' => $classroom->description,
        'schedule'    => $classroom->schedule,
        'assistants'  => $assistants,
        'total_assistants' => $classroom->assistants_count,
        'total_students' => $classroom->students_count,
        'created_at'  => $classroom->created_at,
        'updated_at'  => $classroom->updated_at,
      ]
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $classroom = Classroom::find($id);

    if (! $classroom) {
      return response()->json([
        'success' => false,
        'message' => 'Classroom not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'name'        => 'sometimes|required|string|max:255',
      'code'        => 'sometimes|required|string|max:50|unique:classrooms,code,' . $id,
      'description' => 'nullable|string',
      'schedule'    => 'nullable|date',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $classroom->update($request->only([
      'name',
      'code',
      'description',
      'schedule'
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Classroom updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $classroom = Classroom::find($id);

    if (! $classroom) {
      return response()->json([
        'success' => false,
        'message' => 'Classroom not found'
      ], 404);
    }

    $classroom->delete();

    return response()->json([
      'success' => true,
      'message' => 'Classroom deleted successfully'
    ], 200);
  }
}

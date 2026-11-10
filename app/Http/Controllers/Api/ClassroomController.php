<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
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

    $abilities = $request->user()->currentAccessToken()->abilities;
    $roleAbility = collect($abilities)->first(fn($ability) => str_starts_with($ability, 'role:'));
    $tokenRole = $roleAbility ? str_replace('role:', '', $roleAbility) : null;

    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Classroom::withCount([
      'assistants',
      'students',
    ]);

    if ($tokenRole === 'praktikan') {
      $query->where(function ($q) use ($user) {
        $q->whereHas('students', function ($subQuery) use ($user) {
          $subQuery->where('users.id', $user->id);
        });
      });
    }

    if ($tokenRole === 'asisten') {
      $query->where(function ($q) use ($user) {
        $q->whereHas('assistants', function ($subQuery) use ($user) {
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
      'assistant_emails'    => 'required|array|min:1',
      'assistant_emails.*'  => 'required|email|exists:users,email',
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

    $assistantIds = User::whereIn('email', $request->assistant_emails)->pluck('id');

    $assistantData = [];
    foreach ($assistantIds as $id) {
      $assistantData[$id] = ['role' => 'asisten'];
    }

    $classroom->users()->attach($assistantData);
    $classroom->load('assistants');

    return response()->json([
      'success' => true,
      'message' => 'Classroom created successfully',
      'data' => $classroom,
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(Request $request, string $id)
  {
    $classroom = Classroom::with(['assistants', 'students', 'sections', 'announcements', 'assignments', 'materials'])
      ->withCount('assistants')
      ->withCount('students')
      ->withCount('announcements')
      ->withCount('assignments')
      ->withCount('materials')
      ->find($id);

    if (! $classroom) {
      return response()->json([
        'success' => false,
        'message' => 'Classroom not found'
      ], 404);
    }

    $abilities = $request->user()->currentAccessToken()->abilities;
    $roleAbility = collect($abilities)->first(fn($ability) => str_starts_with($ability, 'role:'));
    $tokenRole = $roleAbility ? str_replace('role:', '', $roleAbility) : null;

    $assistants = $classroom->assistants->map(function ($assistant) {
      return [
        'id'    => $assistant->id,
        'name'  => $assistant->name,
        'photo' => $assistant->photo,
      ];
    });

    $students = $classroom->students->map(function ($student) {
      return [
        'id'    => $student->id,
        'name'  => $student->name,
        'photo' => $student->photo,
      ];
    });

    $sections = $classroom->sections->map(function ($section) {
      return [
        'id'    => $section->id,
        'title' => $section->title,
        'date'  => $section->date,
        'order' => $section->order,
      ];
    });

    $announcements = $classroom->announcements->map(function ($announcement) {
      return [
        'id'            => $announcement->id,
        'title'         => $announcement->title,
        'announcement'  => $announcement->announcement,
        'section_id'    => $announcement->section_id,
        'order'         => $announcement->order,
        'updated_at'    => $announcement->updated_at,
        'created_at'    => $announcement->created_at,
      ];
    });

    $materials = $classroom->materials->map(function ($material) {
      return [
        'id'          => $material->id,
        'title'       => $material->title,
        'description' => $material->description,
        'section_id'  => $material->section_id,
        'order'       => $material->order,
        'updated_at'  => $material->updated_at,
        'created_at'  => $material->created_at,
      ];
    });

    $assignments = $classroom->assignments->map(function ($assignment) {
      return [
        'id'          => $assignment->id,
        'title'       => $assignment->title,
        'description' => $assignment->description,
        'section_id'  => $assignment->section_id,
        'order'       => $assignment->order,
        'started_at'  => $assignment->started_at,
        'ended_at'    => $assignment->ended_at,
      ];
    });

    return response()->json([
      'success' => true,
      'message' => 'Classroom retrieved successfully',
      'data'    => [
        'id'                => $classroom->id,
        'name'              => $classroom->name,
        'code'              => $classroom->code,
        'description'       => $classroom->description,
        'schedule'          => $classroom->schedule,
        'assistants'        => $assistants,
        'students'          => $students,
        'sections'          => $sections,
        'announcements'     => $announcements,
        'materials'         => $materials,
        'assignments'       => $assignments,
        'total_assistants'  => $classroom->assistants_count,
        'total_students'    => $classroom->students_count,
        'total_announcements' => $classroom->announcements_count,
        'total_assignments'   => $classroom->assignments_count,
        'total_materials'     => $classroom->materials_count,
        'created_at'        => $classroom->created_at,
        'updated_at'        => $classroom->updated_at,
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
      'data'    => $classroom,
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

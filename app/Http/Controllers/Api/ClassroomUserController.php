<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassroomUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassroomUserController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'classroom_code' => 'nullable|string|exists:classrooms,code',
      'classroom_id'  => 'required|string|exists:classrooms,id',
      'user_id'       => 'required|string|exists:users,id',
      'role'          => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $role = $request->classroom_code ? 'student' : $request->role;

    ClassroomUser::firstOrCreate([
      'classroom_id' => $request->classroom_id,
      'user_id'      => $request->user_id,
      'role'         => $role,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Classroom User created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Request $request, string $id = null)
  {
    $validator = Validator::make($request->all(), [
      'classroom_id' => 'required|exists:classrooms,id',
      'user_id'      => 'required|exists:users,id',
      'role'         => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $currentUser = $request->user();
    $isAdminOrAssistant = in_array($currentUser->role, ['admin', 'asisten']);

    if (! $isAdminOrAssistant) {
      if ($currentUser->id != $request->user_id) {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized. You can only remove yourself from the classroom.',
        ], 403);
      }

      if ($request->role !== 'praktikan') {
        return response()->json([
          'success' => false,
          'message' => 'Unauthorized. You can only remove your own student role.',
        ], 403);
      }
    }

    $deleted = ClassroomUser::where('classroom_id', $request->classroom_id)
      ->where('user_id', $request->user_id)
      ->where('role', $request->role)
      ->delete();

    if (!$deleted) {
      return response()->json([
        'success' => false,
        'message' => 'Classroom user relation not found.',
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Classroom User removed successfully',
    ], 200);
  }
}

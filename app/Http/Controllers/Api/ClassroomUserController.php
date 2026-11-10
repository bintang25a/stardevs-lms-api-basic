<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ClassroomUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
  public function join(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'classroom_code' => 'required|string|exists:classrooms,code',
      'classroom_id'  => 'nullable|string|exists:classrooms,id',
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

    $classroom = Classroom::firstWhere('code', $request->classroom_code);

    ClassroomUser::firstOrCreate([
      'classroom_id' => $request->classroom_id ?? $classroom->id,
      'user_id'      => $request->user_id,
      'role'         => $request->role,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Classroom User created successfully',
      'data'    => $classroom,
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function add(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'classroom_id'  => 'required|string|exists:classrooms,id',
      'uids'          => 'required|array|min:1',
      'uids.*'        => 'required|string',
      'role'          => 'required|string|in:admin,asisten,praktikan',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $userIds = User::query()
      ->whereIn('email', $request->uids)
      ->orWhereIn('nim', $request->uids)
      ->pluck('id');

    if ($userIds->isEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'Users with email/NIM not found.',
      ], 404);
    }

    foreach ($userIds as $userId) {
      ClassroomUser::firstOrCreate([
        'classroom_id' => $request->classroom_id,
        'user_id'      => $userId,
        'role'         => $request->role,
      ]);
    }

    return response()->json([
      'success' => true,
      'message' => 'Classroom Users created successfully',
    ], 201);
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
  public function leave(Request $request)
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnnouncementController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Announcement::all();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('code', 'LIKE', "%{$search}%");
      });
    }

    $announcements = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Announcements retrieved successfully',
      'data'    => $announcements->through(function ($announcement) {
        return [
          'id'            => $announcement->id,
          'title'         => $announcement->title,
          'announcement'  => $announcement->announcement,
          'classroom_id'  => $announcement->classroom_id,
          'section_id'    => $announcement->section_id,
          'created_at'    => $announcement->created_at,
          'updated_at'    => $announcement->updated_at,
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
      'title'         => 'required|string|max:255',
      'announcement'  => 'required',
      'classroom_id'  => 'required|string|exists:classrooms,id',
      'section_id'    => 'nullable|string|exists:sections,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    Announcement::create([
      'title'         => $request->title,
      'announcement'  => $request->announcement,
      'classroom_id' => $request->classroom_id,
      'section_id' => $request->section_id,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Announcement created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $announcement = Announcement::find($id);

    if (! $announcement) {
      return response()->json([
        'success' => false,
        'message' => 'Announcement not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Announcement retrieved successfully',
      'data'    => $announcement
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $announcement = Announcement::find($id);

    if (! $announcement) {
      return response()->json([
        'success' => false,
        'message' => 'Announcement not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'title'         => 'required|string|max:255',
      'announcement'  => 'required',
      'section_id'    => 'nullable|string|exists:sections,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $announcement->update($request->only([
      'title',
      'announcement',
      'section_id',
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Announcement updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $announcement = Announcement::find($id);

    if (! $announcement) {
      return response()->json([
        'success' => false,
        'message' => 'Announcement not found'
      ], 404);
    }

    $announcement->delete();

    return response()->json([
      'success' => true,
      'message' => 'Announcement deleted successfully'
    ], 200);
  }
}

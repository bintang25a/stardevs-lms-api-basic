<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttachmentController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Attachment::all();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'LIKE', "%{$search}%")
          ->orWhere('code', 'LIKE', "%{$search}%");
      });
    }

    $attachments = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Attachments retrieved successfully',
      'data'    => $attachments->through(function ($attachment) {
        return [
          'id'          => $attachment->id,
          'file_name'   => $attachment->file_name,
          'file_path'   => $attachment->file_path,
          'file_url'    => $attachment->file_url,
          'class_model' => $attachment->class_model,
          'class_id'    => $attachment->class_id,
          'type'        => $attachment->type,
          'created_at'  => $attachment->created_at,
          'updated_at'  => $attachment->updated_at,
        ];
      })
    ], 200);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $attachment = Attachment::find($id);

    if (! $attachment) {
      return response()->json([
        'success' => false,
        'message' => 'Aattachment not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Aattachment retrieved successfully',
      'data'    => $attachment
    ], 200);
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
  public function destroy(string $id)
  {
    $attachment = Attachment::find($id);

    if (! $attachment) {
      return response()->json([
        'success' => false,
        'message' => 'Aattachment not found'
      ], 404);
    }

    $attachment->delete();

    return response()->json([
      'success' => true,
      'message' => 'Aattachment deleted successfully'
    ], 200);
  }
}

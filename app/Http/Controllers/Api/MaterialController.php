<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $search = $request->query('search') ?? $request->query('keyword');
    $perPage = $request->query('per_page', 10);

    $query = Material::all();

    if (!empty($search)) {
      $query->where(function ($q) use ($search) {
        $q->where('title', 'LIKE', "%{$search}%")
          ->orWhere('description', 'LIKE', "%{$search}%");
      });
    }

    $materials = $query->paginate($perPage);

    return response()->json([
      'success' => true,
      'message' => 'Materials retrieved successfully',
      'data'    => $materials->through(function ($material) {
        return [
          'id'            => $material->id,
          'title'         => $material->title,
          'description'   => $material->description,
          'classroom_id'  => $material->classroom_id,
          'created_at'    => $material->created_at,
          'updated_at'    => $material->updated_at,
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
      'description'   => 'nullable|string',
      'classroom_id'  => 'required|string|exists:classrooms,id',
      'section_id'    => 'nullable|string|exists:sections,id',
      'order'         => 'nullable|numeric',
      'files'         => 'nullable|array',
      'files.*'       => 'file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files_url'     => 'nullable|array',
      'files_url.*'   => 'string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    DB::transaction(function () use ($request) {
      $material = Material::create([
        'title'         => $request->title,
        'description'   => $request->description,
        'classroom_id'  => $request->classroom_id,
        'section_id'    => $request->section_id,
        'order'         => $request->order,
      ]);

      $type = 'material';

      if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
          $originalName = $file->getClientOriginalName();

          $fileName = time() . '_' . str_replace(' ', '_', $originalName);

          $filePath = $file->storeAs($type . 's', $fileName, 'public');

          $fileUrl = Storage::url($filePath);

          Attachment::create([
            'file_name'   => $originalName,
            'file_path'   => $filePath,
            'file_url'    => $fileUrl,
            'class_model' => 'App\Models\Material',
            'class_id'    => $material->id,
            'type'        => $type,
          ]);
        }
      }

      if ($request->has('files_url') && is_array($request->files_url)) {
        foreach ($request->files_url as $url) {

          $parsedUrl = parse_url($url, PHP_URL_PATH);
          $fileNameFromUrl = $parsedUrl ? basename($parsedUrl) : 'External Link';

          Attachment::create([
            'file_name'   => $fileNameFromUrl,
            'file_path'   => null,
            'file_url'    => $url,
            'class_model' => 'App\Models\Material',
            'class_id'    => $material->id,
            'type'        => $type,
          ]);
        }
      }
    });

    return response()->json([
      'success' => true,
      'message' => 'Material created successfully',
    ], 201);
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $material = Material::with('classroom')->find($id);

    if (! $material) {
      return response()->json([
        'success' => false,
        'message' => 'Material not found'
      ], 404);
    }

    $files = Attachment::where('class_id', $id)
      ->where('class_model', 'App\Models\Material')
      ->where('type', 'material')
      ->get();

    return response()->json([
      'success' => true,
      'message' => 'Material retrieved successfully',
      'data'    => [
        'id'                => $material->id,
        'title'             => $material->title,
        'description'       => $material->description,
        'classroom_id'      => $material->classroom_id,
        'section_id'        => $material->section_id,
        'order'             => $material->order,
        'classroom'         => $material->classroom,
        'files'             => $files,
        'created_at'        => $material->created_at,
        'updated_at'        => $material->updated_at,
      ]
    ], 200);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $material = Material::find($id);

    if (! $material) {
      return response()->json([
        'success' => false,
        'message' => 'Material not found'
      ], 404);
    }

    $validator = Validator::make($request->all(), [
      'title'         => 'required|string|max:255',
      'description'   => 'nullable|string',
      'section_id'    => 'nullable|string|exists:sections,id',
      'order'         => 'nullable|numeric',
      'files'         => 'nullable|array',
      'files.*'       => 'file|extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,c,cpp,java,py,ipynb,js,zip,rar|max:20480',
      'files_url'     => 'nullable|string',
      'files_remove_ids'    => 'nullable|array',
      'files_remove_ids.*'  => 'string|exists:attachments,id',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    DB::transaction(function () use ($request, $material) {
      $material->update($request->only([
        'title'         => $request->title,
        'description'   => $request->description,
        'section_id'    => $request->section_id,
        'order'         => $request->order,
      ]));

      $type = 'material';

      if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
          $originalName = $file->getClientOriginalName();

          $fileName = time() . '_' . str_replace(' ', '_', $originalName);

          $filePath = $file->storeAs($type.'s', $fileName, 'public');

          $fileUrl = Storage::url($filePath);

          Attachment::create([
            'file_name'   => $originalName,
            'file_path'   => $filePath,
            'file_url'    => $fileUrl,
            'class_model' => 'App\Models\Material',
            'class_id'    => $material->id,
            'type'        => $type,
          ]);
        }
      }

      if ($request->has('files_url') && is_array($request->files_url)) {
        foreach ($request->files_url as $url) {

          $parsedUrl = parse_url($url, PHP_URL_PATH);
          $fileNameFromUrl = $parsedUrl ? basename($parsedUrl) : 'External Link';

          Attachment::create([
            'file_name'   => $fileNameFromUrl,
            'file_path'   => null,
            'file_url'    => $url,
            'class_model' => 'App\Models\Material',
            'class_id'    => $material->id,
            'type'        => $type,
          ]);
        }
      }

      if ($request->has('files_remove_ids') && is_array($request->files_remove_ids)) {

        $attachmentsToRemove = Attachment::whereIn('id', $request->files_remove_ids)
          ->where('class_model', 'App\Models\Material')
          ->where('type', $type)
          ->get();

        foreach ($attachmentsToRemove as $attachment) {
          if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
          }

          $attachment->delete();
        }
      }
    });

    return response()->json([
      'success' => true,
      'message' => 'Material updated successfully',
    ], 200);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    $material = Material::find($id);

    if (! $material) {
      return response()->json([
        'success' => false,
        'message' => 'Material not found'
      ], 404);
    }

    $attachmentsToRemove = Attachment::where('type', 'material')
      ->where('class_model', 'App\Models\Material')
      ->where('class_id', $material->id)
      ->get();

    foreach ($attachmentsToRemove as $attachment) {
      if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
        Storage::disk('public')->delete($attachment->file_path);
      }

      $attachment->delete();
    }

    $material->delete();

    return response()->json([
      'success' => true,
      'message' => 'Material deleted successfully'
    ], 200);
  }
}

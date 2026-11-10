<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ActionController extends Controller
{
  public function raiseOrder(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'classroom_id'        => 'required|exists:classrooms,id',
      'current_id'          => 'required',
      'current_type'        => 'required|string|in:section,assignment,material,announcement',
      'current_order'       => 'required|numeric',
      'data'                => 'required|array|min:1',
      'data.*.content_type' => 'required|string|in:section,assignment,material,announcement',
      'data.*.id'           => 'required',
      'data.*.order'        => 'required|numeric',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $items = $request->data;
    $currentId = $request->current_id;
    $currentType = $request->current_type;
    $currentOrder = $request->current_order;

    $idx = null;

    foreach ($items as $index => $item) {
      if ($item['id'] === $currentId && $item['content_type'] === $currentType) {
        $idx = $index;
        break;
      }
    }

    $prevItem = $items[$idx - 1];

    if ($prevItem && $prevItem['order'] === $currentOrder) {
      foreach ($items as $index => $item) {
        $modelName = Str::studly($item['content_type']);
        $modelClass = "App\\Models\\{$modelName}";

        $condition = $index !== $idx && $index >= $idx - 1;

        if ($condition) {
          $data = $modelClass::find($item['id']);

          $data->update([
            'order' => $item['order'] + 1
          ]);
        }
      }
    } else if ($prevItem && $prevItem['order'] < $currentOrder) {
      $modelNamePrev = Str::studly($prevItem['content_type']);
      $modelClassPrev = "App\\Models\\{$modelNamePrev}";

      $prevData = $modelClassPrev::find($prevItem['id']);
      $difference = $currentOrder - $prevItem['order'];

      if ($difference > 1) {
        $prevData->update([
          'order' =>  $currentOrder - $difference + 1
        ]);
      } else {
        $prevData->update([
          'order' =>  $currentOrder
        ]);
      }

      $modelNameCurr = Str::studly($currentType);
      $modelClassCurr = "App\\Models\\{$modelNameCurr}";

      $currentData = $modelClassCurr::find($currentId);
      $currentData->update([
        'order' => $prevItem['order']
      ]);
    }

    $classroomController = new ClassroomController();
    $response = $classroomController->show($request->classroom_id);
    $responseData = $response->getData(true);

    if (!$responseData['success']) {
      return response()->json([
        'message' => 'Classroom not found'
      ], 404);
    }

    $classroomData = $responseData['data'];

    return response()->json([
      'success' => true,
      'message' => 'Order raised successfully',
      'data'    => $classroomData,
    ], 200);
  }

  public function lowerOrder(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'classroom_id'        => 'required|exists:classrooms,id',
      'current_id'          => 'required',
      'current_type'        => 'required|string|in:section,assignment,material,announcement',
      'current_order'       => 'required|numeric',
      'data'                => 'required|array|min:1',
      'data.*.content_type' => 'required|string|in:section,assignment,material,announcement',
      'data.*.id'           => 'required',
      'data.*.order'        => 'required|numeric',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation Errors',
        'errors'  => $validator->errors()
      ], 422);
    }

    $items = $request->data;
    $currentId = $request->current_id;
    $currentType = $request->current_type;
    $currentOrder = $request->current_order;

    $idx = null;

    foreach ($items as $index => $item) {
      if ($item['id'] === $currentId && $item['content_type'] === $currentType) {
        $idx = $index;
        break;
      }
    }

    $nextItem = $items[$idx + 1];

    if ($nextItem && $nextItem['order'] === $currentOrder) {
      foreach ($items as $index => $item) {
        $modelName = Str::studly($item['content_type']);
        $modelClass = "App\\Models\\{$modelName}";

        $condition = $index !== $idx && $index <= $idx + 1;

        if ($condition) {
          $data = $modelClass::find($item['id']);

          $data->update([
            'order' => $item['order'] - 1
          ]);
        }
      }
    } else if ($nextItem && $nextItem['order'] > $currentOrder) {
      $modelNamePrev = Str::studly($nextItem['content_type']);
      $modelClassPrev = "App\\Models\\{$modelNamePrev}";

      $nextData = $modelClassPrev::find($nextItem['id']);
      $difference = $nextItem['order'] - $currentOrder;

      $nextData->update([
        'order' =>  $currentOrder
      ]);

      $modelNameCurr = Str::studly($currentType);
      $modelClassCurr = "App\\Models\\{$modelNameCurr}";

      $currentData = $modelClassCurr::find($currentId);
      $difference = $nextItem['order'] - $currentOrder;

      if ($difference > 1) {
        $currentData->update([
          'order' => $nextItem['order'] - $difference + 1
        ]);
      } else {
        $currentData->update([
          'order' => $nextItem['order']
        ]);
      }
    }

    $classroomController = new ClassroomController();
    $response = $classroomController->show($request->classroom_id);
    $responseData = $response->getData(true);

    if (!$responseData['success']) {
      return response()->json([
        'message' => 'Classroom not found'
      ], 404);
    }

    $classroomData = $responseData['data'];

    return response()->json([
      'success' => true,
      'message' => 'Order lowered successfully',
      'data'    => $classroomData,
    ], 200);
  }

  public function fileDownload(string $id)
  {
    $attachment = Attachment::findOrFail($id);

    if (!Storage::disk('public')->exists($attachment->file_path)) {
      return response()->json([
        'success' => false,
        'message' => 'File fisik tidak ditemukan di server.'
      ], 404);
    }

    return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
  }
}

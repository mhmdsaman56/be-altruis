<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $interactionType = $request->input('interaction_type');
        $reactionType = $request->input('reaction_type');
        $query = Activity::where('user_id', $request->user()->id)->orderBy('created_at', 'desc');
        if ($type) {
            $query->where('type', $type);
        }
        if ($interactionType) {
            $query->where('interaction_type', $interactionType);
        }
        if ($reactionType) {
            $query->where('reaction_type', $reactionType);
        }
        $activities = $query->get();
        return response()->json(['message' => 'List of activities', 'payload' => $activities], 200);
    }
}

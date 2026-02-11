<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Models\Content;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Content::select('id', 'content_type','body', 'user_id', 'created_at')->where('content_type', 'post')->with('reactions')->orderBy('created_at', 'desc')->get();
        $template = [
            'like',
            'dislike',
            'helpful',
            'unhelpful',
            'agree',
            'disagree',
            'upvote',
            'downvote'
        ];
        foreach ($posts as $post) {
            $post->reaction_summary = collect($template)->map(function ($type) use ($post, $request) {
                return [
                    'type' => $type,
                    'count' => $post->reactions->where('type', $type)->count(),
                    'is_active' => $post->reactions->where('type', $type)->where('user_id', $request->user()->id)->isNotEmpty(),
                ];
            });
            unset($post->reactions);
        }




        return response()->json(['message' => 'List of posts', 'payload' => $posts], 200);
    }
    public function store(CreatePostRequest $request)
    {

        $validated = $request->validated();
        try {
            $post = Content::create([
                'content_type' => 'post',
                'body' => $validated['body'],
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['message' => 'Post created successfully', 'payload' => [
                'id' => $post->id,
                'body' => $post->body,
                'user_id' => $post->user_id,
                'created_at' => $post->created_at,
            ]], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Post creation failed', 'error' => $th->getMessage()], 500);
        }
    }

    public function addReaction(Request $request, Content $content)
    {
        $validated = $request->validate([
            'type' => 'required|in:like,dislike,agree,disagree,helpful,unhelpful,upvote,downvote',
        ]);
        $opposites = [
            'like'     => 'dislike',
            'dislike'  => 'like',
            'agree'    => 'disagree',
            'disagree' => 'agree',
            'helpful'   => 'unhelpful',
            'unhelpful' => 'helpful',
            'upvote'   => 'downvote',
            'downvote' => 'upvote',
        ];
        try {
            $exactReaction = Reaction::where('content_id', $content->id)
                ->where('user_id', $request->user()->id)
                ->where('type', $validated['type'])
                ->first();

            if ($exactReaction) {
                $exactReaction->delete();
                return response()->json(['message' => 'Removed', 'payload' => null]);
            }
            $oppositeReaction = Reaction::where('content_id', $content->id)
                ->where('user_id', $request->user()->id)
                ->where('type', $opposites[$validated['type']])
                ->first();

            if ($oppositeReaction) {
                $oppositeReaction->update(
                    ['type' => $validated['type']]
                );

                return response()->json(['message' => 'Reaction updated successfully'], 200);
            }
            $newReaction = Reaction::create([
                'content_id' => $content->id,

                'user_id' => $request->user()->id,
                'type' => $validated['type'],
            ]);

            return response()->json(['message' => 'Reaction added successfully', 'payload' => $newReaction], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to add reaction', 'error' => $th->getMessage()], 500);
        }
    }

    public function addAnswer(Request $request, Content $content)
    {

        $validated = $request->validate([
            'body' => 'required|string',
            'content_type' => 'nullable|in:post,answer,comment,article ',
        ]);
        $validated['content_type'] = $validated['content_type'] ?? 'answer';
        try {
            $answer = $content->children()->create([
                'body' => $validated['body'],
                'content_type' => $validated['content_type'],
                'user_id' => $request->user()->id,
                'parent_id' => $content->id,
                'slug' =>  Str::slug( 'answer-' . uniqid()),
            ]);
            return response()->json(['message' => 'Answer added successfully', 'payload' => $answer], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to add answer', 'error' => $th->getMessage()], 500);
        }
    }
    public function showAllAnswers(Content $content)
    {
   
                
                $answers = $content->children()->with('user:id,name', 'reactions')->get();
        $template = [
            'like',
            'dislike',
            'helpful',
            'unhelpful',
            'agree',
            'disagree',
            'upvote',
            'downvote'
        ];
        foreach ($answers as $answer) {
            $answer->reaction_summary = collect($template)->map(function ($type) use ($answer, $content) {
                return [
                    'type' => $type,
                    'count' => $answer->reactions->where('type', $type)->count(),
                    'is_active' => $answer->reactions->where('type', $type)->where('user_id', $content->user_id)->isNotEmpty(),
                ];
            });
            unset($answer->reactions);
        }




        return response()->json(['message' => 'List of answers', 'payload' => $answers], 200);
    }

    public function show(string $slug)
    {
         $content = Content::with('parent', 'children', 'reactions')->where('slug', $slug)->firstOrFail();
        return response()->json(['message' => 'Content found', 'payload' => $content ], 200);
    }
}

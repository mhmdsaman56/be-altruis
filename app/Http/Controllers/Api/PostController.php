<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePostRequest;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Notification;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Content::select('id', 'content_type','body', 'user_id', 'created_at')->where('content_type', 'post')->with('reactions')
        ->where('user_id','!=', $request->user()->id)
        ->orderBy('created_at', 'desc')->get();
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
        'like' => 'dislike',
        'dislike' => 'like',
        'agree' => 'disagree',
        'disagree' => 'agree',
        'helpful' => 'unhelpful',
        'unhelpful' => 'helpful',
        'upvote' => 'downvote',
        'downvote' => 'upvote',
    ];

    return DB::transaction(function () use ($request, $content, $validated, $opposites) {

        $userId = $request->user()->id;

        $exactReaction = Reaction::where([
            'content_id' => $content->id,
            'user_id' => $userId,
            'type' => $validated['type']
        ])->first();

        if ($exactReaction) {
            $exactReaction->delete();
            Activity::where('user_id', $request->user()->id)
                ->where('content_id', $content->id)
                ->where('interaction_type', 'reaction')
                ->where('reaction_type', $validated['type'])
                ->delete();
             Notification::where('user_id', $content->user_id)
                ->where('content_id', $content->id)
                ->where('reaction_type', $validated['type'])
                ->delete();
            return response()->json(['message' => 'Removed']);
        }

        $oppositeReaction = Reaction::where([
            'content_id' => $content->id,
            'user_id' => $userId,
            'type' => $opposites[$validated['type']]
        ])->first();
                    if ($content->content_type === 'post') {
                $notificationType = 'question';
            } else {
                $notificationType = 'answer';
            }

        if ($oppositeReaction) {
            $oppositeReaction->update([
                'type' => $validated['type']
            ]);
            Notification::create([
                'user_id' => $content->user_id,
                'type' => $notificationType,
                'interaction_type' => 'reaction',
                'reaction_type' => $validated['type'],
                'data' => [
                    'actor' => $request->user()->name,
                    'content_id' => $content->id,
                    'content_body' => Str::limit($content->body, 120),
                    
                ],
            ]);
            Activity::create([
                'user_id' => $request->user()->id,
                'type' => $notificationType,
                'interaction_type' => 'reaction',
                'reaction_type' => $validated['type'],
                'content_id' => $content->id,
                
                'data' => [
                    'actor' => $request->user()->name,
                    'content_body' => Str::limit($content->body, 120),
                    
                ],
            ]);
            return response()->json(['message' => 'Reaction switched']);
        }

        $newReaction = Reaction::create([
            'content_id' => $content->id,
            'user_id' => $userId,
            'type' => $validated['type'],
        ]);

        Notification::create([
            'user_id' => $content->user_id,
            'type' => $notificationType,
            'interaction_type' => 'reaction',
            'reaction_type' => $validated['type'],
            'data' => [
                'actor' => $request->user()->name,
                'content_id' => $content->id,
                'content_body' => Str::limit($content->body, 120),  
            ],
        ]);
        Activity::create([
            'user_id' => $request->user()->id,
            'type' => $notificationType,
            'interaction_type' => 'reaction',
            'reaction_type' => $validated['type'],
            'content_id' => $content->id,
            'data' => [
                'actor' => $request->user()->name,
                'content_body' => Str::limit($content->body, 120),
                
            ],
        ]);
        return response()->json([
            'message' => 'Added',
            'payload' => $newReaction
        ], 201);
    });
}


    public function addAnswer(Request $request, Content $content)
    {

        $validated = $request->validate([
            'body' => 'required|string',
            'content_type' => 'nullable|in:post,answer,comment,article ',
        ]);
        $validated['content_type'] = $validated['content_type'] ?? 'answer';
        DB::beginTransaction();
        try {
            $answer = $content->children()->create([
                'body' => $validated['body'],
                'content_type' => $validated['content_type'],
                'user_id' => $request->user()->id,
                'parent_id' => $content->id,
                'slug' =>  Str::slug( 'answer-' . uniqid()),
            ]);
            if ($answer->content_type === 'answer') {
                Notification::create([
                    'user_id' => $content->user_id,
                    'type' => 'question',
                    'interaction_type' => 'answer',
                    'data' => [
                        'actor' => $request->user()->name,
                        'content_id' => $answer->id,
                        'content_body' => Str::limit($answer->body, 120),
                    ],
                ]);
                Activity::create([
                    'user_id' => $request->user()->id,
                    'type' => 'question',
                    'interaction_type' => 'answer',
                    'content_id' => $answer->id,
                    'data' => [
                        'actor' => $request->user()->name,
                        'content_body' => Str::limit($answer->body, 120),
                    ],
                ]);
            }
            if ($answer->content_type === 'comment') {
                Notification::create([
                    'user_id' => $content->user_id,
                    'type' => 'answer',
                    'interaction_type' => 'answer',
                    'data' => [
                        'actor' => $request->user()->name,
                        'content_id' => $answer->id,
                        'content_body' => Str::limit($answer->body, 120),
                        
                    ],
                ]);

                Activity::create([
                    'user_id' => $request->user()->id,
                    'type' => 'answer',
                    'interaction_type' => 'answer',
                    'content_id' => $answer->id,
                    'data' => [
                        'actor' => $request->user()->name,
                        'content_body' => Str::limit($answer->body, 120),
                        
                    ],
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Answer added successfully', 'payload' => $answer], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add answer', 'error' => $th->getMessage()], 500);
        }
    }
    public function showAllAnswers(Request $request,    Content $content)
    {
   
                
                $answers = $content->children()->with('user:id,name', 'reactions')->get();
      
        foreach ($answers as $answer) {
            $answer->reaction_summary = $this->getReactionSummary($answer,  $request->user()->id);
            unset($answer->reactions);
        }




        return response()->json(['message' => 'List of answers', 'payload' => $answers], 200);
    }

    public function show(Request $request, string $slug)
    {
         $content = Content::with('parent.reactions', 'children', 'reactions')->where('slug', $slug)->firstOrFail();

        
        return response()->json(['message' => 'Content found', 'payload' => [
            'id' => $content->parent->id,
            'body' => $content->parent->body,
            'reaction_summary' => $this->getReactionSummary($content->parent, $request->user()->id),
            'above_answers' => [],
            'primary_answer'=> [
                'id' => $content->id,
                'body' => $content->body,
                'slug' => $content->slug,
                'user' => [
                    'id' => $content->user->id,
                    'name' => $content->user->name,
                ],
                'reaction_summary' => $this->getReactionSummary($content, $request->user()->id)],
            'below_answers' => [],
            ]], 200);
    }

    public function getReactionSummary($data, $user_id){
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
            return collect($template)->map(function ($type) use ($data, $user_id) {
                return [
                    'type' => $type,
                    'count' => $data->reactions->where('type', $type)->count(),
                    'is_active' => $data->reactions->where('type', $type)->where('user_id', $user_id)->isNotEmpty(),
                ];
            });

    }
}

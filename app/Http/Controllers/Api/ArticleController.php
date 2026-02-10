<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateArticleRequest;
use App\Models\Article;
use App\Models\Content;

use Illuminate\Support\Str;

class ArticleController extends Controller
{
    
    public function index()
    {
        //
        $articles = Content::where('content_type', 'article')->get();
        return response()->json(['message' => 'Articles retrieved successfully', 'payload' => $articles], 200);
    }

    public function show($slug)
    {
        //
        $article = Content::where('slug', $slug)->where('content_type', 'article')->first();
        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }
        return response()->json(['message' => 'Article retrieved successfully', 'payload' => $article], 200);
    }

    public function store(CreateArticleRequest $request)
    {
        //
        $validated = $request->validated();
        try {
            
            $article = Content::create([
                'content_type' => 'article',
                'title' => $validated['title'],
                'slug' => Str::slug($validated['title']) . '-' . uniqid(),
                'body' => $validated['content'],
                'user_id' => $request->user()->id,
            ]);
            return response()->json(['message' => 'Article created successfully', 'payload' => $article], 201);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json(['message' => 'Article creation failed', 'error' => $th->getMessage()], 500);
        }



    }
}

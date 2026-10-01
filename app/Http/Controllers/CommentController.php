<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    /**
     * Authorization (GradePolicy::comment) and validation live in CommentRequest.
     */
    public function store(CommentRequest $request, Grade $grade): RedirectResponse
    {
        $comment = new Comment($request->validated());
        $comment->author()->associate($request->user());
        $grade->comments()->save($comment);

        return back();
    }

    /**
     * Authorization (CommentPolicy::update) is the `can:update,comment` route middleware, validation lives in UpdateCommentRequest.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validated());

        return back();
    }
}

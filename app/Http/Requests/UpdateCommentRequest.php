<?php

namespace App\Http\Requests;

class UpdateCommentRequest extends CommentRequest
{
    /**
     * Authorization (CommentPolicy::update) is done by the `can:update,comment`
     * route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }
}

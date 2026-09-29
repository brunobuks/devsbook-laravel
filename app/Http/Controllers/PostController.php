<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
   private ?Authenticatable $loggedUser;

    public function __construct()
    {
        $this->loggedUser = Auth::user();
    }

    public function like(int $id)
    {
        $log = ['message' => ''];

        $postExist = Post::find($id);
        if ($postExist) {
            $isLiked = PostLike::where('id_post', $id)
                        ->where('id_user', $this->loggedUser->id)
                        ->count();

            if($isLiked > 0) {
                $liked = PostLike::where('id_post', $id)
                        ->where('id_user', $this->loggedUser->id)
                        ->first();

                $liked->delete();

                $log['isLiked'] = false;
            } else {
                $newPostLike             = new PostLike();
                $newPostLike->id_post    = $id;
                $newPostLike->id_user    = $this->loggedUser->id;
                $newPostLike->created_at = date('Y-m-d H:i:s');
                $newPostLike->save();

                $log['isLiked'] = true;
            }

            $likeCount = PostLike::where('id_post', $id)->count();
            $log['likeCount'] = $likeCount;
        } else {
            $log['message'] = 'Post does not exist.';
            return $log;
        }

        return $log;
    }

    public function comment(Request $request, array $id)
    {
        $log = ['message' => ''];

        $comment = $request->input('txt');

        $postExist = Post::find($id);
        if ($postExist) {
            if($comment) {
                $newComment = new PostComment();
                $newComment->id_post    = $id;
                $newComment->id_user    = $this->loggedUser->id;
                $newComment->created_at = date('Y-m-d H:i:S');
                $newComment->body       = $comment;
                $newComment->save();
            } else {
                $log['message'] = 'No message sent.';
                return $log;
            }
        } else {
            $log['message'] = 'Post not found.';
            return $log;
        }

        return $log;
    }
}

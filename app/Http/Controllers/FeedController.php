<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use Throwable;

class FeedController extends Controller
{
    private ?Authenticatable $loggedUser;
    private const LIMIT = 2;

    public function __construct()
    {
        $this->loggedUser = Auth::user();
    }

    public function create(Request $request)
    {
        $log = ['message' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $type  = $request->input('type');
        $body  = $request->input('body');
        $photo = $request->file('photo');

        if ($type) {
            switch($type) {
                case 'text':
                    if(!$body) {
                        $log['message'] = 'No text sent.';
                        return $log;
                    }
                break;

                case 'photo':
                    if(!$photo) {
                        $log['message'] = 'File not sent.';
                        return $log;
                    }
                    
                    if(!in_array($photo->getClientMimeType(), $allowedTypes)) {
                        $log['message'] = 'File type not supported.';
                        return $log;
                    }
                    
                    $fileName = md5(time() . rand(0, 9999)) . '.jpg';
                    $destinationPath = public_path('/media/uploads');

                    $manager = ImageManager::usingDriver(Driver::class);

                    try {
                        $manager->decode($photo->path())
                                ->scale(800)
                                ->save($destinationPath . '/' . $fileName);
                    } catch(Throwable $e) {
                        Log::error('No image saved.', [
                            'error'   => $e->getMessage(),
                            'user_id' => $this->loggedUser->id
                        ]);

                        return ['message' => 'Error to save image.'];
                    }

                    $body = $fileName;
                break;

                default:
                    $log['message'] = 'Post type not supported.';
                    return $log;
            }

            if ($body) {
                $newPost = new Post();
                $newPost->id_user = $this->loggedUser->id;
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }
        } else {
            $log['message'] = 'No data sent.';
            return $log;
        }

        return $log;
    }

    public function userFeed(Request $request, $id = null)
    {
        $id = $id ?: $this->loggedUser->id;

        $page    = max(0, $request->integer('page'));
        $perPage = self::LIMIT;

        $postList = Post::where('id_user', $id)
                    ->orderBy('created_at', 'desc')
                    ->offset($page * $perPage)
                    ->limit($perPage)
                    ->get();

        $total     = Post::where('id_user', $id)->count();
        $pageCount = ceil($total / $perPage);

        $posts = $this->postListToObject($postList, $this->loggedUser->id);

        return [
            'posts'       => $posts,
            'pageCount'   => $pageCount,
            'currentPage' => $page
        ];
    }

    public function read(Request $request)
    {
        $page    = max(0, $request->integer('page'));
        $perPage = self::LIMIT;

        $users = UserRelation::where('user_from', $this->loggedUser->id)->pluck('user_to');
        $users->push($this->loggedUser->id);

        $postList = Post::whereIn('id_user', $users)
                    ->orderBy('created_at', 'desc')
                    ->offset($page * $perPage)
                    ->limit($perPage)
                    ->get();
        
        $total     = Post::whereIn('id_user', $users)->count();
        $pageCount = ceil($total / $perPage);

        $posts = $this->postListToObject($postList, $this->loggedUser->id);

        return [
            'posts'       => $posts,
            'pageCount'   => $pageCount,
            'currentPage' => $page
        ];
    }

    public function userPhotos(Request $request, $id = null)
    {
        $id = $id ?: $this->loggedUser->id;

        $page    = max(0, $request->integer('page'));
        $perPage = self::LIMIT;

        $postList = Post::where('id_user', $id)
                    ->where('type', 'photo')
                    ->orderBy('created_at', 'desc')
                    ->offset($page * $perPage)
                    ->limit($perPage)
                    ->get();

        $total = Post::where('id_user', $id)
                    ->where('type', 'photo')
                    ->count();
        $pageCount = ceil($total / $perPage);

        $posts = $this->postListToObject($postList, $this->loggedUser->id);

        $posts->transform(function ($post) {
            $post['body'] = url('media/uploads/' . $post['body']);
            return $post;
        });

        return [
            'posts'       => $posts,
            'pageCount'   => $pageCount,
            'currentPage' => $page
        ];
    }

    private function postListToObject(Collection $postList, int $loggedId)
    {
        foreach($postList as $postKey => $postItem) {
            if($postItem['id_user'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            $userInfo = User::find($postItem['id_user']);
            $userInfo['avatar'] = url('media/avatar/' . $userInfo['avatar']);
            $userInfo['cover']  = url('media/cover/' . $userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])
                    ->where('id_user', $loggedId)
                    ->count();

            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;

            $comments = PostComment::where('id_post', $postItem['id'])->get();

            foreach($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);
                $user['avatar'] = url('media/avatar/' . $user['avatar']);
                $user['cover']  = url('media/cover/' . $user['cover']);
                $comments[$commentKey]['user'] = $user;
            }

            $postList[$postKey]['comments'] = $comments;
        }

        return $postList;
    }
}

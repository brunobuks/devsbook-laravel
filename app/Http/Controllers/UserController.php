<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\Post;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use function Laravel\Prompts\info;

class UserController extends Controller
{
    private ?User $loggedUser;

    public function __construct()
    {
        /** @var ?User $user */
        $user = Auth::user();
        $this->loggedUser = $user;
    }

    public function update(Request $request)
    {
        $log = ['message' => ''];

        $name            = $request->input('name');
        $email           = $request->input('email');
        $birthdate       = $request->input('birthdate');
        $city            = $request->input('city');
        $work            = $request->input('work');
        $password        = $request->input('password');
        $passwordConfirm = $request->input('password_confirm');

        $user = $this->loggedUser;

        if ($name) {
            $user->name = $name;
        }

        if ($email) {
            if ($email != $user->email) {
                $emailExist = User::where('email', $email)->count();
                if ($emailExist === 0) {
                    $user->email = $email;
                } else {
                    $log['message'] = 'Email already exist';
                    return $log;
                }
            }
        }

        if ($birthdate) {
            $format = 'Y-m-d';
            $date   = date_create_from_format($format, $birthdate);

            if ($date === false || $date->format($format) !== $birthdate) {
                $log['message'] = 'Invalid birthdate';
                return $log;
            }

            $user->birthdate = $birthdate;
        }

        if ($city) {
            $user->city = $city;
        }

        if ($work) {
            $user->work = $work;
        }

        if ($password && $passwordConfirm) {
            if ($password === $passwordConfirm) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;
            } else {
                $log['message'] = 'Password does not match';
                return $log;
            }
        }

        $user->save();

        return $log;
    }

    public function updateAvatar(Request $request)
    {
        $log = ['message' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $fileName = md5(time() . rand(0, 9999)) . '.jpg';

                $destinationPath = public_path('/media/avatar');

                $manager = ImageManager::usingDriver(Driver::class);
                $img = $manager->decode($image->path())
                    ->cover(200, 200)
                    ->save($destinationPath . '/' . $fileName);

                $user = $this->loggedUser;
                $user->avatar = $fileName;
                $user->save();

                $log['url'] = url('/media/avatar/' . $fileName);

            } else {
                $log = ['message' => 'File type not supported'];
                return $log;
            }
        } else {
            $log = ['message' => 'No file sent'];
            return $log;
        }

        return $log;
    }

    public function updateCover(Request $request)
    {
        $log = ['message' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');

        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $fileName = md5(time() . rand(0, 9999)) . '.jpg';

                $destinationPath = public_path('/media/cover');

                $manager = ImageManager::usingDriver(Driver::class);
                $img = $manager->decode($image->path())
                    ->cover(850, 310)
                    ->save($destinationPath . '/' . $fileName);

                $user = $this->loggedUser;
                $user->cover = $fileName;
                $user->save();

                $log['url'] = url('/media/cover/' . $fileName);

            } else {
                $log = ['message' => 'File type not supported'];
                return $log;
            }
        } else {
            $log = ['message' => 'No file sent'];
            return $log;
        }

        return $log;
    }

    public function read($id = false)
    {
       $log = ['message' => ''];

       if ($id) {
        $info = User::find($id);
        if(!$info) {
            $log['message'] = 'No user found.';

            return $log;
        } 
       } else {
        $info = $this->loggedUser;
       }

       $info['avatar'] = url('media/avatar/' . $info['avatar']);
       $info['cover']  = url('media/cover/' . $info['cover']);

       $info['me'] = ($info['id'] == $this->loggedUser['id']) 
                    ? true
                    : false;

        $dateFrom    = new DateTime($info['birthdate']);
        $dateTo      = new DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

       $log['data'] = $info;

       $info['followers'] = UserRelation::where('user_to', $info['id'])->count();
       $info['following'] = UserRelation::where('user_from', $info['id'])->count();

       $info['photos'] = Post::where('id_user', $info['id'])
                        ->where('type', 'photo')
                        ->count();

       $hasRelation = UserRelation::where('user_from', $this->loggedUser['id'])
                        ->where('user_to', $info['id'])
                        ->count();
       $info['isFollowing'] = ($hasRelation > 0)
                        ? true
                        : false;

       return $log;
    }

    public function follow(string $id)
    {
        $log = ['message' => ''];

        if ($id == $this->loggedUser->id) {
            $log['message'] = 'User cannot follow their self';
            return $log;
        }

        $userExist = User::find($id);
        if($userExist) {
            $relation = UserRelation::where('user_from', $this->loggedUser->id)
                                    ->where('user_to', $id)
                                    ->first();
            
            if($relation) {
                $relation->delete();
                info('follow');
            } else {
                $newRelation            = new UserRelation();
                $newRelation->user_from = $this->loggedUser->id;
                $newRelation->user_to   = $id;
                $newRelation->save();
            }
        } else {
            $log['message'] = 'User not found.';
            return $log;
        }

        return $log;
    }

    public function followers($id)
    {
        $log = ['message' => ''];

        $userExist = User::find($id);
        if ($userExist) {
            $followers = UserRelation::where('user_to', $id)->get();
            $following = UserRelation::where('user_from', $id)->get();

            $log['followers'] = [];
            $log['following'] = [];

            foreach($followers as $follower) {
                $user = User::find($follower['user_from']);
                $log['followers'][] = [
                    'id'     => $user['id'],
                    'name'   => $user['name'],
                    'avatar' => url('media/avatar/' . $user['avatar'])
                ];
            }

            foreach($following as $follower) {
                $user = User::find($follower['user_to']);
                $log['following'][] = [
                    'id'     => $user['id'],
                    'name'   => $user['name'],
                    'avatar' => url('media/avatar/' . $user['avatar'])
                ];
            }
        } else {
            $log['message'] = 'User not found.';
            return $log;
        }

        return $log;
    }
}

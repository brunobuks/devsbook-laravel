<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    private ?Authenticatable $loggedUser;

    public function __construct()
    {
        $this->loggedUser = Auth::user();
    }

    public function search(Request $request)
    {
        $log = ['message' => '', 'users' => []];

        $search = $request->input('txt');

        if ($search) {
            $userList = User::where('name', 'like', '%' . $search . '%')->get();

            foreach($userList as $userItem) {
                $log['users'][] = [
                    'id'     => $userItem['id'],
                    'name'   => $userItem['name'],
                    'avatar' => url('media/avatar/' . $userItem['avatar'])
                ];
            }
        } else {
            $log['message'] = 'Type something...';
            return $log;
        }

        return $log;
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Core\Controller;

class UserController extends Controller
{
    public function show(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->json(['error' => 'User not found']);
        }
        
        return $this->json($user);
    }
}
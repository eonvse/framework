<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Http\HttpError;

use App\Models\User;
use Core\Controller;

class UserController extends Controller
{
    public function show(int $id)
    {
        $user = User::find($id);
        if (!$user) {
            throw HttpError::notFound("User not found");
        }
        
        return $this->json($user);
    }
}
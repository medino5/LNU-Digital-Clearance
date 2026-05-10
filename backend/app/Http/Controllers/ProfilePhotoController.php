<?php

namespace App\Http\Controllers;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ProfilePhotoController extends Controller
{
    public function show(string $fileName)
    {
        abort_if(! preg_match('/^user-\d+-[A-Fa-f0-9-]+\.(jpe?g|png|webp)$/', $fileName), Response::HTTP_NOT_FOUND);

        $user = User::query()
            ->where('profile_photo_path', $fileName)
            ->first();

        abort_unless($user?->profile_photo_content, Response::HTTP_NOT_FOUND);

        return response(base64_decode($user->profile_photo_content), Response::HTTP_OK)
            ->header('Content-Type', $user->profile_photo_mime_type ?: 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }
}

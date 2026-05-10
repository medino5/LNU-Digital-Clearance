<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProfilePhotoStorage
{
    public function storeForUser(User $user, UploadedFile $file): string
    {
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');
        $fileName = sprintf('user-%d-%s.%s', $user->id, Str::uuid(), $extension);

        $user->forceFill([
            'profile_photo_path' => $fileName,
            'profile_photo_mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'profile_photo_content' => base64_encode(file_get_contents($file->getRealPath()) ?: ''),
        ])->save();

        return $fileName;
    }
}

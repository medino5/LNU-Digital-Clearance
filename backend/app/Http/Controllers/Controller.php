<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function validateForm(
        Request $request,
        string $errorBag,
        array $rules,
        string $redirectTo,
        array $messages = [],
        array $attributes = [],
    ): array {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw (new ValidationException($validator))
                ->errorBag($errorBag)
                ->redirectTo($redirectTo);
        }

        return $validator->validated();
    }

    protected function formValidationException(
        array $messages,
        string $errorBag,
        string $redirectTo,
    ): ValidationException {
        return ValidationException::withMessages($messages)
            ->errorBag($errorBag)
            ->redirectTo($redirectTo);
    }

    protected function redirectWithMessage(
        string $redirectTo,
        string $flashKey,
        string $message,
    ): RedirectResponse {
        return redirect()->to($redirectTo)->with($flashKey, $message);
    }

    protected function redirectWithInputAndMessage(
        Request $request,
        string $redirectTo,
        string $flashKey,
        string $message,
        array $extraInput = [],
    ): RedirectResponse {
        return redirect()->to($redirectTo)
            ->with($flashKey, $message)
            ->withInput(array_merge(
                $request->except(['password', 'password_confirmation']),
                $extraInput,
            ));
    }

    protected function adminSectionUrl(string $section): string
    {
        return route('admin.dashboard') . '#' . $section;
    }

    protected function officeDashboardUrl(): string
    {
        return route('office.dashboard');
    }
}

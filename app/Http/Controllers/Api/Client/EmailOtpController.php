<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Services\Auth\EmailOtpService;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EmailOtpController extends ClientApiController
{
    public function __construct(
        private ValidationFactory $validation,
        private EmailOtpService $emailOtpService,
    ) {
        parent::__construct();
    }

    /**
     * Enable email OTP for the authenticated user's account.
     * Requires the current password. Email OTP cannot be enabled
     * if TOTP (authenticator app) is already active.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validation->make($request->all(), [
            'password' => ['required', 'string'],
        ])->validate();

        if (!password_verify($data['password'], $request->user()->password)) {
            throw new BadRequestHttpException('The password provided is incorrect.');
        }

        if ($request->user()->use_totp) {
            throw new BadRequestHttpException(
                'Two-factor authentication (TOTP) is already enabled. Disable it before enabling email OTP.'
            );
        }

        if ($request->user()->use_email_otp) {
            throw new BadRequestHttpException('Email OTP is already enabled on this account.');
        }

        $request->user()->update(['use_email_otp' => true]);

        Activity::event('user:email-otp.create')->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Disable email OTP for the authenticated user's account.
     * Requires the current password.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function delete(Request $request): JsonResponse
    {
        $data = $this->validation->make($request->all(), [
            'password' => ['required', 'string'],
        ])->validate();

        if (!password_verify($data['password'], $request->user()->password)) {
            throw new BadRequestHttpException('The password provided is incorrect.');
        }

        if (!$request->user()->use_email_otp) {
            throw new BadRequestHttpException('Email OTP is not enabled on this account.');
        }

        $request->user()->update(['use_email_otp' => false]);

        Activity::event('user:email-otp.delete')->log();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}

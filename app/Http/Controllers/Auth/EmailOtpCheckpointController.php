<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Pterodactyl\Models\User;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Auth\EmailOtpService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Http\Requests\Auth\EmailOtpCheckpointRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class EmailOtpCheckpointController extends AbstractLoginController
{
    public function __construct(
        private EmailOtpService $emailOtpService,
        private ValidationFactory $validation,
    ) {
        parent::__construct();
    }

    /**
     * Handle verification of the email OTP code after login.
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function __invoke(EmailOtpCheckpointRequest $request): JsonResponse
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->sendLockoutResponse($request);
        }

        $details = $request->session()->get('auth_confirmation_token');

        if (!$this->hasValidSessionData($details) || ($details['type'] ?? '') !== 'email_otp') {
            throw new DisplayException('The authentication token has expired. Please refresh the page and try again.');
        }

        if (!hash_equals($request->input('confirmation_token') ?? '', $details['token_value'])) {
            $this->incrementLoginAttempts($request);

            throw new DisplayException('The confirmation token provided is invalid.');
        }

        try {
            /** @var User $user */
            $user = User::query()->findOrFail($details['user_id']);
        } catch (ModelNotFoundException) {
            throw new DisplayException('The authentication token has expired. Please refresh the page and try again.');
        }

        if (!$this->emailOtpService->verify($user, $request->input('otp_code'))) {
            $this->incrementLoginAttempts($request);
            $this->fireFailedLoginEvent($user);

            throw new DisplayException('The verification code provided is invalid or has expired.');
        }

        return $this->sendLoginResponse($user, $request);
    }

    /**
     * Validates the session data structure and expiry for the email OTP flow.
     */
    private function hasValidSessionData(?array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $validator = $this->validation->make($data, [
            'user_id'     => 'required|integer|min:1',
            'token_value' => 'required|string',
            'expires_at'  => 'required',
            'type'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return false;
        }

        if (!$data['expires_at'] instanceof CarbonInterface) {
            return false;
        }

        return !$data['expires_at']->isBefore(CarbonImmutable::now());
    }
}

import http from '@/api/http';
import { LoginResponse } from '@/api/auth/login';

export default (token: string, otpCode: string): Promise<LoginResponse> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/login/email-otp-checkpoint', {
            confirmation_token: token,
            otp_code: otpCode,
        })
            .then((response) =>
                resolve({
                    complete: response.data.data.complete,
                    intended: response.data.data.intended || undefined,
                })
            )
            .catch(reject);
    });
};

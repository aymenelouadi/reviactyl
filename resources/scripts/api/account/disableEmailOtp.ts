import http from '@/api/http';

export default (password: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/email-otp/disable', { password })
            .then(() => resolve())
            .catch(reject);
    });
};

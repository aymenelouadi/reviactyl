import React, { useState } from 'react';
import { useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { useFlashKey } from '@/plugins/useFlash';
import enableEmailOtp from '@/api/account/enableEmailOtp';
import disableEmailOtp from '@/api/account/disableEmailOtp';
import Field from '@/components/elements/Field';
import { Formik, FormikHelpers, FormikValues } from 'formik';
import { object, string } from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface FormValues {
    password: string;
}

export default () => {
    const [action, setAction] = useState<'enable' | 'disable' | null>(null);
    const isEnabled = useStoreState((state: ApplicationStore) => state.user.data!.useEmailOtp);
    const { clearFlashes, clearAndAddHttpError, addError } = useFlashKey('account:email-otp');
    const updateUserData = useStoreActions((actions: ApplicationStore) => actions.user.updateUserData);

    const onSubmit = ({ password }: FormValues, { setSubmitting, resetForm }: FormikHelpers<FormValues>) => {
        clearFlashes();

        const request = action === 'enable' ? enableEmailOtp(password) : disableEmailOtp(password);

        request
            .then(() => {
                updateUserData({ useEmailOtp: action === 'enable' });
                resetForm();
                setAction(null);
                setSubmitting(false);
            })
            .catch((error: Error) => {
                clearAndAddHttpError(error);
                setSubmitting(false);
            });
    };

    if (action === null) {
        return (
            <div>
                <p css={tw`text-sm`}>
                    {isEnabled
                        ? 'Email OTP is currently enabled. A verification code will be sent to your email at each login.'
                        : 'Email OTP is currently disabled. Enable it to receive a one-time code by email at each login.'}
                </p>
                <div css={tw`mt-6`}>
                    {isEnabled ? (
                        <Button.Danger onClick={() => setAction('disable')}>Disable Email OTP</Button.Danger>
                    ) : (
                        <Button onClick={() => setAction('enable')}>Enable Email OTP</Button>
                    )}
                </div>
            </div>
        );
    }

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{ password: '' }}
            validationSchema={object().shape({
                password: string().min(1, 'Your current password is required.').required(),
            })}
        >
            {({ isSubmitting, handleSubmit }: { isSubmitting: boolean; handleSubmit: () => void }) => (
                <div css={tw`relative`}>
                    <SpinnerOverlay visible={isSubmitting} />
                    <p css={tw`text-sm mb-4`}>
                        {action === 'enable'
                            ? 'Enter your current password to enable Email OTP. You cannot enable this while TOTP is active.'
                            : 'Enter your current password to disable Email OTP.'}
                    </p>
                    <Field
                        type={'password'}
                        name={'password'}
                        title={'Current Password'}
                        autoComplete={'current-password'}
                        autoFocus
                    />
                    <div css={tw`mt-4 flex gap-x-2`}>
                        <Button type={'button'} onClick={() => { clearFlashes(); setAction(null); }}>
                            Cancel
                        </Button>
                        {action === 'enable' ? (
                            <Button onClick={() => handleSubmit()}>Confirm Enable</Button>
                        ) : (
                            <Button.Danger onClick={() => handleSubmit()}>Confirm Disable</Button.Danger>
                        )}
                    </div>
                </div>
            )}
        </Formik>
    );
};

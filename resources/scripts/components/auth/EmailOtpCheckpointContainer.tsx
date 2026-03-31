import React from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import emailOtpCheckpoint from '@/api/auth/emailOtpCheckpoint';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { ActionCreator } from 'easy-peasy';
import { StaticContext } from 'react-router';
import { useFormikContext, withFormik } from 'formik';
import useFlash from '@/plugins/useFlash';
import { FlashStore } from '@/state/flashes';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import { MailIcon } from '@heroicons/react/solid';

interface Values {
    otpCode: string;
}

type OwnProps = RouteComponentProps<Record<string, string | undefined>, StaticContext, { token?: string }>;

type Props = OwnProps & {
    clearAndAddHttpError: ActionCreator<FlashStore['clearAndAddHttpError']['payload']>;
};

const EmailOtpCheckpointContainer = () => {
    const { isSubmitting } = useFormikContext<Values>();

    return (
        <LoginFormContainer title={'Email Verification'} css={tw`w-full flex`}>
            <p css={tw`text-sm text-gray-400 text-center mb-4`}>
                A 6-digit verification code has been sent to your email address. Enter it below to continue.
            </p>
            <div css={tw`mt-1`}>
                <Field
                    icon={MailIcon}
                    name={'otpCode'}
                    title={'Verification Code'}
                    description={'Enter the 6-digit code sent to your email'}
                    type={'text'}
                    inputMode={'numeric'}
                    autoComplete={'one-time-code'}
                    maxLength={6}
                    autoFocus
                />
            </div>
            <div css={tw`mt-4`}>
                <Button css={tw`w-full !py-3`} type={'submit'} disabled={isSubmitting}>
                    Verify
                </Button>
            </div>
            <div css={tw`mt-3 text-center`}>
                <Link
                    to={'/auth/login'}
                    css={tw`text-sm text-reviactyl/80 tracking-wide no-underline hover:text-reviactyl/50`}
                >
                    Return to Login
                </Link>
            </div>
        </LoginFormContainer>
    );
};

const EnhancedForm = withFormik<Props, Values>({
    handleSubmit: ({ otpCode }, { setSubmitting, props: { clearAndAddHttpError, location } }) => {
        emailOtpCheckpoint(location.state?.token || '', otpCode)
            .then((response) => {
                if (response.complete) {
                    // @ts-expect-error this is valid
                    window.location = response.intended || '/';
                    return;
                }
                setSubmitting(false);
            })
            .catch((error) => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    },

    mapPropsToValues: () => ({
        otpCode: '',
    }),
})(EmailOtpCheckpointContainer);

export default ({ history, location, ...props }: OwnProps) => {
    const { clearAndAddHttpError } = useFlash();

    if (!location.state?.token) {
        history.replace('/auth/login');
        return null;
    }

    return (
        <EnhancedForm clearAndAddHttpError={clearAndAddHttpError} history={history} location={location} {...props} />
    );
};

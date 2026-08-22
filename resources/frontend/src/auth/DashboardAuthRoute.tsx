import {Navigate, Outlet, useNavigate} from "react-router-dom";
import {useLocation} from "react-router";
import {useDispatch, useSelector} from "react-redux";
import type {RootState} from "../store/store";
import { consumeImpersonationPayload, setAccessToken } from "../ApiClient";
import { setIsAuth, setUser } from "../store/slices/userSlice";
import { useEffect, useState } from "react";

function DashboardAuthRoute() {
    const isAuth = useSelector((state: RootState) => state.auth?.isAuth);
    const authChecked = useSelector((state: RootState) => state.auth?.authChecked);
    const user = useSelector((state: RootState) => state.auth?.user);
    const location = useLocation()
    const dispatch = useDispatch()
    const navigate = useNavigate()
    
    const [isVerifying, setIsVerifying] = useState(false);

    const searchParams = new URLSearchParams(location.search);
    const payloadParam = searchParams.get('payload');
    const impersonateParam = searchParams.get('impersonate');

    // CRITICAL: We must detect the payload IMMEDIATELY on render
    const hasInitialPayload = !!payloadParam && impersonateParam === 'true';

    useEffect(() => {
        if (hasInitialPayload && !isVerifying) {
            setIsVerifying(true);
            
            const handleSuccess = (data: any) => {
                const { user: userData, access_token } = data;
                
                if (userData.is_agent) {
                    setAccessToken(access_token.plain_token, access_token.expires_at);
                    
                    dispatch(setUser(userData));
                    dispatch(setIsAuth(true));

                    // Clean URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('payload');
                    url.searchParams.delete('impersonate');
                    window.history.replaceState({}, document.title, url.toString());

                    setTimeout(() => {
                        setIsVerifying(false);
                        navigate('/dashboard', { replace: true });
                    }, 500);
                } else {
                    setIsVerifying(false);
                    navigate('/', { replace: true });
                }
            };

            consumeImpersonationPayload(payloadParam)
                .then(response => handleSuccess(response.data.data))
                .catch(() => {
                    setIsVerifying(false);
                });
        }
    }, [hasInitialPayload, isVerifying, payloadParam, dispatch, navigate, location.pathname]);

    if (hasInitialPayload || isVerifying) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-slate-50 w-screen">
                <div className="text-center">
                    <div className="h-12 w-12 animate-spin rounded-full border-4 border-[#B12C2E] border-t-transparent mx-auto"></div>
                    <p className="mt-4 text-sm font-medium text-slate-600 font-arabic">جاري التحقق من الهوية...</p>
                    <p className="mt-2 text-xs text-slate-400 uppercase tracking-widest">Impersonation in progress</p>
                </div>
            </div>
        );
    }

    if (!authChecked) {
        return null;
    }

    return (
        isAuth && user?.is_agent ? <Outlet/> 
        : !isAuth ? <Navigate to={`/login`} state={{ from: location }} replace/>
        : <Navigate to={`/`} state={{ from: location }} replace/>
    );
}

export default DashboardAuthRoute;

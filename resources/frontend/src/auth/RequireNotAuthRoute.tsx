import {Navigate, Outlet} from "react-router-dom";
import {useLocation} from "react-router";
import {useSelector} from "react-redux";
import type {RootState} from "../store/store";

function RequireNotAuthRoute() {
    const isAuth = useSelector((state: RootState) => state.auth?.isAuth);
    const authChecked = useSelector((state: RootState) => state.auth?.authChecked);
    const user = useSelector((state: RootState) => state.auth?.user);
    const location = useLocation()

    if (!authChecked) {
        return null;
    }

    if (!isAuth) {
        return <Outlet/>;
    }

    const redirectPath = user?.is_agent ? '/dashboard' : '/';

    return (
        <Navigate to={redirectPath} state={{ from: location }} replace/>
    )
}

export default RequireNotAuthRoute

import {Navigate, Outlet} from "react-router-dom";
import {useLocation} from "react-router";
import {useSelector} from "react-redux";
import type {RootState} from "../store/store";

function RequireAuthRoute() {
    const isAuth = useSelector((state: RootState) => state.auth.isAuth);
    const authChecked = useSelector((state: RootState) => state.auth.authChecked);
    const location = useLocation()

    if (!authChecked) {
        return null;
    }

    return (
        isAuth ? <Outlet/> 
        : <Navigate to={`/`} state={{ from: location }} replace/>
    )
}

export default RequireAuthRoute

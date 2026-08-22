import { Navigate, Outlet } from 'react-router-dom';
import { useLocation } from 'react-router';
import { useSelector } from 'react-redux';
import type { RootState } from '../store/store';

function PreventAgentCheckoutRoute() {
  const isAuth = useSelector((state: RootState) => state.auth?.isAuth);
  const authChecked = useSelector((state: RootState) => state.auth?.authChecked);
  const user = useSelector((state: RootState) => state.auth?.user);
  const cartItems = useSelector((state: RootState) => state.cart?.items);
  const location = useLocation();

  if (!authChecked) {
    return null;
  }

  if ((isAuth && user?.is_agent) || !cartItems || cartItems.length === 0) {
    return <Navigate to="/cart" state={{ from: location }} replace />;
  }

  return <Outlet />;
}

export default PreventAgentCheckoutRoute;

import React, { useEffect, useState, useCallback } from 'react';
import MaxWidthWrapper from '../../components/MaxWidthWrapper';
import { FiUser, FiMail, FiCalendar, FiClock, FiBox, FiDollarSign, FiTarget, FiAward, FiTrash2, FiFileText, FiTrendingUp, FiEdit3, FiCheck, FiX, FiLoader, FiChevronDown, FiShoppingBag, FiInfo } from 'react-icons/fi';
import { motion, AnimatePresence } from 'framer-motion';
import FooterSection from '../../components/FooterSection';
import { useTranslation } from 'react-i18next';
import i18n from '../../i18n';
import ApiClient, { clearAuthTokens } from '../../ApiClient';
import { useDispatch, useSelector } from 'react-redux';
import type { RootState } from '../../store/store';
import { setUser, clearUser, setIsAuth } from '../../store/slices/userSlice';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';

interface OrderItem {
  id: number;
  order_number: string;
  status: string;
  total: number;
  subtotal?: number;
  discount_amount?: number;
  source: string;
  date: string;
  has_invoice: boolean;
  items?: Array<{
    id: number;
    product_name: string;
    product_slug?: string;
    product_image: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    variant_name?: string;
    variant_value?: string;
    variant_image?: string;
  }>;
}

interface AgentSummary {
  total_commission_this_month: number;
  total_commission_all_time: number;
  monthly_target: number;
  monthly_target_progress_percent: number;
  expected_bonus: number;
}

interface AgentActivityItem {
  id: number;
  order_number: string;
  customer: string;
  status: string;
  total: number;
  source: string;
  date: string;
  invoice_uuid: string | null;
  has_invoice: boolean;
  items?: Array<{
    id: number;
    product_name: string;
    product_slug?: string;
    product_image: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    variant_name?: string;
    variant_value?: string;
    variant_image?: string;
  }>;
}

interface ProfileData {
  user: {
    id: number;
    full_name: string;
    email: string;
    is_agent: boolean;
    code: string | null;
    commission: string | null;
    monthly_target: string | null;
    created_at: string;
    last_login_at: string | null;
  };
  customer_summary: {
    total_orders: number;
    total_spent: number;
  };
  recent_orders: OrderItem[];
  agent_summary: AgentSummary | null;
}

export const Profile: React.FC = () => {
  const isRTL = i18n.language === 'ar';
  const { t } = useTranslation(['profile', 'auth']);
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const authUser = useSelector((state: RootState) => state.auth?.user);

  const [profileData, setProfileData] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);
  const [isEditingName, setIsEditingName] = useState(false);
  const [editName, setEditName] = useState('');
  const [savingName, setSavingName] = useState(false);
  const [deletingAccount, setDeletingAccount] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [downloadingInvoice, setDownloadingInvoice] = useState<number | null>(null);
  const [expandedOrderId, setExpandedOrderId] = useState<number | null>(null);

  const [agentActivity, setAgentActivity] = useState<AgentActivityItem[]>([]);
  const [agentActivityPage, setAgentActivityPage] = useState(1);
  const [agentActivityLastPage, setAgentActivityLastPage] = useState(1);
  const [loadingActivity, setLoadingActivity] = useState(false);

  const fetchProfile = useCallback(async () => {
    try {
      setLoading(true);
      const response = await ApiClient.profile.getProfile();
      setProfileData(response.data.data);
      setEditName(response.data.data.user.full_name || '');
    } catch {
      // error handled silently
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchAgentActivity = useCallback(async (page: number = 1) => {
    try {
      setLoadingActivity(true);
      const response = await ApiClient.profile.getAgentActivity(page, 5);
      const data = response.data.data;
      setAgentActivity(data.data || []);
      setAgentActivityPage(data.current_page || 1);
      setAgentActivityLastPage(data.last_page || 1);
    } catch {
      // error handled silently
    } finally {
      setLoadingActivity(false);
    }
  }, []);

  useEffect(() => {
    fetchProfile();
  }, [fetchProfile]);

  useEffect(() => {
    if (profileData?.user.is_agent) {
      fetchAgentActivity(1);
    }
  }, [profileData?.user.is_agent, fetchAgentActivity]);

  useEffect(() => {
    const section = searchParams.get('section');
    if (section && !loading) {
      const el = document.getElementById(`profile-${section}`);
      el?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, [searchParams, loading]);

  const handleSaveName = async () => {
    if (!editName.trim() || editName === profileData?.user.full_name) {
      setIsEditingName(false);
      return;
    }
    try {
      setSavingName(true);
      const response = await ApiClient.profile.updateProfile({ full_name: editName.trim() });
      const updatedUser = response.data.data;
      setProfileData(prev => prev ? { ...prev, user: { ...prev.user, full_name: updatedUser.full_name } } : prev);
      dispatch(setUser({
        ...authUser,
        full_name: updatedUser.full_name,
        email: authUser?.email ?? null,
        is_agent: authUser?.is_agent ?? null,
        code: authUser?.code ?? null,
        commission: authUser?.commission ?? null,
        created_at: authUser?.created_at ?? null,
      }));
      setIsEditingName(false);
    } catch {
      // error handled silently
    } finally {
      setSavingName(false);
    }
  };

  const handleDeleteAccount = async () => {
    try {
      setDeletingAccount(true);
      await ApiClient.profile.deleteAccount();
      clearAuthTokens();
      dispatch(clearUser());
      dispatch(setIsAuth(false));
      navigate('/');
    } catch {
      setDeletingAccount(false);
      setShowDeleteConfirm(false);
    }
  };

  const handleDownloadInvoice = async (orderId: number) => {
    try {
      setDownloadingInvoice(orderId);
      const response = await ApiClient.profile.downloadInvoice(orderId);
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `invoice-ORD-${String(orderId).padStart(6, '0')}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch {
      // error handled silently
    } finally {
      setDownloadingInvoice(null);
    }
  };

  const getInitials = (name: string | null) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return t('profile:notAvailable', 'N/A');
    try {
      const date = new Date(dateStr);
      return date.toLocaleDateString(isRTL ? 'ar-EG' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
      return dateStr;
    }
  };

  const formatLastLogin = (dateStr: string | null) => {
    if (!dateStr) return t('profile:never', 'Never');
    try {
      const date = new Date(dateStr);
      const now = new Date();
      const diffMs = now.getTime() - date.getTime();
      const diffMins = Math.floor(diffMs / 60000);

      if (diffMins < 1) return t('profile:justNow', 'Just now');
      if (diffMins < 60) {
        if (isRTL) {
          if (diffMins === 1) return 'منذ دقيقة';
          if (diffMins === 2) return 'منذ دقيقتين';
          if (diffMins >= 3 && diffMins <= 10) return `منذ ${diffMins} دقائق`;
          return `منذ ${diffMins} دقيقة`;
        }
        return `${diffMins}m ago`;
      }
      const diffHours = Math.floor(diffMins / 60);
      if (diffHours < 24) {
        if (isRTL) {
          if (diffHours === 1) return 'منذ ساعة';
          if (diffHours === 2) return 'منذ ساعتين';
          if (diffHours >= 3 && diffHours <= 10) return `منذ ${diffHours} ساعات`;
          return `منذ ${diffHours} ساعة`;
        }
        return `${diffHours}h ago`;
      }

      return date.toLocaleDateString(isRTL ? 'ar-EG' : 'en-US', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
    } catch {
      return dateStr;
    }
  };

  const getStatusBadge = (status: string) => {
    const styles: Record<string, string> = {
      pending: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
      approved: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
      shipped: 'bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-300',
      delivered: 'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-300',
      cancelled: 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300',
      submitted: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
      rejected: 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300',
    };
    return styles[status] || 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-300';
  };

  const statusLabel = (status: string) => {
    const labels: Record<string, { en: string; ar: string }> = {
      pending: { en: 'Pending', ar: 'قيد الانتظار' },
      approved: { en: 'Approved', ar: 'موافق عليه' },
      shipped: { en: 'Shipped', ar: 'تم الشحن' },
      delivered: { en: 'Delivered', ar: 'تم التوصيل' },
      cancelled: { en: 'Cancelled', ar: 'ملغي' },
      submitted: { en: 'Submitted', ar: 'مقدم' },
      rejected: { en: 'Rejected', ar: 'مرفوض' },
    };
    return labels[status]?.[isRTL ? 'ar' : 'en'] || status;
  };

  if (loading) {
    return (
      <div className="min-h-screen pt-10 pb-20 flex items-center justify-center">
        <FiLoader className="animate-spin text-[#B12C2E]" size={32} />
      </div>
    );
  }

  if (!profileData) {
    return (
      <div className="min-h-screen pt-10 pb-20 flex items-center justify-center">
        <p className="text-gray-500 dark:text-slate-400">{t('profile:errorLoading', 'Failed to load profile')}</p>
      </div>
    );
  }

  const { user, customer_summary, recent_orders, agent_summary } = profileData;
  const isAgent = user.is_agent;

  return (
    <div className="min-h-screen pt-10 pb-20 font-sans" dir={isRTL ? "rtl" : "ltr"}>
      <MaxWidthWrapper className='pb-20'>
        {/* Header Section */}
        <div className="mb-10 text-center md:text-start flex flex-col md:flex-row items-center justify-between">
          <div>
            <h1 className="text-3xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">
              {t('profile:title', 'My Profile')}
            </h1>
            <p className="text-gray-500 dark:text-slate-400 mt-2 text-sm md:text-base">
              {t('profile:subtitle', 'Manage your account settings and activities')}
            </p>
          </div>
          <div className="mt-6 md:mt-0 flex items-center space-x-3 rtl:space-x-reverse bg-white dark:bg-[#151D2C] px-5 py-2.5 rounded-full shadow-sm border border-gray-100 dark:border-slate-800">
            <div className="w-10 h-10 ltr:mr-2 rtl:ml-2 rounded-full bg-gradient-to-br from-[#B12C2E] to-red-600 flex items-center justify-center text-white font-bold text-lg shadow-sm">
              {getInitials(user.full_name)}
            </div>
            <div className="flex flex-col">
              <span className="font-bold text-gray-900 dark:text-slate-100 text-sm">{user.full_name}</span>
              <span className={`text-xs font-semibold px-2.5 py-0.5 rounded-full w-max ${isAgent ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-300'}`}>
                {isAgent ? t('profile:salesAgent', 'Sales Agent') : t('profile:customer', 'Customer')}
              </span>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column */}
          <div className="space-y-8 lg:col-span-1">
            {/* Basic Info Card */}
            <div id="profile-info" className="bg-white dark:bg-[#151D2C] rounded-[24px] shadow-sm border border-gray-100 dark:border-slate-800 p-6 xl:p-8 relative overflow-hidden transition-colors">
              <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 mb-6 flex items-center">
                <FiUser className="me-3 text-[#B12C2E] dark:text-[#E03E41]" size={22} />
                {t('profile:basicInfo', 'Basic Information')}
              </h2>

              <div className="space-y-6">
                <div>
                  <label className="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{t('profile:fullName', 'Full Name')}</label>
                  {isEditingName ? (
                    <div className="flex items-center mt-1.5 gap-2">
                      <input
                        type="text"
                        value={editName}
                        onChange={(e) => setEditName(e.target.value)}
                        className="flex-1 border border-gray-300 dark:border-slate-700 bg-white dark:bg-[#1C2638] text-gray-900 dark:text-slate-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-[#B12C2E]"
                        autoFocus
                        onKeyDown={(e) => { if (e.key === 'Enter') handleSaveName(); if (e.key === 'Escape') setIsEditingName(false); }}
                      />
                      <button onClick={handleSaveName} disabled={savingName} className="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-950/40 rounded-lg transition-colors">
                        {savingName ? <FiLoader className="animate-spin" size={16} /> : <FiCheck size={16} />}
                      </button>
                      <button onClick={() => { setIsEditingName(false); setEditName(user.full_name || ''); }} className="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors">
                        <FiX size={16} />
                      </button>
                    </div>
                  ) : (
                    <div className="flex items-center mt-1.5 text-gray-800 dark:text-slate-200 font-semibold justify-between">
                      <div className="flex items-center">
                        <FiUser className="me-2 text-gray-400 dark:text-slate-500" size={16} />
                        {user.full_name}
                      </div>
                      <button onClick={() => { setIsEditingName(true); setEditName(user.full_name || ''); }} className="p-1.5 cursor-pointer text-gray-400 hover:text-[#B12C2E] dark:hover:text-[#E03E41] hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors">
                        <FiEdit3 size={15} />
                      </button>
                    </div>
                  )}
                </div>

                <hr className="border-gray-100 dark:border-slate-800" />

                <div>
                  <label className="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">{t('profile:email', 'Email Address')}</label>
                  <div className="flex items-center mt-1.5 text-gray-800 dark:text-slate-200 font-semibold text-sm">
                    <FiMail className="me-2 text-gray-400 dark:text-slate-500" size={16} />
                    {user.email}
                  </div>
                </div>
              </div>
            </div>

            {/* Account Info Card - only for agents */}
            {isAgent && (
              <div id="profile-account" className="bg-white dark:bg-[#151D2C] rounded-[24px] shadow-sm border border-gray-100 dark:border-slate-800 p-6 xl:p-8 transition-colors">
                <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 mb-6 flex items-center">
                  <FiClock className="me-3 text-[#B12C2E] dark:text-[#E03E41]" size={22} />
                  {t('profile:accountInfo', 'Account Information')}
                </h2>

                <div className="space-y-4">
                  <div className="flex justify-between items-center bg-gray-50 dark:bg-[#1C2638] p-3.5 rounded-xl border border-gray-100 dark:border-slate-800">
                    <span className="text-sm text-gray-500 dark:text-slate-400">{t('profile:accountType', 'Account Type')}</span>
                    <span className="text-sm font-bold text-gray-900 dark:text-slate-100">{t('profile:agent', 'Agent')}</span>
                  </div>
                  <div className="flex justify-between items-center bg-gray-50 dark:bg-[#1C2638] p-3.5 rounded-xl border border-gray-100 dark:border-slate-800">
                    <span className="text-sm text-gray-500 dark:text-slate-400 flex items-center"><FiCalendar className="me-2 text-[#B12C2E]" /> {t('profile:joinedOn', 'Joined On')}</span>
                    <span className="text-sm font-bold text-gray-900 dark:text-slate-100">{formatDate(user.created_at)}</span>
                  </div>
                  <div className="flex justify-between items-center bg-gray-50 dark:bg-[#1C2638] p-3.5 rounded-xl border border-gray-100 dark:border-slate-800">
                    <span className="text-sm text-gray-500 dark:text-slate-400 flex items-center"><FiClock className="me-2 text-[#B12C2E]" /> {t('profile:lastLogin', 'Last Login')}</span>
                    <span className="text-sm font-bold text-gray-900 dark:text-slate-100">{formatLastLogin(user.last_login_at)}</span>
                  </div>
                </div>
              </div>
            )}

            {/* Danger Zone */}
            <div className="bg-red-50/50 dark:bg-red-950/20 rounded-[24px] shadow-sm border border-red-100 dark:border-red-900/40 p-6 xl:p-8 transition-colors">
              <h2 className="text-lg font-bold text-red-600 dark:text-red-400 mb-3 flex items-center">
                <FiTrash2 className="me-2" size={20} />
                {t('profile:deleteAccount', 'Delete Account')}
              </h2>
              <p className="text-sm text-red-500/80 dark:text-red-400/80 mb-5 leading-relaxed">
                {t('profile:deleteWarning', 'Once you delete your account, there is no going back. Please be certain.')}
              </p>
              {showDeleteConfirm ? (
                <div className="space-y-3">
                  <p className="text-sm font-bold text-red-700 dark:text-red-300">{t('profile:deleteConfirm', 'Are you sure you want to delete your account?')}</p>
                  <div className="flex gap-3">
                    <button
                      onClick={handleDeleteAccount}
                      disabled={deletingAccount}
                      className="flex-1 py-3 px-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                    >
                      {deletingAccount && <FiLoader className="animate-spin" size={14} />}
                      {t('profile:confirmDelete', 'Yes, Delete')}
                    </button>
                    <button
                      onClick={() => setShowDeleteConfirm(false)}
                      className="flex-1 py-3 px-4 bg-white dark:bg-[#151D2C] border border-gray-200 dark:border-slate-700 text-gray-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors"
                    >
                      {t('profile:cancel', 'Cancel')}
                    </button>
                  </div>
                </div>
              ) : (
                <button
                  onClick={() => setShowDeleteConfirm(true)}
                  className="w-full cursor-pointer py-3 px-4 bg-white dark:bg-[#151D2C] border border-red-200 dark:border-red-900/60 text-red-600 dark:text-red-400 rounded-xl font-bold hover:bg-red-600 hover:text-white dark:hover:bg-red-600 dark:hover:text-white transition-all shadow-sm"
                >
                  {t('profile:deleteMyAccount', 'Delete My Account')}
                </button>
              )}
            </div>
          </div>

          {/* Right Column */}
          <div className="space-y-8 lg:col-span-2">
            {/* Agent Dashboard */}
            {isAgent && agent_summary && (
              <div className="bg-white dark:bg-[#151D2C] rounded-[24px] shadow-sm border border-gray-100 dark:border-slate-800 p-6 xl:p-8 relative overflow-hidden transition-colors">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                  <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 flex items-center">
                    <FiAward className="me-3 text-amber-500" size={24} />
                    {t('profile:agentDashboard', 'Agent Dashboard')}
                  </h2>
                  <div className="bg-amber-100 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 text-sm font-bold px-4 py-1.5 rounded-full flex items-center border border-amber-200/50 dark:border-amber-900/50">
                    {t('profile:code', 'Code:')} <span className="mx-1 tracking-wider uppercase text-amber-900 dark:text-amber-200">{user.code}</span>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                  {/* Commission */}
                  <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-5 border border-gray-100 dark:border-slate-800">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-sm font-semibold text-gray-500 dark:text-slate-400">{t('profile:commission', 'Commission')}</span>
                      <div className="p-2 bg-amber-100 dark:bg-amber-950/50 rounded-xl text-amber-600 dark:text-amber-400"><FiDollarSign size={18} /></div>
                    </div>
                    <div className="text-2xl font-extrabold text-gray-900 dark:text-white">EGP {agent_summary.total_commission_all_time.toLocaleString()}</div>
                    <div className="mt-1 text-xs text-gray-400 dark:text-slate-500">
                      {isRTL ? 'هذا الشهر:' : 'This Month:'} <span className="font-bold text-gray-700 dark:text-slate-300">EGP {agent_summary.total_commission_this_month.toLocaleString()}</span>
                    </div>
                  </div>
                  {/* Target */}
                  <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-5 border border-gray-100 dark:border-slate-800">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-sm font-semibold text-gray-500 dark:text-slate-400">{t('profile:monthlyTarget', 'Monthly Target')}</span>
                      <div className="p-2 bg-blue-100 dark:bg-blue-950/50 rounded-xl text-blue-600 dark:text-blue-400"><FiTarget size={18} /></div>
                    </div>
                    <div className="text-2xl font-extrabold text-gray-900 dark:text-white">{Math.min(agent_summary.monthly_target_progress_percent, 100).toFixed(0)}%</div>
                    <div className="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2 mt-2">
                      <div className="bg-blue-500 h-2 rounded-full transition-all" style={{ width: `${Math.min(agent_summary.monthly_target_progress_percent, 100)}%` }}></div>
                    </div>
                  </div>
                  {/* Bonus */}
                  <div className="bg-gray-50 dark:bg-[#1C2638] rounded-2xl p-5 border border-gray-100 dark:border-slate-800">
                    <div className="flex items-center justify-between mb-3">
                      <span className="text-sm font-semibold text-gray-500 dark:text-slate-400">{t('profile:expectedBonus', 'Expected Bonus')}</span>
                      <div className="p-2 bg-green-100 dark:bg-green-950/50 rounded-xl text-green-600 dark:text-green-400"><FiTrendingUp size={18} /></div>
                    </div>
                    <div className="text-2xl font-extrabold text-gray-900 dark:text-white">EGP {agent_summary.expected_bonus.toLocaleString()}</div>
                  </div>
                </div>

                {/* Agent Sales History */}
                <div>
                  <h3 className="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">{t('profile:recentSales', 'Recent Sales History')}</h3>
                  {loadingActivity ? (
                    <div className="flex justify-center py-8">
                      <FiLoader className="animate-spin text-gray-400" size={24} />
                    </div>
                  ) : agentActivity.length === 0 ? (
                    <div className="text-center py-8 text-gray-400 dark:text-slate-500 text-sm">
                      {t('profile:noSales', 'No sales activity yet')}
                    </div>
                  ) : (
                    <>
                      <div className="space-y-4">
                        {agentActivity.map((item) => (
                          <div key={item.id} className="bg-white dark:bg-[#1C2638] rounded-2xl border border-gray-100 dark:border-slate-800 transition-all duration-300 shadow-sm">
                            <div
                              onClick={() => setExpandedOrderId(expandedOrderId === item.id ? null : item.id)}
                              className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 sm:p-5 cursor-pointer gap-4"
                            >
                              <div className="flex items-start gap-4 flex-1 min-w-0">
                                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center transition-colors flex-shrink-0 ${expandedOrderId === item.id ? 'bg-[#B12C2E] text-white' : 'bg-gray-50 dark:bg-slate-800 text-[#B12C2E] dark:text-[#E03E41]'}`}>
                                  <FiShoppingBag className="text-xl" />
                                </div>
                                <div className="min-w-0 flex-1">
                                  <div className="flex flex-wrap items-center gap-2 mb-1">
                                    <h4 className="text-base font-bold text-gray-900 dark:text-slate-100 truncate">{item.order_number}</h4>
                                    <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${getStatusBadge(item.status)}`}>
                                      {statusLabel(item.status)}
                                    </span>
                                  </div>
                                  <div className="text-xs text-gray-500 dark:text-slate-400 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span className="flex items-center gap-1.5 font-semibold text-gray-700 dark:text-slate-300">
                                      <FiUser size={12} className="text-gray-400 dark:text-slate-500" /> {item.customer}
                                    </span>
                                    <span className="flex items-center gap-1.5"><FiCalendar size={12} className="text-gray-400 dark:text-slate-500" /> {item.date}</span>
                                    <span className="font-bold text-[#B12C2E] dark:text-[#E03E41]">EGP {item.total.toLocaleString()}</span>
                                  </div>
                                </div>
                              </div>

                              <div className="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end mt-2 sm:mt-0">
                                <div className="flex items-center gap-2">
                                  {item.has_invoice && (
                                    <button
                                      onClick={(e) => { e.stopPropagation(); handleDownloadInvoice(item.id); }}
                                      disabled={downloadingInvoice === item.id}
                                      className="p-2.5 cursor-pointer text-gray-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-xl transition-all group relative disabled:opacity-50"
                                    >
                                      {downloadingInvoice === item.id ? <FiLoader className="animate-spin" size={18} /> : <FiFileText size={18} />}
                                    </button>
                                  )}
                                  <div className={`p-2.5 rounded-xl transition-all ${expandedOrderId === item.id ? 'bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rotate-180' : 'text-gray-400 dark:text-slate-500'}`}>
                                    <FiChevronDown size={20} />
                                  </div>
                                </div>
                              </div>
                            </div>

                            <AnimatePresence>
                              {expandedOrderId === item.id && (
                                <motion.div
                                  initial={{ height: 0, opacity: 0 }}
                                  animate={{ height: 'auto', opacity: 1 }}
                                  exit={{ height: 0, opacity: 0 }}
                                  transition={{ duration: 0.3, ease: 'easeInOut' }}
                                >
                                  <div className="px-5 pb-6">
                                    <hr className="border-gray-100 dark:border-slate-800 mb-5" />
                                    <div className="space-y-3">
                                      {item.items?.map((orderItem) => (
                                        <div key={orderItem.id} className="flex items-center gap-3 sm:gap-4 p-3 rounded-2xl bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800">
                                          <Link
                                            to={`/products/${orderItem.product_slug}`}
                                            className="w-14 h-14 rounded-xl bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700 overflow-hidden flex-shrink-0 p-1 flex items-center justify-center"
                                          >
                                            {orderItem.product_image ? (
                                              <img src={orderItem.product_image} alt={orderItem.product_name} className="max-w-full max-h-full object-contain" />
                                            ) : (
                                              <FiBox size={20} className="text-gray-400" />
                                            )}
                                          </Link>
                                          <div className="flex-1 min-w-0">
                                            <Link to={`/products/${orderItem.product_slug}`} className="block hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors">
                                              <h6 className="font-bold text-gray-900 dark:text-slate-100 text-xs sm:text-sm">{orderItem.product_name}</h6>
                                            </Link>
                                            <div className="flex flex-wrap items-center gap-x-2 sm:gap-x-4 gap-y-0.5 mt-1">
                                              {orderItem.variant_value && (
                                                <span className="text-[10px] sm:text-xs text-gray-600 dark:text-slate-400 bg-white dark:bg-[#151D2C] px-2 py-0.5 rounded-md border border-gray-100 dark:border-slate-700">
                                                  {orderItem.variant_name ? `${orderItem.variant_name}: ` : ''}{orderItem.variant_value}
                                                </span>
                                              )}
                                              <span className="text-[10px] sm:text-xs text-gray-400 dark:text-slate-500">
                                                {orderItem.quantity} × EGP {orderItem.unit_price.toLocaleString()}
                                              </span>
                                            </div>
                                          </div>
                                          <div className="text-right flex-shrink-0 font-bold text-gray-900 dark:text-white text-sm">
                                            EGP {orderItem.line_total.toLocaleString()}
                                          </div>
                                        </div>
                                      ))}
                                    </div>
                                  </div>
                                </motion.div>
                              )}
                            </AnimatePresence>
                          </div>
                        ))}
                      </div>
                      {agentActivityLastPage > 1 && (
                        <div className="flex justify-center gap-2 mt-6">
                          <button
                            onClick={() => fetchAgentActivity(agentActivityPage - 1)}
                            disabled={agentActivityPage <= 1}
                            className="px-4 py-2 text-sm font-semibold border border-gray-200 dark:border-slate-700 bg-white dark:bg-[#1C2638] text-gray-700 dark:text-slate-300 rounded-xl disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors"
                          >
                            {isRTL ? 'التالي' : 'Prev'}
                          </button>
                          <span className="px-4 py-2 text-sm text-gray-500 dark:text-slate-400 font-mono">{agentActivityPage} / {agentActivityLastPage}</span>
                          <button
                            onClick={() => fetchAgentActivity(agentActivityPage + 1)}
                            disabled={agentActivityPage >= agentActivityLastPage}
                            className="px-4 py-2 text-sm font-semibold border border-gray-200 dark:border-slate-700 bg-white dark:bg-[#1C2638] text-gray-700 dark:text-slate-300 rounded-xl disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors"
                          >
                            {isRTL ? 'السابق' : 'Next'}
                          </button>
                        </div>
                      )}
                    </>
                  )}
                </div>
              </div>
            )}

            {/* Orders Section - for customers */}
            {!isAgent && (
              <div id="profile-orders" className="bg-white dark:bg-[#151D2C] rounded-[24px] shadow-sm border border-gray-100 dark:border-slate-800 p-6 xl:p-8 transition-colors">
                <div className="flex flex-col min-[430px]:flex-row justify-between items-center mb-6 gap-3 sm:gap-4">
                  <h2 className="text-xl font-bold text-gray-900 dark:text-slate-100 flex items-center">
                    <FiBox className="me-3 text-[#B12C2E] dark:text-[#E03E41]" size={22} />
                    {t('profile:ordersInvoices', 'Orders & Invoices')}
                  </h2>
                  <div className="bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 text-xs sm:text-sm font-bold px-3.5 py-1.5 rounded-full w-fit">
                    {isRTL ? `إجمالي: ${customer_summary.total_orders} طلب` : `Total: ${customer_summary.total_orders} Orders`}
                  </div>
                </div>

                {recent_orders.length === 0 ? (
                  <div className="text-center py-12">
                    <FiBox className="mx-auto text-gray-300 dark:text-slate-600 mb-3" size={40} />
                    <p className="text-gray-400 dark:text-slate-500 text-sm">{t('profile:noOrders', 'No orders yet')}</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {recent_orders.map((order) => (
                      <div key={order.id} className="bg-white dark:bg-[#1C2638] rounded-2xl border border-gray-100 dark:border-slate-800 transition-all duration-300 shadow-sm">
                        <div
                          onClick={() => setExpandedOrderId(expandedOrderId === order.id ? null : order.id)}
                          className="flex flex-col sm:flex-row justify-between items-start sm:items-center p-5 cursor-pointer gap-4"
                        >
                          <div className="flex items-start gap-4">
                            <div className={`w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-colors flex-shrink-0 ${expandedOrderId === order.id ? 'bg-[#B12C2E] text-white' : 'bg-gray-50 dark:bg-slate-800 text-[#B12C2E] dark:text-[#E03E41]'}`}>
                              <FiShoppingBag className="text-xl sm:text-2xl" />
                            </div>
                            <div className="min-w-0 flex-1">
                              <div className="flex flex-wrap items-center gap-2 mb-1">
                                <h4 className="text-base sm:text-lg font-bold text-gray-900 dark:text-slate-100 truncate">{order.order_number}</h4>
                                <span className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${getStatusBadge(order.status)}`}>
                                  {statusLabel(order.status)}
                                </span>
                              </div>
                              <div className="text-xs text-gray-500 dark:text-slate-400 flex flex-wrap items-center gap-x-3 sm:gap-x-4 gap-y-1">
                                <span className="flex items-center gap-1.5"><FiCalendar size={12} className="text-gray-400 dark:text-slate-500" /> {order.date}</span>
                                <span className="flex items-center gap-1.5"><FiBox size={12} className="text-gray-400 dark:text-slate-500" /> {order.items?.length || 0} {t('profile:items', 'Items')}</span>
                                <span className="font-bold text-[#B12C2E] dark:text-[#E03E41] text-sm sm:text-base">EGP {order.total.toLocaleString()}</span>
                              </div>
                            </div>
                          </div>

                          <div className="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end mt-2 sm:mt-0">
                            <div className="flex items-center gap-2">
                              {order.has_invoice && (
                                <button
                                  onClick={(e) => { e.stopPropagation(); handleDownloadInvoice(order.id); }}
                                  disabled={downloadingInvoice === order.id}
                                  className="p-2.5 cursor-pointer text-gray-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-xl transition-all group relative disabled:opacity-50"
                                >
                                  {downloadingInvoice === order.id ? <FiLoader className="animate-spin" size={18} /> : <FiFileText size={18} />}
                                </button>
                              )}
                              <div className={`p-2.5 rounded-xl transition-all ${expandedOrderId === order.id ? 'bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rotate-180' : 'text-gray-400 dark:text-slate-500'}`}>
                                <FiChevronDown size={20} />
                              </div>
                            </div>
                          </div>
                        </div>

                        <AnimatePresence>
                          {expandedOrderId === order.id && (
                            <motion.div
                              initial={{ height: 0, opacity: 0 }}
                              animate={{ height: 'auto', opacity: 1 }}
                              exit={{ height: 0, opacity: 0 }}
                              transition={{ duration: 0.3, ease: 'easeInOut' }}
                            >
                              <div className="px-5 pb-6">
                                <hr className="border-gray-100 dark:border-slate-800 mb-5" />
                                <div className="space-y-3">
                                  {order.items?.map((item) => (
                                    <div key={item.id} className="flex items-center gap-3 sm:gap-4 p-3 rounded-2xl bg-gray-50/70 dark:bg-slate-800/40 border border-gray-100 dark:border-slate-800">
                                      <Link
                                        to={`/products/${item.product_slug}`}
                                        className="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-white dark:bg-[#151D2C] border border-gray-100 dark:border-slate-700 overflow-hidden flex-shrink-0 p-1 flex items-center justify-center"
                                      >
                                        {item.product_image ? (
                                          <img src={item.product_image} alt={item.product_name} className="max-w-full max-h-full object-contain" />
                                        ) : (
                                          <FiBox size={20} className="text-gray-400" />
                                        )}
                                      </Link>
                                      <div className="flex-1 min-w-0">
                                        <Link to={`/products/${item.product_slug}`} className="block hover:text-[#B12C2E] dark:hover:text-[#E03E41] transition-colors">
                                          <h6 className="font-bold text-gray-900 dark:text-slate-100 leading-snug line-clamp-1 sm:line-clamp-2 text-xs sm:text-sm">{item.product_name}</h6>
                                        </Link>
                                        <div className="flex flex-wrap items-center gap-x-2 sm:gap-x-4 gap-y-0.5 mt-1 sm:mt-1.5">
                                          {item.variant_value && (
                                            <span className="text-[10px] sm:text-xs text-gray-600 dark:text-slate-400 bg-white dark:bg-[#151D2C] px-2 py-0.5 rounded-md border border-gray-100 dark:border-slate-700 whitespace-nowrap">
                                              {item.variant_name ? `${item.variant_name}: ` : ''}{item.variant_value}
                                            </span>
                                          )}
                                          <span className="text-[10px] sm:text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">
                                            {item.quantity} × EGP {item.unit_price.toLocaleString()}
                                          </span>
                                        </div>
                                      </div>
                                      <div className="text-right flex-shrink-0 font-bold text-gray-900 dark:text-white text-sm">
                                        EGP {item.line_total.toLocaleString()}
                                      </div>
                                    </div>
                                  ))}
                                </div>
                              </div>
                            </motion.div>
                          )}
                        </AnimatePresence>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </MaxWidthWrapper>
      <FooterSection />
    </div>
  );
};

export default Profile;

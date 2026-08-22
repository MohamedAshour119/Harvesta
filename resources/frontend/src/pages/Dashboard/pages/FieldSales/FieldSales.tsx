import { useEffect, useMemo, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { FiCalendar, FiCamera, FiCheckCircle, FiChevronLeft, FiChevronRight, FiClock, FiPackage, FiPlus, FiSearch, FiTrash2, FiUser, FiXCircle } from "react-icons/fi";
import { useTranslation } from "react-i18next";
import { useSelector } from "react-redux";
import Header from "../../components/Header";
import { Sidebar } from "../../components/Sidebar";
import ApiClient from "../../../../ApiClient";
import type {
    AgentManualOrder,
    AgentManualOrderItem,
    AgentManualOrderLookupCustomer,
    AgentManualOrderLookupProduct,
} from "../../../../types";
import type { RootState } from "../../../../store/store";

type ManualOrderFormState = {
    userId: number | null;
    firstName: string;
    lastName: string;
    email: string;
    phoneNumber: string;
    companyName: string;
    address: string;
    city: string;
    province: string;
    postalCode: string;
    soldAt: string;
    proofPhoto: File | null;
    items: AgentManualOrderItem[];
};

const createInitialForm = (): ManualOrderFormState => ({
    userId: null,
    firstName: "",
    lastName: "",
    email: "",
    phoneNumber: "",
    companyName: "",
    address: "",
    city: "",
    province: "",
    postalCode: "",
    soldAt: new Date().toISOString().slice(0, 16),
    proofPhoto: null,
    items: [],
});

const statusClassName = (status: string) => {
    switch (status) {
        case "approved":
            return "bg-emerald-100 text-emerald-700 ring-emerald-200";
        case "rejected":
            return "bg-rose-100 text-rose-700 ring-rose-200";
        default:
            return "bg-red-100 text-[#B12C2E] ring-red-200";
    }
};

const formatMoney = (value: number) =>
    `EGP ${new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value || 0)}`;

const formatDate = (value?: string | null) => {
    if (!value) {
        return "--";
    }

    return new Date(value).toLocaleString();
};

const getAgentLookupPrice = (variant: AgentManualOrderLookupVariant) =>
    Number(variant.agent_price ?? variant.unit_price_usd ?? 0);

const parseDateTimeLocal = (value?: string | null) => {
    if (!value) {
        return new Date();
    }

    const [datePart, timePart = "00:00"] = value.split("T");
    const [year, month, day] = datePart.split("-").map(Number);
    const [hours, minutes] = timePart.split(":").map(Number);

    return new Date(year, (month || 1) - 1, day || 1, hours || 0, minutes || 0, 0, 0);
};

const toDateTimeLocalValue = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const StatCard = ({
    label,
    value,
    accent,
}: {
    label: string;
    value: string;
    accent: string;
}) => (
    <div className="rounded-[28px] border border-white/60 bg-white/80 p-5 shadow-[0_18px_60px_rgba(15,23,42,0.08)] backdrop-blur">
        <div className={`mb-3 h-1.5 w-14 rounded-full ${accent}`} />
        <p className="text-xs uppercase tracking-[0.3em] text-slate-400">{label}</p>
        <p className="mt-3 text-2xl font-semibold text-slate-900">{value}</p>
    </div>
);

const FeedbackPopup = ({
    type,
    message,
    onClose,
    translations,
}: {
    type: "success" | "error";
    message: string;
    onClose: () => void;
    translations: {
        successBadge: string;
        successTitle: string;
        successHint: string;
        successAction: string;
        errorBadge: string;
        errorTitle: string;
        errorHint: string;
        errorAction: string;
    };
}) => {
    const isSuccess = type === "success";

    return (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/30 px-4 backdrop-blur-[2px]">
            <div
                className={`relative w-full max-w-md overflow-hidden rounded-[32px] border p-6 shadow-[0_30px_90px_rgba(15,23,42,0.18)] sm:p-7 ${isSuccess
                    ? "border-emerald-100 bg-[linear-gradient(180deg,#fcfffd_0%,#edfdf4_100%)]"
                    : "border-rose-100 bg-[linear-gradient(180deg,#fffdfd_0%,#fff1f2_100%)]"
                    }`}
            >
                <div className="pointer-events-none absolute inset-0">
                    <div
                        className={`absolute -right-10 top-0 h-28 w-28 rounded-full blur-3xl ${isSuccess ? "bg-emerald-200/45" : "bg-rose-200/45"
                            }`}
                    />
                    <div
                        className={`absolute bottom-0 left-0 h-24 w-24 rounded-full blur-3xl ${isSuccess ? "bg-blue-100/40" : "bg-red-100/40"
                            }`}
                    />
                </div>



                <div className="relative">
                    <div
                        className={`inline-flex rounded-[24px] p-4 shadow-[0_16px_35px_rgba(15,23,42,0.08)] ${isSuccess
                            ? "bg-emerald-500 text-white"
                            : "bg-rose-500 text-white"
                            }`}
                    >
                        {isSuccess ? (
                            <FiCheckCircle className="h-7 w-7" />
                        ) : (
                            <FiXCircle className="h-7 w-7" />
                        )}
                    </div>

                    <p
                        className={`mt-5 text-xs font-semibold uppercase tracking-[0.28em] ${isSuccess ? "text-emerald-600" : "text-rose-600"
                            }`}
                    >
                        {isSuccess ? translations.successBadge : translations.errorBadge}
                    </p>
                    <h3 className="mt-2 text-2xl font-semibold text-slate-900">
                        {isSuccess ? translations.successTitle : translations.errorTitle}
                    </h3>
                    <p className="mt-3 text-sm leading-7 text-slate-600">
                        {message}
                    </p>

                    <div className="mt-6 rounded-[24px] border border-white/80 bg-white/80 px-4 py-4 text-sm text-slate-600 shadow-sm">
                        {isSuccess ? translations.successHint : translations.errorHint}
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className={`mt-6 w-full rounded-full px-5 py-3.5 text-sm font-semibold transition ${isSuccess
                            ? "bg-emerald-500 text-white hover:bg-emerald-600"
                            : "bg-rose-500 text-white hover:bg-rose-600"
                            }`}
                    >
                        {isSuccess ? translations.successAction : translations.errorAction}
                    </button>
                </div>
            </div>
        </div>
    );
};

const ValidationMessage = ({ message }: { message?: string | null }) =>
    message ? <p className="mt-2 text-xs font-medium text-rose-600">{message}</p> : null;

const Input = ({
    label,
    value,
    onChange,
    type = "text",
    placeholder,
    required = false,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    type?: string;
    placeholder?: string;
    required?: boolean;
}) => (
    <label className="block">
        <span className="mb-2 block text-sm font-medium text-slate-700">
            {label}
            {required && <span className="ms-1 text-[#B12C2E]">*</span>}
        </span>
        <input
            type={type}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            placeholder={placeholder}
            className="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-[#B12C2E] focus:ring-4 focus:ring-red-100"
        />
    </label>
);

const PremiumTimeSelect = ({
    label,
    value,
    options,
    onChange,
    isRtl = false,
}: {
    label: string;
    value: string;
    options: { value: string; label: string }[];
    onChange: (value: string) => void;
    isRtl?: boolean;
}) => {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handleClickOutside = (event: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);

        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, [open]);

    const selectedOption = options.find((option) => option.value === value);

    return (
        <div className="relative block" ref={wrapperRef}>
            <span className="mb-2 block text-xs uppercase tracking-[0.2em] text-slate-400">{label}</span>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className={`flex w-full cursor-pointer items-center justify-between rounded-2xl border px-3 py-3 text-sm shadow-sm transition ${open
                    ? "border-red-300 bg-red-50 text-slate-900 ring-4 ring-red-100"
                    : "border-slate-200 bg-white text-slate-900 hover:border-red-200"
                    }`}
            >
                <span>{selectedOption?.label ?? value}</span>
                {isRtl ? (
                    <FiChevronLeft className={`h-4 w-4 text-[#B12C2E] transition ${open ? "-rotate-90" : ""}`} />
                ) : (
                    <FiChevronRight className={`h-4 w-4 text-[#B12C2E] transition ${open ? "rotate-90" : ""}`} />
                )}
            </button>

            {open && (
                <div className="absolute inset-x-0 bottom-[calc(100%+0.65rem)] z-40 max-h-56 overflow-y-auto rounded-[22px] border border-red-100 bg-white/95 p-2 shadow-[0_20px_55px_rgba(177,44,46,0.16)] backdrop-blur">
                    <div className="grid gap-1">
                        {options.map((option) => {
                            const active = option.value === value;

                            return (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                    className={`cursor-pointer rounded-2xl px-3 py-3 text-sm transition ${active
                                        ? "bg-[#B12C2E] font-semibold text-white shadow-sm"
                                        : "bg-transparent text-slate-700 hover:bg-red-50"
                                        }`}
                                >
                                    {option.label}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}
        </div>
    );
};

const PremiumDateTimePicker = ({
    label,
    value,
    onChange,
    locale,
    translations,
    required = false,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    locale: string;
    translations: {
        placeholder: string;
        today: string;
        clear: string;
        hour: string;
        minute: string;
        period: string;
        weekdaysCompact: string[];
    };
    required?: boolean;
}) => {
    const [open, setOpen] = useState(false);
    const [viewDate, setViewDate] = useState(() => parseDateTimeLocal(value));
    const wrapperRef = useRef<HTMLDivElement | null>(null);
    const isRtl = locale.toLowerCase().startsWith("ar");

    useEffect(() => {
        setViewDate(parseDateTimeLocal(value));
    }, [value]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handleClickOutside = (event: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);

        return () => document.removeEventListener("mousedown", handleClickOutside);
    }, [open]);

    const selectedDate = parseDateTimeLocal(value);
    const currentMonth = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1);
    const monthStartDay = currentMonth.getDay();
    const gridStart = new Date(currentMonth);
    gridStart.setDate(currentMonth.getDate() - monthStartDay);

    const calendarDays = Array.from({ length: 42 }, (_, index) => {
        const date = new Date(gridStart);
        date.setDate(gridStart.getDate() + index);
        return date;
    });

    const monthLabel = new Intl.DateTimeFormat(locale, { month: "long", year: "numeric" }).format(currentMonth);
    const weekDays = Array.from({ length: 7 }, (_, index) =>
        new Intl.DateTimeFormat(locale, { weekday: "short" }).format(new Date(2026, 2, 1 + index))
    );

    const hour24 = selectedDate.getHours();
    const selectedPeriod = hour24 >= 12 ? "PM" : "AM";
    const selectedHour = hour24 % 12 === 0 ? 12 : hour24 % 12;
    const selectedMinute = selectedDate.getMinutes();
    const hourOptions = Array.from({ length: 12 }, (_, index) => {
        const hour = index + 1;
        return { value: String(hour), label: String(hour).padStart(2, "0") };
    });
    const minuteOptions = Array.from({ length: 60 }, (_, index) => ({
        value: String(index),
        label: String(index).padStart(2, "0"),
    }));
    const periodOptions = [
        { value: "AM", label: "AM" },
        { value: "PM", label: "PM" },
    ];

    const updateDate = (updates: Partial<{ year: number; month: number; day: number; hour: number; minute: number }>) => {
        const next = new Date(selectedDate);

        if (updates.year !== undefined) next.setFullYear(updates.year);
        if (updates.month !== undefined) next.setMonth(updates.month);
        if (updates.day !== undefined) next.setDate(updates.day);
        if (updates.hour !== undefined) next.setHours(updates.hour);
        if (updates.minute !== undefined) next.setMinutes(updates.minute);

        onChange(toDateTimeLocalValue(next));
    };

    const setHour12 = (hour: number) => {
        const normalized = hour % 12;
        const nextHour = selectedPeriod === "PM" ? normalized + 12 : normalized;
        updateDate({ hour: nextHour });
    };

    const setPeriod = (period: "AM" | "PM") => {
        let nextHour = selectedDate.getHours();

        if (period === "AM" && nextHour >= 12) nextHour -= 12;
        if (period === "PM" && nextHour < 12) nextHour += 12;

        updateDate({ hour: nextHour });
    };

    const displayValue = value
        ? new Intl.DateTimeFormat(locale, {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "numeric",
            minute: "2-digit",
        }).format(selectedDate)
        : translations.placeholder;

    return (
        <div className="relative block" ref={wrapperRef}>
            <span className="mb-2 block text-sm font-medium text-slate-700">
                {label}
                {required && <span className="ms-1 text-[#B12C2E]">*</span>}
            </span>
            <button
                type="button"
                onClick={() => setOpen((current) => !current)}
                className="flex w-full cursor-pointer items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition hover:border-red-300 focus:border-[#B12C2E] focus:ring-4 focus:ring-red-100"
            >
                <span>{displayValue}</span>
                <span className="rounded-full bg-red-50 p-2 text-[#B12C2E]">
                    <FiCalendar className="h-4 w-4" />
                </span>
            </button>

            {open && (
                <div className="absolute z-30 mt-3 w-full max-w-[92vw] rounded-[24px] border border-red-100 bg-[linear-gradient(180deg,#fffdf9_0%,#fff5f5_100%)] p-3 shadow-[0_28px_80px_rgba(177,44,46,0.16)] backdrop-blur sm:w-[min(100%,35rem)] sm:min-w-[32rem] sm:rounded-[28px] sm:p-4">
                    <div className="flex items-center justify-between rounded-[20px] bg-white/90 px-3 py-3 shadow-sm sm:rounded-[22px] sm:px-4">
                        <button
                            type="button"
                            onClick={() =>
                                setViewDate(new Date(viewDate.getFullYear(), viewDate.getMonth() + (isRtl ? 1 : -1), 1))
                            }
                            className="cursor-pointer rounded-full border border-red-100 bg-red-50 p-2 text-[#B12C2E] transition hover:bg-red-100"
                        >
                            {isRtl ? <FiChevronRight className="h-4 w-4" /> : <FiChevronLeft className="h-4 w-4" />}
                        </button>
                        <div className="text-center">
                            <p className="text-xs uppercase tracking-[0.24em] text-slate-400">{translations.placeholder}</p>
                            <p className="mt-1 text-base font-semibold text-slate-900">{monthLabel}</p>
                        </div>
                        <button
                            type="button"
                            onClick={() =>
                                setViewDate(new Date(viewDate.getFullYear(), viewDate.getMonth() + (isRtl ? -1 : 1), 1))
                            }
                            className="cursor-pointer rounded-full border border-red-100 bg-red-50 p-2 text-[#B12C2E] transition hover:bg-red-100"
                        >
                            {isRtl ? <FiChevronLeft className="h-4 w-4" /> : <FiChevronRight className="h-4 w-4" />}
                        </button>
                    </div>

                    <div className="mt-4 grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-slate-400 sm:gap-1.5 sm:text-[11px]">
                        {weekDays.map((day, index) => (
                            <span key={`${day}-${index}`} className="truncate py-2.5">
                                <span className="sm:hidden">{translations.weekdaysCompact[index] ?? day}</span>
                                <span className="hidden sm:inline">{day}</span>
                            </span>
                        ))}
                    </div>

                    <div className="mt-2 grid grid-cols-7 gap-1 sm:gap-1.5">
                        {calendarDays.map((day) => {
                            const sameMonth = day.getMonth() === currentMonth.getMonth();
                            const isSelected =
                                day.getDate() === selectedDate.getDate() &&
                                day.getMonth() === selectedDate.getMonth() &&
                                day.getFullYear() === selectedDate.getFullYear();

                            return (
                                <button
                                    key={day.toISOString()}
                                    type="button"
                                    onClick={() => updateDate({ year: day.getFullYear(), month: day.getMonth(), day: day.getDate() })}
                                    className={`cursor-pointer rounded-xl py-2.5 text-sm transition sm:rounded-2xl sm:py-3 ${isSelected
                                        ? "bg-[#B12C2E] font-semibold text-white shadow-md"
                                        : sameMonth
                                            ? "bg-white/85 text-slate-700 hover:bg-red-50"
                                            : "bg-white/50 text-slate-400 hover:bg-white"
                                        }`}
                                >
                                    {day.getDate()}
                                </button>
                            );
                        })}
                    </div>

                    <div className="mt-4 rounded-[20px] bg-white/85 p-3 shadow-sm sm:rounded-[24px] sm:p-4">
                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-slate-700">
                            <FiClock className="h-4 w-4 text-[#B12C2E]" />
                            {label}
                        </div>
                        <div className="grid grid-cols-3 gap-2 sm:gap-3">
                            <PremiumTimeSelect
                                label={translations.hour}
                                value={String(selectedHour)}
                                options={hourOptions}
                                onChange={(nextValue) => setHour12(Number(nextValue))}
                                isRtl={isRtl}
                            />
                            <PremiumTimeSelect
                                label={translations.minute}
                                value={String(selectedMinute)}
                                options={minuteOptions}
                                onChange={(nextValue) => updateDate({ minute: Number(nextValue) })}
                                isRtl={isRtl}
                            />
                            <PremiumTimeSelect
                                label={translations.period}
                                value={selectedPeriod}
                                options={periodOptions}
                                onChange={(nextValue) => setPeriod(nextValue as "AM" | "PM")}
                                isRtl={isRtl}
                            />
                        </div>
                    </div>

                    <div className="mt-4 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            onClick={() => onChange(toDateTimeLocalValue(new Date()))}
                            className="cursor-pointer rounded-full border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-medium text-[#144995] transition hover:bg-blue-100"
                        >
                            {translations.today}
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                onChange("");
                                setOpen(false);
                            }}
                            className="cursor-pointer rounded-full border border-rose-100 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-100"
                        >
                            {translations.clear}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};

export const FieldSales = () => {
    const { t } = useTranslation(["dashboard"]);
    const user = useSelector((state: RootState) => state.auth?.user);
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [manualOrders, setManualOrders] = useState<AgentManualOrder[]>([]);
    const [customerResults, setCustomerResults] = useState<AgentManualOrderLookupCustomer[]>([]);
    const [productResults, setProductResults] = useState<AgentManualOrderLookupProduct[]>([]);
    const [customerSearch, setCustomerSearch] = useState("");
    const [productSearch, setProductSearch] = useState("");
    const [feedback, setFeedback] = useState<{ type: "success" | "error"; message: string } | null>(null);
    const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});
    const [loadingOrders, setLoadingOrders] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [form, setForm] = useState<ManualOrderFormState>(createInitialForm());
    const [recentlyAddedId, setRecentlyAddedId] = useState<number | null>(null);
    const [proofPreview, setProofPreview] = useState<string | null>(null);
    const [isProofDragActive, setIsProofDragActive] = useState(false);
    const statusLabels: Record<string, string> = {
        approved: t("dashboard:fieldSales.status.approved"),
        rejected: t("dashboard:fieldSales.status.rejected"),
        submitted: t("dashboard:fieldSales.status.submitted"),
    };

    const totals = useMemo(() => {
        const units = form.items.reduce((sum, item) => sum + item.quantity, 0);
        const amount = form.items.reduce((sum, item) => sum + item.line_total, 0);

        return { units, amount };
    }, [form.items]);
    const customerFieldsRequired = true;
    const validationFieldLabels: Record<string, string> = {
        first_name: t("dashboard:fieldSales.customer.firstName"),
        last_name: t("dashboard:fieldSales.customer.lastName"),
        email: t("dashboard:fieldSales.customer.email"),
        phone_number: t("dashboard:fieldSales.customer.phoneNumber"),
        sold_at: t("dashboard:fieldSales.customer.soldAt"),
        address: t("dashboard:fieldSales.customer.address"),
        city: t("dashboard:fieldSales.customer.city"),
        province: t("dashboard:fieldSales.customer.province"),
        postal_code: t("dashboard:fieldSales.customer.postalCode"),
        proof_photo: t("dashboard:fieldSales.proof.title"),
        products: t("dashboard:fieldSales.products.title"),
    };

    const submittedCount = manualOrders.filter((order) => order.manual_sale_status === "submitted").length;
    const approvedValue = manualOrders
        .filter((order) => order.manual_sale_status === "approved")
        .reduce((sum, order) => sum + Number(order.total || 0), 0);

    const fetchManualOrders = async () => {
        setLoadingOrders(true);
        try {
            const response = await ApiClient.agent.getManualOrders(8, 1);
            setManualOrders(response.data.data.data ?? []);
        } catch (error) {
            console.error("Error fetching manual orders:", error);
        } finally {
            setLoadingOrders(false);
        }
    };

    useEffect(() => {
        fetchManualOrders();
    }, []);

    useEffect(() => {
        if (!form.proofPhoto) {
            setProofPreview(null);
            return;
        }

        const objectUrl = URL.createObjectURL(form.proofPhoto);
        setProofPreview(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [form.proofPhoto]);

    useEffect(() => {
        if (customerSearch.trim().length < 1) {
            setCustomerResults([]);
            return;
        }

        const timeout = window.setTimeout(async () => {
            try {
                const response = await ApiClient.agent.lookupManualOrderCustomers(customerSearch.trim());
                setCustomerResults(response.data.data ?? []);
            } catch (error) {
                console.error("Error fetching customers:", error);
            }
        }, 250);

        return () => window.clearTimeout(timeout);
    }, [customerSearch]);

    useEffect(() => {
        const searchQuery = productSearch.trim();
        if (searchQuery.length === 0) {
            setProductResults([]);
            return;
        }

        const timeout = window.setTimeout(async () => {
            try {
                // Run both in parallel for speed, giving priority to Semantic results (AI Search)
                const [semanticRes, lookupRes] = await Promise.allSettled([
                    ApiClient.search.semanticSearch(searchQuery),
                    ApiClient.agent.lookupManualOrderProducts(searchQuery.length === 1 ? searchQuery + " " : searchQuery)
                ]);

                const finalResults: AgentManualOrderLookupProduct[] = [];
                const seenIds = new Set<number>();

                // 1. Add Semantic Results First (AI Priority)
                if (semanticRes.status === "fulfilled") {
                    const semanticData = semanticRes.value.data.data;
                    const semanticProducts = (semanticData?.semantic_results || semanticData?.products?.data || []) as any[];

                    semanticProducts.forEach((p: any) => {
                        if (!seenIds.has(p.id)) {
                            seenIds.add(p.id);
                            finalResults.push({
                                id: p.id,
                                name: p.name,
                                slug: p.slug,
                                image: p.images?.main_image || p.cheapestVariant?.main_image || p.image,
                                variants: (p.variants || []).map((v: any) => ({
                                    id: v.id,
                                    variant_name: v.variant_name || v.name,
                                    variant_value: v.variant_value || v.value,
                                    agent_price: v.agent_price || v.unit_price_usd || v.price,
                                    price_after_discount: v.price_after_discount || v.unit_price_usd || v.price,
                                    unit_price_usd: v.unit_price_usd || v.price,
                                    total_quantity: v.total_quantity || 0,
                                })),
                            });
                        }
                    });
                }

                // 2. Add Regular Lookup Results (Literal matches) as secondary
                if (lookupRes.status === "fulfilled") {
                    const lookupProducts = (lookupRes.value.data.data ?? []) as AgentManualOrderLookupProduct[];
                    lookupProducts.forEach((p) => {
                        if (!seenIds.has(p.id)) {
                            seenIds.add(p.id);
                            finalResults.push(p);
                        }
                    });
                }

                setProductResults(finalResults);
            } catch (error) {
                console.error("Error fetching products:", error);
            }
        }, 250);

        return () => window.clearTimeout(timeout);
    }, [productSearch]);

    const clearValidationError = (key: string) => {
        setValidationErrors((current) => {
            if (!(key in current)) {
                return current;
            }

            const next = { ...current };
            delete next[key];
            return next;
        });
    };

    const setFormField = <K extends keyof ManualOrderFormState>(key: K, value: ManualOrderFormState[K]) => {
        setForm((current) => ({ ...current, [key]: value }));

        const fieldErrorMap: Partial<Record<keyof ManualOrderFormState, string>> = {
            firstName: "first_name",
            lastName: "last_name",
            email: "email",
            phoneNumber: "phone_number",
            soldAt: "sold_at",
            address: "address",
            city: "city",
            province: "province",
            postalCode: "postal_code",
            proofPhoto: "proof_photo",
        };

        const errorKey = fieldErrorMap[key];
        if (errorKey) {
            clearValidationError(errorKey);
        }
    };

    const setProofPhoto = (file: File | null) => {
        if (!file) {
            setFormField("proofPhoto", null);
            return;
        }

        if (!file.type.startsWith("image/")) {
            return;
        }

        setFormField("proofPhoto", file);
        clearValidationError("proof_photo");
    };

    const selectCustomer = (customer: AgentManualOrderLookupCustomer) => {
        const parts = customer.full_name.split(" ");
        setForm((current) => ({
            ...current,
            userId: customer.id,
            firstName: parts[0] ?? current.firstName,
            lastName: parts.slice(1).join(" ") || current.lastName,
            email: customer.email ?? current.email,
            phoneNumber: customer.phone_number ?? current.phoneNumber,
        }));
        setCustomerSearch(customer.full_name);
        setCustomerResults([]);
        setValidationErrors((current) => {
            const next = { ...current };
            delete next.first_name;
            delete next.last_name;
            delete next.email;
            delete next.phone_number;
            delete next.address;
            delete next.city;
            delete next.province;
            delete next.postal_code;
            return next;
        });
    };

    const addVariant = (product: AgentManualOrderLookupProduct, variantId: number) => {
        const variant = product.variants.find((item) => item.id === variantId);
        if (!variant) {
            return;
        }

        // Trigger premium animation state
        setRecentlyAddedId(variantId);
        setTimeout(() => setRecentlyAddedId(null), 2000);

        const unitPrice = getAgentLookupPrice(variant);

        setForm((current) => {
            const existingIndex = current.items.findIndex((item) => item.product_variant_id === variant.id);

            if (existingIndex >= 0) {
                const items = [...current.items];
                const nextQuantity = items[existingIndex].quantity + 1;
                items[existingIndex] = {
                    ...items[existingIndex],
                    quantity: nextQuantity,
                    line_total: nextQuantity * items[existingIndex].unit_price,
                };

                return { ...current, items };
            }

            return {
                ...current,
                items: [
                    ...current.items,
                    {
                        product_id: product.id,
                        product_name: product.name,
                        product_variant_id: variant.id,
                        variant_value: variant.variant_value,
                        quantity: 1,
                        unit_price: unitPrice,
                        line_total: unitPrice,
                        stock: variant.total_quantity,
                    },
                ],
            };
        });

        clearValidationError("products");
    };

    const updateQuantity = (variantId: number, quantity: number) => {
        setForm((current) => ({
            ...current,
            items: current.items.map((item) =>
                item.product_variant_id === variantId
                    ? {
                        ...item,
                        quantity,
                        line_total: quantity * item.unit_price,
                    }
                    : item
            ),
        }));
    };

    const removeItem = (variantId: number) => {
        setForm((current) => ({
            ...current,
            items: current.items.filter((item) => item.product_variant_id !== variantId),
        }));
    };

    const submitOrder = async () => {
        if (submitting) {
            return;
        }

        setFeedback(null);
        setValidationErrors({});
        setSubmitting(true);

        try {
            const payload = new FormData();

            if (form.userId) {
                payload.append("user_id", String(form.userId));
            }

            payload.append("first_name", form.firstName);
            payload.append("last_name", form.lastName);
            payload.append("email", form.email);
            payload.append("phone_number", form.phoneNumber);
            payload.append("company_name", form.companyName);
            payload.append("address", form.address);
            payload.append("city", form.city);
            payload.append("province", form.province);
            payload.append("postal_code", form.postalCode);
            payload.append("sold_at", form.soldAt);

            if (form.proofPhoto) {
                payload.append("proof_photo", form.proofPhoto);
            }

            form.items.forEach((item, index) => {
                payload.append(`products[${index}][product_id]`, String(item.product_id));
                payload.append(`products[${index}][product_variant_id]`, String(item.product_variant_id));
                payload.append(`products[${index}][quantity]`, String(item.quantity));
            });

            const response = await ApiClient.agent.submitManualOrder(payload);
            const order = response.data.data;
            const isAutoApproved = order.manual_sale_status === "approved";

            setForm(createInitialForm());
            setCustomerSearch("");
            setCustomerResults([]);
            setProductSearch("");
            setProductResults([]);
            setValidationErrors({});
            setFeedback({
                type: "success",
                message: isAutoApproved
                    ? t("dashboard:fieldSales.feedback.approved")
                    : t("dashboard:fieldSales.feedback.submitted")
            });
            fetchManualOrders();
        } catch (error: any) {
            console.error("Error submitting manual order:", error);

            const responseErrors = error?.response?.data?.errors as Record<string, string[]> | undefined;

            if (responseErrors && typeof responseErrors === "object") {
                const normalizedErrors = Object.entries(responseErrors).reduce<Record<string, string>>((carry, [key, messages]) => {
                    const [rootKey] = key.split(".");
                    if (!carry[rootKey] && Array.isArray(messages) && messages.length > 0) {
                        carry[rootKey] = messages[0];
                    }
                    return carry;
                }, {});

                setValidationErrors(normalizedErrors);

                const fieldNames = Object.keys(normalizedErrors)
                    .map((key) => validationFieldLabels[key] ?? key)
                    .filter((value, index, values) => values.indexOf(value) === index);

                setFeedback({
                    type: "error",
                    message: fieldNames.length > 0
                        ? t("dashboard:fieldSales.feedback.validationSummary", { fields: fieldNames.join("، ") })
                        : t("dashboard:fieldSales.feedback.error"),
                });
            } else {
                setValidationErrors({});
                setFeedback({
                    type: "error",
                    message: error?.response?.data?.message || t("dashboard:fieldSales.feedback.error"),
                });
            }
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="flex h-screen w-full overflow-hidden bg-[#f6f2e8]">
            <Sidebar sidebarOpen={sidebarOpen} setSidebarOpen={setSidebarOpen} />
            <div className="flex min-w-0 flex-1 flex-col">
                <Header setSidebarOpen={setSidebarOpen} title={t("dashboard:fieldSales.headerTitle")} />
                <div className="flex-1 overflow-y-auto overflow-x-hidden">
                    <div className="min-h-full bg-[radial-gradient(circle_at_top_left,_rgba(177,44,46,0.14),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(20,73,149,0.12),_transparent_24%),linear-gradient(180deg,#faf7f5_0%,#f4efec_100%)] px-4 py-6 sm:px-6">
                        <section className="rounded-[34px] border border-red-100 bg-[linear-gradient(135deg,#fff8f8_0%,#fff0f0_58%,#eef4ff_100%)] px-6 py-7 text-slate-900 shadow-[0_24px_70px_rgba(177,44,46,0.14)]">
                            <div className="flex flex-col gap-8 xl:flex-row xl:items-end xl:justify-between">
                                <div className="max-w-2xl">
                                    <span className="inline-flex items-center rounded-full border border-red-200 bg-white/80 px-4 py-1 text-xs uppercase tracking-[0.28em] text-[#B12C2E]">
                                        {t("dashboard:fieldSales.heroBadge")}
                                    </span>
                                    <h1 className="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                                        {t("dashboard:fieldSales.heroTitle")}
                                    </h1>
                                    <p className="mt-3 max-w-xl text-sm leading-6 text-slate-600">
                                        {t("dashboard:fieldSales.heroDescription")}
                                    </p>
                                </div>
                                <div className="grid gap-4 md:grid-cols-3">
                                    <StatCard label={t("dashboard:fieldSales.stats.pendingReview")} value={String(submittedCount)} accent="bg-[#B12C2E]" />
                                    <StatCard label={t("dashboard:fieldSales.stats.approvedValue")} value={formatMoney(approvedValue)} accent="bg-emerald-400" />
                                    <StatCard label={t("dashboard:fieldSales.stats.signedInAgent")} value={user?.full_name || "--"} accent="bg-sky-400" />
                                </div>
                            </div>
                        </section>

                        <section className="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1.3fr)_minmax(340px,0.7fr)]">
                            <div className="rounded-[34px] border border-white/70 bg-white/85 p-5 shadow-[0_20px_70px_rgba(15,23,42,0.08)] backdrop-blur sm:p-6">
                                <div className="flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-xs uppercase tracking-[0.24em] text-slate-400">{t("dashboard:fieldSales.composer.eyebrow")}</p>
                                        <h2 className="mt-2 text-2xl font-semibold text-slate-900">{t("dashboard:fieldSales.composer.title")}</h2>
                                    </div>
                                    <div className="inline-flex items-center gap-2 rounded-full bg-red-50 px-4 py-2 text-sm font-medium text-[#B12C2E]">
                                        <FiPackage className="h-4 w-4" />
                                        {t("dashboard:fieldSales.composer.unitsSelected", { count: totals.units })}
                                    </div>
                                </div>

                                <div className="mt-6 grid gap-6">
                                    <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
                                        <div className="rounded-[28px] border border-slate-100 bg-slate-50/80 p-5">
                                            <div className="flex items-center gap-3">
                                                <div className="rounded-2xl bg-red-100 p-3 text-[#B12C2E]">
                                                    <FiUser className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <h3 className="text-lg font-semibold text-slate-900">{t("dashboard:fieldSales.customer.title")}</h3>
                                                    <p className="text-sm text-slate-500">{t("dashboard:fieldSales.customer.description")}</p>
                                                </div>
                                            </div>

                                            <div className="relative mt-5">
                                                <FiSearch className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                                <input
                                                    value={customerSearch}
                                                    onChange={(event) => setCustomerSearch(event.target.value)}
                                                    placeholder={t("dashboard:fieldSales.customer.searchPlaceholder")}
                                                    className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 shadow-sm outline-none transition focus:border-[#B12C2E] focus:ring-4 focus:ring-red-100"
                                                />
                                                {customerResults.length > 0 && (
                                                    <div className="absolute z-20 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                                                        {customerResults.map((customer) => (
                                                            <button
                                                                key={customer.id}
                                                                type="button"
                                                                onClick={() => selectCustomer(customer)}
                                                                className="flex w-full items-start justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-red-50 last:border-b-0"
                                                            >
                                                                <span>
                                                                    <span className="block text-sm font-medium text-slate-900">{customer.full_name}</span>
                                                                    <span className="block text-xs text-slate-500">{customer.email}</span>
                                                                </span>
                                                                <span className="text-xs text-slate-400">{customer.phone_number || "--"}</span>
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            <div className="mt-5 grid gap-4 md:grid-cols-2">
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.firstName")} value={form.firstName} onChange={(value) => setFormField("firstName", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.first_name} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.lastName")} value={form.lastName} onChange={(value) => setFormField("lastName", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.last_name} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.email")} type="email" value={form.email} onChange={(value) => setFormField("email", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.email} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.phoneNumber")} value={form.phoneNumber} onChange={(value) => setFormField("phoneNumber", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.phone_number} />
                                                </div>
                                                <Input label={t("dashboard:fieldSales.customer.companyName")} value={form.companyName} onChange={(value) => setFormField("companyName", value)} />
                                                <div>
                                                    <PremiumDateTimePicker
                                                        label={t("dashboard:fieldSales.customer.soldAt")}
                                                        value={form.soldAt}
                                                        onChange={(value) => setFormField("soldAt", value)}
                                                        required
                                                        locale={t("dashboard:fieldSales.locale", { defaultValue: "en-US" })}
                                                        translations={{
                                                            placeholder: t("dashboard:fieldSales.datePicker.placeholder"),
                                                            today: t("dashboard:fieldSales.datePicker.today"),
                                                            clear: t("dashboard:fieldSales.datePicker.clear"),
                                                            hour: t("dashboard:fieldSales.datePicker.hour"),
                                                            minute: t("dashboard:fieldSales.datePicker.minute"),
                                                            period: t("dashboard:fieldSales.datePicker.period"),
                                                            weekdaysCompact: t("dashboard:fieldSales.datePicker.weekdaysCompact", { returnObjects: true }) as string[],
                                                        }}
                                                    />
                                                    <ValidationMessage message={validationErrors.sold_at} />
                                                </div>
                                                <div className="md:col-span-2">
                                                    <Input label={t("dashboard:fieldSales.customer.address")} value={form.address} onChange={(value) => setFormField("address", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.address} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.city")} value={form.city} onChange={(value) => setFormField("city", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.city} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.province")} value={form.province} onChange={(value) => setFormField("province", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.province} />
                                                </div>
                                                <div>
                                                    <Input label={t("dashboard:fieldSales.customer.postalCode")} value={form.postalCode} onChange={(value) => setFormField("postalCode", value)} required={customerFieldsRequired} />
                                                    <ValidationMessage message={validationErrors.postal_code} />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="rounded-[28px] border border-slate-100 bg-[linear-gradient(180deg,#fffafa_0%,#fff1f1_100%)] p-5">
                                            <div className="flex items-center gap-3">
                                                <div className="rounded-2xl bg-[#B12C2E] p-3 text-white">
                                                    <FiCamera className="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <h3 className="text-lg font-semibold text-slate-900">
                                                        {t("dashboard:fieldSales.proof.title")}
                                                        <span className="ms-1 text-[#B12C2E]">*</span>
                                                    </h3>
                                                    <p className="text-sm text-slate-600">{t("dashboard:fieldSales.proof.description")}</p>
                                                </div>
                                            </div>

                                            <label
                                                onDragEnter={(event) => {
                                                    event.preventDefault();
                                                    setIsProofDragActive(true);
                                                }}
                                                onDragOver={(event) => {
                                                    event.preventDefault();
                                                    setIsProofDragActive(true);
                                                }}
                                                onDragLeave={(event) => {
                                                    event.preventDefault();
                                                    if (event.currentTarget.contains(event.relatedTarget as Node | null)) {
                                                        return;
                                                    }
                                                    setIsProofDragActive(false);
                                                }}
                                                onDrop={(event) => {
                                                    event.preventDefault();
                                                    setIsProofDragActive(false);
                                                    setProofPhoto(event.dataTransfer.files?.[0] || null);
                                                }}
                                                className={`mt-5 flex min-h-[240px] cursor-pointer flex-col items-center justify-center rounded-[28px] border border-dashed p-6 text-center transition ${isProofDragActive
                                                    ? "border-[#B12C2E] bg-white shadow-[0_18px_40px_rgba(177,44,46,0.14)] ring-4 ring-red-100"
                                                    : "border-red-300 bg-white/70 hover:border-[#B12C2E] hover:bg-white"
                                                    }`}
                                            >
                                                {proofPreview ? (
                                                    <img src={proofPreview} alt="Proof preview" className="h-48 w-full rounded-[22px] object-cover" />
                                                ) : (
                                                    <>
                                                        <div className={`rounded-full p-4 transition ${isProofDragActive ? "bg-[#B12C2E] text-white" : "bg-red-100 text-[#B12C2E]"
                                                            }`}>
                                                            <FiCamera className="h-7 w-7" />
                                                        </div>
                                                        <p className="mt-4 text-sm font-medium text-slate-800">{t("dashboard:fieldSales.proof.dropzoneTitle")}</p>
                                                        <p className="mt-2 text-xs text-slate-500">{t("dashboard:fieldSales.proof.dropzoneHint")}</p>
                                                    </>
                                                )}
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    className="hidden"
                                                    onChange={(event) => setProofPhoto(event.target.files?.[0] || null)}
                                                />
                                            </label>
                                            <ValidationMessage message={validationErrors.proof_photo} />

                                            <div className="mt-4 rounded-2xl bg-white/80 p-4 text-sm text-slate-600">
                                                <p className="font-medium text-slate-900">{t("dashboard:fieldSales.proof.checklistTitle")}</p>
                                                <div className="mt-3 space-y-2">
                                                    <div className="flex items-center gap-2">
                                                        <FiCheckCircle className="h-4 w-4 text-emerald-500" />
                                                        {t("dashboard:fieldSales.proof.checklistCustomer")}
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <FiCheckCircle className="h-4 w-4 text-emerald-500" />
                                                        {t("dashboard:fieldSales.proof.checklistProducts")}
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <FiClock className="h-4 w-4 text-[#B12C2E]" />
                                                        {t("dashboard:fieldSales.proof.checklistReview")}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[28px] border border-red-100 bg-[linear-gradient(180deg,#fff9f9_0%,#fff1f1_100%)] p-5 text-slate-900">
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p className="text-xs uppercase tracking-[0.28em] text-slate-400">{t("dashboard:fieldSales.products.eyebrow")}</p>
                                                <h3 className="mt-2 text-xl font-semibold">
                                                    {t("dashboard:fieldSales.products.title")}
                                                    <span className="ms-1 text-[#B12C2E]">*</span>
                                                </h3>
                                            </div>
                                            <div className="inline-flex items-center gap-2 rounded-full border border-red-200 bg-white/90 px-4 py-2 text-sm text-[#B12C2E]">
                                                <FiPlus className="h-4 w-4" />
                                                {t("dashboard:fieldSales.products.helper")}
                                            </div>
                                        </div>

                                        <div className="relative mt-5">
                                            <FiSearch className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                            <input
                                                value={productSearch}
                                                onChange={(event) => setProductSearch(event.target.value)}
                                                placeholder={t("dashboard:fieldSales.products.searchPlaceholder")}
                                                className="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#B12C2E] focus:ring-4 focus:ring-red-100"
                                            />
                                        </div>
                                        <ValidationMessage message={validationErrors.products} />

                                        <div className="mt-5 grid gap-4 lg:grid-cols-2">
                                            <AnimatePresence mode="popLayout">
                                                {productResults.length === 0 ? (
                                                    <motion.div
                                                        initial={{ opacity: 0, y: 10 }}
                                                        animate={{ opacity: 1, y: 0 }}
                                                        exit={{ opacity: 0, scale: 0.95 }}
                                                        className="rounded-[24px] border border-dashed border-red-200 bg-white/80 p-6 text-sm text-slate-500 lg:col-span-2"
                                                    >
                                                        {t("dashboard:fieldSales.products.empty")}
                                                    </motion.div>
                                                ) : (
                                                    productResults.map((product) => (
                                                        <motion.div
                                                            key={product.id}
                                                            layout
                                                            initial={{ opacity: 0, scale: 0.95 }}
                                                            animate={{ opacity: 1, scale: 1 }}
                                                            exit={{ opacity: 0, scale: 0.95 }}
                                                            transition={{ duration: 0.2 }}
                                                            className="group relative overflow-hidden rounded-[30px] border border-red-100/80 bg-[linear-gradient(135deg,rgba(255,255,255,0.98)_0%,rgba(255,246,246,0.98)_58%,rgba(237,243,255,0.92)_100%)] p-5 text-left transition-colors duration-300"
                                                        >
                                                            <div className="relative flex items-center gap-4">
                                                                <div className="relative flex h-24 w-24 items-center justify-center overflow-hidden rounded-[26px] border border-white/70 bg-[linear-gradient(180deg,#ffffff_0%,#fff0f0_100%)] shadow-[inset_0_1px_0_rgba(255,255,255,0.9),0_14px_24px_rgba(177,44,46,0.12)]">
                                                                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(177,44,46,0.12),transparent_60%),radial-gradient(circle_at_bottom,rgba(20,73,149,0.10),transparent_68%)]" />
                                                                    {product.image ? (
                                                                        <motion.img
                                                                            whileHover={{ scale: 1.05 }}
                                                                            src={product.image}
                                                                            alt={product.name}
                                                                            className="relative h-20 w-20 object-contain transition duration-300"
                                                                        />
                                                                    ) : (
                                                                        <div className="relative flex h-full items-center justify-center text-[#B12C2E]">
                                                                            <FiPackage className="h-7 w-7" />
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div className="min-w-0 flex-1">
                                                                    <div className="flex flex-wrap items-center gap-2">
                                                                        <span className="inline-flex items-center rounded-full border border-red-200 bg-white/80 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.24em] text-[#B12C2E]">
                                                                            {t("dashboard:fieldSales.products.helper")}
                                                                        </span>
                                                                    </div>
                                                                    <h4 className="mt-3 line-clamp-2 text-lg font-semibold leading-7 text-slate-900">{product.name}</h4>
                                                                    <p className="mt-2 text-[11px] uppercase tracking-[0.32em] text-slate-400">{product.slug}</p>
                                                                    <div className="mt-4 flex flex-wrap gap-2">
                                                                        {product.variants.map((variant) => {
                                                                            const variantLabel = [variant.variant_name, variant.variant_value]
                                                                                .filter(Boolean)
                                                                                .join(": ");
                                                                            const isRecentlyAdded = recentlyAddedId === variant.id;

                                                                            return (
                                                                                <motion.button
                                                                                    key={variant.id}
                                                                                    whileHover={{ scale: 1.02 }}
                                                                                    whileTap={{ scale: 0.98 }}
                                                                                    type="button"
                                                                                    onClick={(event) => {
                                                                                        event.stopPropagation();
                                                                                        addVariant(product, variant.id);
                                                                                    }}
                                                                                    className={`cursor-pointer relative overflow-hidden rounded-[20px] border px-3.5 py-2.5 text-left text-xs shadow-sm transition-all duration-300 ${isRecentlyAdded
                                                                                        ? "border-emerald-200 bg-emerald-50 text-emerald-700 shadow-emerald-100"
                                                                                        : "border-red-200 bg-white/88 text-slate-700 hover:border-red-300 hover:bg-red-50 hover:shadow-[0_10px_24px_rgba(177,44,46,0.12)]"
                                                                                        }`}
                                                                                >
                                                                                    <AnimatePresence mode="wait">
                                                                                        {isRecentlyAdded ? (
                                                                                            <motion.div
                                                                                                key="added"
                                                                                                initial={{ opacity: 0, y: 10 }}
                                                                                                animate={{ opacity: 1, y: 0 }}
                                                                                                exit={{ opacity: 0, y: -10 }}
                                                                                                className="flex items-center gap-2"
                                                                                            >
                                                                                                <FiCheckCircle className="h-4 w-4" />
                                                                                                <span className="font-semibold uppercase tracking-wider">{t("dashboard:fieldSales.status.added")}</span>
                                                                                            </motion.div>
                                                                                        ) : (
                                                                                            <motion.div
                                                                                                key="normal"
                                                                                                initial={{ opacity: 0 }}
                                                                                                animate={{ opacity: 1 }}
                                                                                                exit={{ opacity: 0 }}
                                                                                            >
                                                                                                <span className="block font-medium">{variantLabel || t("dashboard:fieldSales.products.defaultVariant")}</span>
                                                                                                <span className="mt-1 block text-[11px] text-slate-500">
                                                                                                    {formatMoney(getAgentLookupPrice(variant))} - {t("dashboard:fieldSales.products.stock", { count: variant.total_quantity })}
                                                                                                </span>
                                                                                            </motion.div>
                                                                                        )}
                                                                                    </AnimatePresence>
                                                                                </motion.button>
                                                                            );
                                                                        })}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </motion.div>
                                                    ))
                                                )}
                                            </AnimatePresence>
                                        </div>
                                    </div>

                                    <div className="rounded-[28px] border border-slate-100 bg-slate-50 p-5">
                                        <div className="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p className="text-xs uppercase tracking-[0.28em] text-slate-400">{t("dashboard:fieldSales.summary.eyebrow")}</p>
                                                <h3 className="mt-2 text-xl font-semibold text-slate-900">{t("dashboard:fieldSales.summary.title")}</h3>
                                            </div>
                                            <div className="rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm">
                                                {t("dashboard:fieldSales.summary.lineCount", { count: form.items.length })}
                                            </div>
                                        </div>

                                        <div className="mt-4 space-y-3">
                                            <AnimatePresence mode="popLayout">
                                                {form.items.length === 0 ? (
                                                    <motion.div
                                                        initial={{ opacity: 0, scale: 0.95 }}
                                                        animate={{ opacity: 1, scale: 1 }}
                                                        exit={{ opacity: 0, scale: 0.95 }}
                                                        className="rounded-[24px] border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500"
                                                    >
                                                        {t("dashboard:fieldSales.summary.empty")}
                                                    </motion.div>
                                                ) : (
                                                    form.items.map((item) => (
                                                        <motion.div
                                                            key={item.product_variant_id}
                                                            layout
                                                            initial={{ opacity: 0, x: -20, scale: 0.95 }}
                                                            animate={{ opacity: 1, x: 0, scale: 1 }}
                                                            exit={{ opacity: 0, x: 20, scale: 0.95 }}
                                                            className="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm"
                                                        >
                                                            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                                                <div>
                                                                    <p className="text-base font-semibold text-slate-900">{item.product_name}</p>
                                                                    <p className="mt-1 text-sm text-slate-500">{item.variant_value || t("dashboard:fieldSales.products.defaultVariant")}</p>
                                                                    <p className="mt-2 text-xs uppercase tracking-[0.24em] text-slate-400">{t("dashboard:fieldSales.summary.stockAvailable", { count: item.stock ?? "--" })}</p>
                                                                </div>
                                                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                                                                    <div className="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 p-1">
                                                                        <motion.button
                                                                            whileTap={{ scale: 0.9 }}
                                                                            type="button"
                                                                            onClick={() => updateQuantity(item.product_variant_id, Math.max(1, item.quantity - 1))}
                                                                            className="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
                                                                        >
                                                                            -
                                                                        </motion.button>
                                                                        <span className="min-w-[52px] text-center text-sm font-semibold text-slate-900">{item.quantity}</span>
                                                                        <motion.button
                                                                            whileTap={{ scale: 0.9 }}
                                                                            type="button"
                                                                            onClick={() => updateQuantity(item.product_variant_id, item.quantity + 1)}
                                                                            className="rounded-full px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
                                                                        >
                                                                            +
                                                                        </motion.button>
                                                                    </div>
                                                                    <div className="min-w-[120px] text-right">
                                                                        <p className="text-xs uppercase tracking-[0.24em] text-slate-400">{t("dashboard:fieldSales.pipeline.total")}</p>
                                                                        <p className="mt-1 text-lg font-semibold text-slate-900">{formatMoney(item.line_total)}</p>
                                                                    </div>
                                                                    <motion.button
                                                                        whileHover={{ scale: 1.1 }}
                                                                        whileTap={{ scale: 0.9 }}
                                                                        type="button"
                                                                        onClick={() => removeItem(item.product_variant_id)}
                                                                        className="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 p-3 text-rose-600 transition hover:bg-rose-100"
                                                                    >
                                                                        <FiTrash2 className="h-4 w-4" />
                                                                    </motion.button>
                                                                </div>
                                                            </div>
                                                        </motion.div>
                                                    ))
                                                )}
                                            </AnimatePresence>
                                        </div>

                                        <div className="mt-5 rounded-[24px] border border-red-100 bg-[linear-gradient(180deg,#fff6f6_0%,#fde8e8_100%)] p-4 text-slate-900 sm:p-5">
                                            <div className="flex items-center justify-between gap-3 text-sm text-slate-600">
                                                <span>{t("dashboard:fieldSales.summary.units")}</span>
                                                <motion.span
                                                    key={totals.units}
                                                    initial={{ scale: 0.8, opacity: 0 }}
                                                    animate={{ scale: 1, opacity: 1 }}
                                                    className="font-semibold"
                                                >
                                                    {totals.units}
                                                </motion.span>
                                            </div>
                                            <div className="mt-3 flex items-center justify-between gap-3 text-sm text-slate-600">
                                                <span>{t("dashboard:fieldSales.summary.subtotal")}</span>
                                                <span>{formatMoney(totals.amount)}</span>
                                            </div>
                                            <div className="mt-4 border-t border-red-200 pt-4 max-[500px]:space-y-4 sm:mt-5">
                                                <div className="flex items-end justify-between gap-4 max-[500px]:flex-col max-[500px]:items-stretch sm:items-end">
                                                    <div className="max-[500px]:text-center">
                                                        <p className="text-xs uppercase tracking-[0.28em] text-slate-500">{t("dashboard:fieldSales.summary.ready")}</p>
                                                        <motion.p
                                                            key={totals.amount}
                                                            initial={{ y: 10, opacity: 0 }}
                                                            animate={{ y: 0, opacity: 1 }}
                                                            className="mt-2 text-2xl font-semibold leading-tight max-[500px]:text-[2rem]"
                                                        >
                                                            {formatMoney(totals.amount)}
                                                        </motion.p>
                                                    </div>
                                                    <motion.button
                                                        whileHover={{ scale: 1.02 }}
                                                        whileTap={{ scale: 0.98 }}
                                                        type="button"
                                                        disabled={submitting}
                                                        onClick={submitOrder}
                                                        className="rounded-full bg-[#B12C2E] cursor-pointer px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#9A2527] disabled:cursor-not-allowed disabled:opacity-60 max-[500px]:w-full max-[500px]:px-5 max-[500px]:py-3.5"
                                                    >
                                                        {submitting ? t("dashboard:fieldSales.summary.submitting") : t("dashboard:fieldSales.summary.submit")}
                                                    </motion.button>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50/50 px-4 py-3 text-xs font-medium text-emerald-700 border border-emerald-100/50">
                                            <div className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                                <FiCheckCircle className="h-3 w-3" />
                                            </div>
                                            <p>{t("dashboard:fieldSales.summary.autoApproveNotice", { threshold: "1,000" })}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <aside className="space-y-6">
                                <div className="rounded-[34px] border border-white/70 bg-white/90 p-5 shadow-[0_20px_70px_rgba(15,23,42,0.08)]">
                                    <p className="text-xs uppercase tracking-[0.28em] text-slate-400">{t("dashboard:fieldSales.pipeline.eyebrow")}</p>
                                    <h2 className="mt-2 text-2xl font-semibold text-slate-900">{t("dashboard:fieldSales.pipeline.title")}</h2>
                                    <p className="mt-2 text-sm text-slate-500">
                                        {t("dashboard:fieldSales.pipeline.description")}
                                    </p>

                                    <div className="mt-5 space-y-4">
                                        {loadingOrders ? (
                                            <div className="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                                                {t("dashboard:fieldSales.pipeline.loading")}
                                            </div>
                                        ) : manualOrders.length === 0 ? (
                                            <div className="rounded-[24px] border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                                                {t("dashboard:fieldSales.pipeline.empty")}
                                            </div>
                                        ) : (
                                            manualOrders.map((order) => (
                                                <div key={order.id} className="rounded-[26px] border border-slate-200 bg-slate-50/80 p-4">
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div>
                                                            <p className="text-sm font-semibold text-slate-900">{order.customer_name || t("dashboard:fieldSales.pipeline.guestCustomer")}</p>
                                                            <p className="mt-1 text-xs text-slate-500">{t("dashboard:fieldSales.pipeline.orderNumber", { id: order.id })}</p>
                                                        </div>
                                                        <span className={`rounded-full px-3 py-1 text-xs font-semibold ring-1 ${statusClassName(order.manual_sale_status)}`}>
                                                            {statusLabels[order.manual_sale_status] || order.manual_sale_status}
                                                        </span>
                                                    </div>

                                                    <div className="mt-4 h-40 w-full overflow-hidden rounded-[22px] bg-slate-100 group">
                                                        <img
                                                            src={order.proof_photo_url || "/images/professional_order_placeholder.png"}
                                                            alt={`Manual order ${order.id}`}
                                                            onError={(e) => {
                                                                const target = e.target as HTMLImageElement;
                                                                if (target.src !== `${window.location.origin}/images/professional_order_placeholder.png`) {
                                                                    target.src = "/images/professional_order_placeholder.png";
                                                                }
                                                            }}
                                                            className={`h-full w-full ${order.proof_photo_url ? 'object-cover' : 'object-contain p-4'} transition-all duration-300 group-hover:scale-105`}
                                                        />
                                                    </div>

                                                    <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                                        <div className="rounded-2xl bg-white p-3">
                                                            <p className="text-xs uppercase tracking-[0.24em] text-slate-400">{t("dashboard:fieldSales.pipeline.total")}</p>
                                                            <p className="mt-2 font-semibold text-slate-900">{formatMoney(Number(order.total || 0))}</p>
                                                        </div>
                                                        <div className="rounded-2xl bg-white p-3">
                                                            <p className="text-xs uppercase tracking-[0.24em] text-slate-400">{t("dashboard:fieldSales.pipeline.submitted")}</p>
                                                            <p className="mt-2 font-semibold text-slate-900">{formatDate(order.sold_at || order.created_at)}</p>
                                                        </div>
                                                    </div>

                                                    <div className="mt-4 flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                                                        <span>{t("dashboard:fieldSales.pipeline.itemsCount", { count: order.items_count ?? 0 })}</span>
                                                        {order.manual_sale_status === "approved" ? (
                                                            <span className="inline-flex items-center gap-2 text-emerald-600">
                                                                <FiCheckCircle className="h-4 w-4" />
                                                                {t("dashboard:fieldSales.status.approved")}
                                                            </span>
                                                        ) : order.manual_sale_status === "rejected" ? (
                                                            <span className="inline-flex items-center gap-2 text-rose-600">
                                                                <FiXCircle className="h-4 w-4" />
                                                                {t("dashboard:fieldSales.status.rejected")}
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-2 text-[#B12C2E]">
                                                                <FiClock className="h-4 w-4" />
                                                                {t("dashboard:fieldSales.status.submitted")}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </div>

                                <div className="rounded-[34px] border border-blue-100 bg-[linear-gradient(180deg,#f8fbff_0%,#eef4ff_100%)] p-5 text-slate-900 shadow-[0_20px_70px_rgba(148,163,184,0.14)]">
                                    <p className="text-xs uppercase tracking-[0.28em] text-slate-400">{t("dashboard:fieldSales.ops.eyebrow")}</p>
                                    <h3 className="mt-2 text-xl font-semibold">{t("dashboard:fieldSales.ops.title")}</h3>
                                    <p className="mt-3 text-sm leading-6 text-slate-600">
                                        {t("dashboard:fieldSales.ops.description")}
                                    </p>
                                    <div className="mt-5 space-y-3 text-sm text-slate-700">
                                        <div className="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3">{t("dashboard:fieldSales.ops.point1")}</div>
                                        <div className="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3">{t("dashboard:fieldSales.ops.point2")}</div>
                                        <div className="rounded-2xl border border-blue-100 bg-white/85 px-4 py-3">{t("dashboard:fieldSales.ops.point3")}</div>
                                    </div>
                                </div>
                            </aside>
                        </section>
                    </div>
                </div>
            </div>

            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {feedback && (
                <FeedbackPopup
                    type={feedback.type}
                    message={feedback.message}
                    onClose={() => setFeedback(null)}
                    translations={{
                        successBadge: t("dashboard:fieldSales.feedbackPopup.successBadge"),
                        successTitle: t("dashboard:fieldSales.feedbackPopup.successTitle"),
                        successHint: t("dashboard:fieldSales.feedbackPopup.successHint"),
                        successAction: t("dashboard:fieldSales.feedbackPopup.successAction"),
                        errorBadge: t("dashboard:fieldSales.feedbackPopup.errorBadge"),
                        errorTitle: t("dashboard:fieldSales.feedbackPopup.errorTitle"),
                        errorHint: t("dashboard:fieldSales.feedbackPopup.errorHint"),
                        errorAction: t("dashboard:fieldSales.feedbackPopup.errorAction"),
                    }}
                />
            )}
        </div>
    );
};

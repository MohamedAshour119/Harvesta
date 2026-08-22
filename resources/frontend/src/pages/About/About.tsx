import { useTranslation } from "react-i18next";
import { FaHandshake, FaIndustry, FaLeaf } from "react-icons/fa";

const aboutContent = {
  en: {
    title: "About Cairo 3A",
    subtitle:
      "Cairo 3A is a trusted Egyptian group operating across integrated agri-commodity trading, manufacturing, and supply chain services for local and regional markets.",
    storyTitle: "Who We Are",
    storyParagraphs: [
      "Since 1981, Cairo 3A has grown into a vertically integrated group built to serve the agri-commodity sector with strong operational capabilities, disciplined execution, and long-term market knowledge.",
      "The group brings together complementary companies across trading, manufacturing, logistics, and distribution to create a connected value chain that helps move essential products efficiently and reliably.",
    ],
    stats: [
      { value: "Since 1981", label: "Long-term market presence" },
      { value: "6", label: "Integrated operating companies" },
      { value: "Local & Regional", label: "Markets served through the value chain" },
    ],
    valuesTitle: "What Drives Cairo 3A",
    values: [
      {
        title: "Trusted Partnerships",
        description:
          "We focus on building dependable relationships with customers, suppliers, and partners through consistency, accountability, and market insight.",
      },
      {
        title: "Integrated Execution",
        description:
          "Our model connects sourcing, manufacturing, logistics, and distribution so businesses can rely on a smoother and more coordinated supply chain.",
      },
      {
        title: "Sustainable Growth",
        description:
          "We aim to create long-term value by investing in operational excellence, modern capabilities, and products that answer real market demand.",
      },
    ],
    ctaTitle: "Explore Cairo 3A Products",
    ctaText:
      "Browse our catalog to discover the product range supported by Cairo 3A's integrated supply and distribution network.",
    ctaButton: "View Products",
  },
  ar: {
    title: "عن كايرو 3A",
    subtitle:
      "كايرو 3A مجموعة مصرية موثوقة تعمل في تجارة وتصنيع السلع الزراعية المتكاملة وخدمات سلاسل الإمداد لخدمة الأسواق المحلية والإقليمية.",
    storyTitle: "من نحن",
    storyParagraphs: [
      "منذ عام 1981، نمت كايرو 3A لتصبح مجموعة متكاملة رأسيًا تخدم قطاع السلع الزراعية من خلال خبرة تشغيلية قوية وتنفيذ منضبط وفهم عميق لاحتياجات السوق.",
      "تضم المجموعة شركات متكاملة في مجالات التجارة والتصنيع واللوجستيات والتوزيع، بما يكوّن سلسلة قيمة مترابطة تساعد على توفير المنتجات الأساسية بكفاءة وموثوقية.",
    ],
    stats: [
      { value: "منذ 1981", label: "خبرة ممتدة في السوق" },
      { value: "6", label: "شركات تشغيلية متكاملة" },
      { value: "محليًا وإقليميًا", label: "أسواق نخدمها عبر سلسلة القيمة" },
    ],
    valuesTitle: "ما الذي يقود كايرو 3A",
    values: [
      {
        title: "شراكات قائمة على الثقة",
        description:
          "نحرص على بناء علاقات طويلة الأمد مع العملاء والموردين والشركاء من خلال الالتزام والموثوقية وفهم السوق.",
      },
      {
        title: "تكامل في التنفيذ",
        description:
          "نربط بين التوريد والتصنيع واللوجستيات والتوزيع لتقديم سلسلة إمداد أكثر سلاسة وكفاءة للأعمال.",
      },
      {
        title: "نمو مستدام",
        description:
          "نركز على خلق قيمة طويلة المدى عبر التميز التشغيلي وتطوير القدرات الحديثة وتقديم منتجات تلبي الطلب الفعلي في السوق.",
      },
    ],
    ctaTitle: "استكشف منتجات كايرو 3A",
    ctaText:
      "تصفح الكتالوج لاكتشاف مجموعة المنتجات المدعومة بمنظومة كايرو 3A المتكاملة للتوريد والتوزيع.",
    ctaButton: "عرض المنتجات",
  },
} as const;

export function About() {
  const { i18n } = useTranslation();
  const isArabic = i18n.language?.startsWith("ar");
  const content = isArabic ? aboutContent.ar : aboutContent.en;

  const icons = [FaHandshake, FaIndustry, FaLeaf];

  return (
    <div className="my-12 space-y-12" dir={isArabic ? "rtl" : "ltr"}>
      <div className="mx-auto max-w-3xl text-center">
        <h1 className="mb-4 text-4xl font-bold text-gray-900">{content.title}</h1>
        <p className="text-lg leading-8 text-gray-600">{content.subtitle}</p>
      </div>

      <div className="grid items-center gap-12 md:grid-cols-2">
        <div className={isArabic ? "md:order-2" : ""}>
          <h2 className="mb-6 text-3xl font-semibold text-gray-900">{content.storyTitle}</h2>
          {content.storyParagraphs.map((paragraph) => (
            <p key={paragraph} className="mb-4 leading-8 text-gray-700 last:mb-0">
              {paragraph}
            </p>
          ))}
        </div>

        <div className="rounded-3xl bg-gradient-to-br from-[#fff6f6] via-[#fde7e7] to-[#f9d9da] p-8 shadow-sm">
          <div className="grid gap-6 text-center sm:grid-cols-3 sm:text-start">
            {content.stats.map((stat) => (
              <div key={stat.label} className="rounded-2xl bg-white/70 p-5 backdrop-blur">
                <div className="mb-2 text-3xl font-bold text-[#B12C2E]">{stat.value}</div>
                <div className="text-sm font-medium leading-6 text-gray-700">{stat.label}</div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="rounded-3xl bg-gray-50 p-8 md:p-10">
        <h2 className="mb-8 text-center text-3xl font-semibold text-gray-900">
          {content.valuesTitle}
        </h2>
        <div className="grid gap-8 md:grid-cols-3">
          {content.values.map((value, index) => {
            const Icon = icons[index];

            return (
              <div key={value.title} className="text-center">
                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#fde8e8]">
                  <Icon className="h-7 w-7 text-[#B12C2E]" />
                </div>
                <h3 className="mb-2 text-xl font-semibold text-gray-900">{value.title}</h3>
                <p className="leading-7 text-gray-600">{value.description}</p>
              </div>
            );
          })}
        </div>
      </div>

      <div className="rounded-3xl bg-[#121826] px-8 py-10 text-center text-white">
        <h2 className="mb-4 text-3xl font-semibold">{content.ctaTitle}</h2>
        <p className="mx-auto mb-8 max-w-2xl text-base leading-8 text-white/80">
          {content.ctaText}
        </p>
        <a
          href="/all-products"
          className="inline-flex rounded-xl bg-[#B12C2E] px-8 py-3 font-semibold text-white transition-colors hover:bg-[#972426]"
        >
          {content.ctaButton}
        </a>
      </div>
    </div>
  );
}

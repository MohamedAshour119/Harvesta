import { useTranslation } from "react-i18next";
import { FaHandshake, FaIndustry, FaLeaf } from "react-icons/fa";

const aboutContent = {
  en: {
    title: "About Harvesta",
    subtitle:
      "Harvesta is a trusted leader operating across integrated agri-commodity trading, premium food production, and supply chain services for local and regional markets.",
    storyTitle: "Who We Are",
    storyParagraphs: [
      "Harvesta has grown into a vertically integrated group built to serve the agri-food and commodity sector with strong operational capabilities, disciplined execution, and long-term market knowledge.",
      "The group brings together complementary capabilities across sourcing, manufacturing, logistics, and distribution to create a connected value chain that delivers essential food products efficiently and reliably.",
    ],
    stats: [
      { value: "Premium Quality", label: "Certified standards & purity" },
      { value: "Integrated", label: "End-to-end supply chain" },
      { value: "Local & Regional", label: "Markets served through the value chain" },
    ],
    valuesTitle: "What Drives Harvesta",
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
    ctaTitle: "Explore Harvesta Products",
    ctaText:
      "Browse our catalog to discover the product range supported by Harvesta's integrated supply and distribution network.",
    ctaButton: "View Products",
  },
  ar: {
    title: "عن هارفيستا",
    subtitle:
      "هارفيستا هي منظومة موثوقة رائدة تعمل في تجارة وتصنيع السلع الزراعية والمنتجات الغذائية وخدمات سلاسل الإمداد للأسواق المحلية والإقليمية.",
    storyTitle: "من نحن",
    storyParagraphs: [
      "نمت هارفيستا لتصبح منظومة متكاملة تخدم قطاع الأغذية والسلع الزراعية من خلال خبرة تشغيلية قوية وتنفيذ منضبط وفهم عميق لاحتياجات السوق والمستهلك.",
      "نجمع بين أحدث تقنيات التوريد والتصنيع واللوجستيات والتوزيع لتكوين سلسلة إمداد مترابطة توفر المنتجات الغذائية الأساسية بأعلى معايير الجودة والموثوقية.",
    ],
    stats: [
      { value: "جودة معتمدة", label: "معايير قياسية في كل المنتجات" },
      { value: "منظومة متكاملة", label: "من المزرعة وحتى باب العميل" },
      { value: "محليًا وإقليميًا", label: "أسواق نخدمها عبر سلسلة القيمة" },
    ],
    valuesTitle: "ما الذي يقود هارفيستا",
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
    ctaTitle: "استكشف منتجات هارفيستا",
    ctaText:
      "تصفح الكتالوج لاكتشاف مجموعة المنتجات المدعومة بمنظومة هارفيستا المتكاملة للتوريد والتوزيع.",
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

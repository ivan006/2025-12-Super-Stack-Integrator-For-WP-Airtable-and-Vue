// utils/seo.js

// utils/seo.js

export function buildSeoConfig({
  title,
  description,
  url, // canonical URL (absolute recommended)
  image, // absolute URL to 1200x630 jpg/png
  siteName,
  type = "WebPage", // schema type: WebPage | Organization | Article | OfferCatalog
  imageWidth = "1200",
  imageHeight = "630",
  schema = {},
}) {
  return {
    title: title ? `${title} | ${siteName}` : siteName,
    link: [{ rel: "canonical", href: url }],
    meta: [
      { name: "description", content: description || "" },

      // Open Graph
      { property: "og:type", content: "website" },
      { property: "og:title", content: title || "" },
      { property: "og:description", content: description || "" },
      { property: "og:url", content: url || "" },
      { property: "og:image", content: image || "" },
      { property: "og:image:secure_url", content: image || "" },
      { property: "og:image:width", content: String(imageWidth) },
      { property: "og:image:height", content: String(imageHeight) },

      // Twitter (always summary_large_image)
      { name: "twitter:card", content: "summary_large_image" },
      { name: "twitter:title", content: title || "" },
      { name: "twitter:description", content: description || "" },
      { name: "twitter:image", content: image || "" },
    ],
    script: {
      structuredData: {
        type: "application/ld+json",
        innerHTML: JSON.stringify(schema),
      },
    },
  };
}

export function buildSchemaItem({
  type,
  name,
  description,
  url,
  image,
  price,
  extras = {},
}) {
  const item = {
    "@context": "https://schema.org",
    "@type": type,
    name,
    description,
    ...(url && { url }),
    image,
    ...extras,
  };

  // Auto-add Offer if Product with price
  if (type === "Product" && price) {
    item.offers = {
      "@type": "Offer",
      price: String(Number(price).toFixed(2)),
      priceCurrency: "ZAR",
      availability: "https://schema.org/InStock",
    };
  }

  return item;
}

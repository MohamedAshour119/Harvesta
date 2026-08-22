# Shahd Products Import Specification

## Objective

Import the products from [shahd.shop](https://shahd.shop) into this Laravel system using a seeder, while preserving the product structure required by the local application so that imported products appear correctly in the storefront and product pages.

## Main Goal

The system should import Shahd products into the local database and:

- assign each product to the most suitable existing category
- attach product images
- create at least one variant for every product
- ensure imported products are visible in the storefront

## Existing Local Constraints

The current system does not display a product unless it has at least one related variant.

From the local codebase:

- products shown in shop pages are filtered with `status = approved`
- products shown in shop pages must satisfy `has('variants')`
- product cards and product details depend on:
  - product main image
  - optional gallery images
  - at least one variant with pricing

This means importing products only into the `products` table is not enough. Each imported product must also include one or more records in `product_variants`.

## Product Import Requirements

### 1. Source of Truth

The import should use Shahd Shop as the source of truth for:

- product title
- product description
- product handle or unique identifier
- product images
- product variants
- prices
- availability
- collection/category membership

### 2. Seeder-Based Import

The import must be done through a Laravel seeder, not manual admin entry.

The seeder should:

- fetch the product catalog from Shahd Shop
- create or update products in the local system
- create or update related variants
- attach remote product and variant images
- map products to the correct local categories

### 3. Category Mapping

The local system already has these categories:

- Fresh
- Frozen
- Oil
- Fried
- Frozen vegetables
- Lanalou x shahd
- Tea

Imported Shahd products should be matched to the most suitable category using Shahd collections first, and product naming or tags only as fallback.

Expected category mapping:

- Shahd `Fresh - فريش` -> local `Fresh`
- Shahd `Frozen - مجمد` -> local `Frozen`
- Shahd `Oil - زيت` -> local `Oil`
- Shahd `Fried - مقلي` -> local `Fried`
- Shahd `Frozen vegetables - خضار مجمد` -> local `Frozen vegetables`
- Shahd `Lanalou x shahd` -> local `Lanalou x shahd`
- Shahd `Tea - شاى` -> local `Tea`

If Shahd contains products in categories that do not exist locally, they should be mapped to the closest valid existing category instead of failing the import.

Example:

- `Meat Range - لحوم مجمدة` can be mapped to `Frozen` if no dedicated meat category exists locally.

## Product Data Requirements

Each imported product should include:

- localized product name when possible
- localized product description when possible
- product slug or unique imported identifier
- related local category ID
- approved status
- stock status
- minimum order quantity if required by local schema
- minimum order value if required by local schema

### Product Visibility Requirements

To make imported products visible in the local storefront:

- `status` must be `approved`
- the product must have at least one variant
- images should be attached to the correct media collections

## Variant Requirements

Each imported product must have at least one variant.

### Minimum Variant Data

Each variant should include:

- `product_id`
- `variant_type_id`
- `variant_value`
- `unit_price_usd`
- `price_after_discount`
- `discount_percentage`
- `availability`
- `weight` if available
- `volume` if needed by local schema

### Variant Display Requirement

The frontend uses the cheapest variant when building product cards and product details. Because of this:

- every imported product must have at least one valid priced variant
- if Shahd has multiple variants, they should all be imported when possible
- if Shahd has only one default option, a default variant must still be created

### Variant Type Mapping

Shahd product options may use labels such as:

- `Size`
- `Style`
- `Title`

These should be mapped to local `variants_type` records so imported variants remain consistent with the local variant system.

## Image Requirements

Imported products should include media attachments in the same collections used by the local app.

### Product Images

- first product image -> `main_images`
- remaining product images -> `products_other_images`

### Variant Images

- variant-specific image -> `variants_images`
- if a variant has no dedicated image, use the product image as fallback

## Import Behavior

The import should be idempotent.

That means re-running the seeder should:

- update existing imported products
- update existing variants
- refresh images if needed
- avoid creating uncontrolled duplicates

Recommended unique keys:

- product uniqueness by Shahd handle or imported slug
- variant uniqueness by product + option type + option value

## Data Quality Expectations

The import should preserve Shahd content as much as possible while fitting the local schema.

This includes:

- keeping English and Arabic naming if both exist in the source text
- cleaning HTML descriptions before saving
- calculating discount fields from sale and compare-at price when available
- setting stock state based on source availability

## Acceptance Criteria

The products import is considered complete when:

- Shahd products are inserted through a seeder
- every imported product belongs to a suitable local category
- every imported product has at least one variant
- imported products appear in the storefront
- product cards show price and image
- product detail pages load variant data correctly
- the import can be safely re-run without duplicate chaos

## Non-Goals

This requirement does not necessarily include:

- importing Shahd orders
- importing Shahd customers
- syncing inventory in real time
- building a scheduled sync job

The focus is the **products part only**.

## Summary

What is wanted for the products part is:

1. Pull Shahd products from the website.
2. Import them into the local Laravel system through a seeder.
3. Relate each product to the correct existing local category.
4. Import product and variant images.
5. Create at least one variant per product so the product is actually displayed.
6. Store imported products in a way that is safe to re-run and maintain.

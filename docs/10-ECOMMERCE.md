# MODULE: E-Commerce

## Overview
The E-Commerce module provides a fully integrated online storefront powered by the same product catalog, inventory, and pricing engine as the rest of the ERP. Orders placed online flow directly into the ERP as sales orders — no manual import needed.

---

## 1. Store Configuration

### 1.1 Store Setup
- Store name, tagline, logo, favicon
- Custom domain (store.yourcompany.com or yourcompany.com)
- Default language and multiple language support
- Base currency and multi-currency display
- Timezone for order timestamps
- Store-wide tax settings (prices shown inclusive or exclusive of tax)
- Maintenance mode toggle (show "coming soon" page)

### 1.2 Store Theming
- Pre-built themes (3–5 included)
- Color palette customization (primary, secondary, accent, background)
- Typography: heading and body font selection
- Homepage layout: hero banner, featured categories, featured products, promotions, testimonials
- CSS/HTML overrides for advanced customization (Power users)
- Mobile-responsive out of the box

### 1.3 Pages
- Static page builder: About Us, Contact, Shipping Policy, Returns Policy, Privacy Policy, Terms & Conditions
- Rich text editor with image embedding
- SEO fields: title, meta description, OG image per page
- Custom URL slugs

---

## 2. Product Catalog (Online Store)

### 2.1 Product Publishing
- Products from ERP catalog selectively published to online store
- Online-only products and in-store-only products supported
- Publish/unpublish without deleting product from ERP

### 2.2 Online Product Page
- Product name, description (rich text with formatting, bullet points)
- Multiple product images with zoom and gallery
- Variant selector (size, color displayed as swatches or dropdowns)
- Real-time availability indicator (in stock, low stock warning, out of stock)
- Stock quantity shown (optional, configurable)
- Related products and "frequently bought together"
- Product reviews and star ratings

### 2.3 Product SEO
- Editable SEO title and meta description per product
- Canonical URL management
- Auto-generated structured data (schema.org/Product) for Google rich results
- Sitemap auto-updated on product publish/unpublish

### 2.4 Categories & Navigation
- Product categories displayed in navigation menu
- Mega menu or simple dropdown (configurable)
- Category landing pages with banner image and description
- Breadcrumb navigation

### 2.5 Search & Filtering
- Full-text product search with typo tolerance (powered by Elasticsearch)
- Filter sidebar: by category, price range, brand, attributes (size, color), rating, availability
- Sort: relevance, price low–high, price high–low, newest, best selling
- Search results show product images and prices
- "No results" page with suggestions

---

## 3. Shopping Cart & Checkout

### 3.1 Shopping Cart
- Add to cart from product page or category listing
- Cart sidebar/drawer (no page reload)
- Update quantities and remove items in cart
- Save cart for logged-in customers (persists across sessions)
- Guest cart: stored in browser session, merged with account on login

### 3.2 Discount Codes at Checkout
- Coupon code field in cart and checkout
- Validate code on entry: show discount amount before applying
- Display "Code applied: save $12.50"
- One coupon code at a time (configurable)

### 3.3 Checkout Flow (Optimized, 3-Step Maximum)
- Step 1: Contact info (email, phone) + Shipping address
- Step 2: Shipping method selection (with rates and estimated delivery dates)
- Step 3: Payment + Order review
- Guest checkout (no account required)
- Returning customer: pre-fill address and payment details
- Address validation (reduce failed deliveries)

### 3.4 Shipping Options at Checkout
- Flat rate, free shipping (over threshold), weight-based, carrier-calculated
- Live carrier rates (DHL, FedEx API) based on destination and package weight
- Click & collect (in-store pickup) option
- Expected delivery date shown per shipping option

### 3.5 Payment at Checkout
- Credit/debit card (Stripe)
- Mobile money (M-Pesa, Airtel Money — region-specific)
- Buy Now Pay Later (Klarna, Afterpay integration — where available)
- Bank transfer (show account details, order confirmed on payment verification)
- Wallet balance / store credit
- Saved payment methods for returning customers (Stripe tokens — no card data stored)

### 3.6 Order Confirmation
- Order confirmation page with order number and summary
- Confirmation email sent automatically (with order details, shipping info, contact)
- SMS confirmation (optional)

---

## 4. Customer Accounts

### 4.1 Account Registration & Login
- Registration: email + password or social login (Google, Facebook)
- Email verification on signup
- Password reset flow
- Social login via OAuth (optional)

### 4.2 Customer Dashboard
- Order history (all past orders with status and tracking)
- Track current orders
- Saved addresses
- Saved payment methods
- Wishlist
- Loyalty points balance and transaction history
- Account settings (name, email, password, preferences)

### 4.3 Guest Order Tracking
- Track order by order number + email (no account required)
- Converts to account seamlessly if customer registers later

---

## 5. Order Management (Online Orders)

### 5.1 Order Flow
- Online order → Auto-created as Sales Order in ERP
- Payment confirmed → Order moves to "Processing" status
- Warehouse picks and packs → Order moves to "Shipped"
- Carrier delivers → Order moves to "Delivered"
- Customer confirms receipt or auto-complete after N days

### 5.2 Customer Order Updates
- Automated email at each status change
- Shipping confirmation email with tracking number and carrier link
- Delivery confirmation email

### 5.3 Order Modifications (Pre-Fulfillment)
- Customer can cancel order (within configurable window)
- Customer can change shipping address (before dispatch)
- Customer cannot change items (contact customer service)

### 5.4 Returns Portal (Self-Service)
- Customer initiates return from account order history
- Select items to return, select reason
- System generates return label (if return shipping is covered)
- Return status tracking in customer account

---

## 6. Inventory Sync

### 6.1 Real-Time Stock Display
- Stock levels sync from ERP Inventory to online store in real-time (or near-real-time, max 1-minute delay)
- "Out of stock" shown automatically when stock = 0
- "Only 3 left" shown when stock < configurable threshold

### 6.2 Overselling Prevention
- Stock reserved at checkout initiation (15-minute hold)
- If payment not completed in 15 minutes, stock released
- Stock deducted permanently on payment confirmation
- Prevents two customers buying the last item simultaneously

### 6.3 Pre-Orders & Back-Orders
- Accept pre-orders for out-of-stock items (with expected restock date displayed)
- Notify customer when back in stock
- Back-in-stock email alert (customer signs up for notification)

---

## 7. Promotions & Marketing

### 7.1 Store Promotions
- Banner announcements (header bar or hero banner)
- Sale badge on product cards (shows original and discounted price)
- Flash sale countdown timer
- Bundle deals: "Buy 2 Get 1 Free" shown on product page

### 7.2 Abandoned Cart Recovery
- Detect carts abandoned for > 1 hour
- Email sequence: 1 hour, 24 hours, 72 hours after abandonment
- Include cart contents with images in email
- Discount offer in final email (optional, configurable)
- Track recovery rate per email in sequence

### 7.3 Product Reviews
- Verified purchase reviews only (email triggered after delivery)
- Star rating (1–5) + written review
- Photo upload with review
- Reviews moderated before publishing (approve/reject)
- Reply to review from admin
- Average rating displayed on product card and product page

### 7.4 Wishlist
- Customer saves products to wishlist (requires account)
- Share wishlist link with others
- "Add to cart" from wishlist
- Wishlist reminder email if items drop in price or go on sale

---

## 8. Multi-Channel (Channel Management)

### 8.1 Shopify / WooCommerce Sync
- Connect existing Shopify or WooCommerce store
- ERP becomes source of truth for products, prices, stock
- Orders from Shopify/WooCommerce pulled into ERP as sales orders
- Stock deductions in ERP push back to Shopify/WooCommerce

### 8.2 Amazon / Marketplaces
- Amazon Seller Central integration (via Selling Partner API)
- Sync product listings, prices, and stock to Amazon
- Amazon orders imported as ERP sales orders
- FBA vs FBM fulfillment routes supported

### 8.3 Channel Attribution
- Each order tagged with its source channel
- Revenue and margin report by channel

---

## 9. Analytics & Reporting

### 9.1 Store Dashboard
- Real-time: active visitors, orders today, revenue today
- Conversion funnel: visitors → product views → add to cart → checkout started → orders
- Revenue by period (today, yesterday, this week, this month)

### 9.2 Product Performance
- Best-selling products by quantity and revenue
- Products with highest cart abandonment
- Products with lowest conversion rate (many views, few adds to cart)
- Search terms that returned no results (add these products!)

### 9.3 Customer Analytics
- New vs returning customer ratio
- Geographic breakdown of customers
- Average order value
- Customer acquisition channel

### 9.4 SEO & Traffic
- Page views per product/category
- Traffic source (organic, direct, referral, paid)
- Bounce rate per page
- Google Search Console integration (keyword rankings)

---

## 10. Integrations

- **Inventory:** Real-time stock sync; stock deducted on order payment
- **Sales:** Online orders created as ERP Sales Orders automatically
- **Accounting:** Revenue and tax posted to GL per order
- **CRM:** Customer record created/updated on account registration and order
- **Shipping Carriers:** DHL, FedEx, Aramex for label generation and tracking
- **Payment:** Stripe, PayPal, mobile money gateways
- **Email (SendGrid):** Transactional emails (confirmation, shipping, review request)
- **Analytics (Google Analytics 4):** Ecommerce event tracking (view item, add to cart, purchase)
- **Marketplaces:** Shopify, WooCommerce, Amazon channel sync

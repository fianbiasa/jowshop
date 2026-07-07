export type AiProvider = 'openai' | 'anthropic' | 'gemini';

export interface AiProviderSetting {
    id: number;
    created_by: number;
    provider: AiProvider;
    label: string;
    default_model: string;
    is_default: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export type PaymentEnvironment = 'sandbox' | 'production';

export interface PaymentSettingSummary {
    environment: PaymentEnvironment;
    is_active: boolean;
    is_configured: true;
}

export interface MetaCapiSettingSummary {
    pixel_id: string;
    test_event_code: string | null;
    is_active: boolean;
    is_configured: true;
}

export type ShippingProvider = 'komerce' | 'rajaongkir' | 'biteship';

export interface ShippingSettingSummary {
    provider: ShippingProvider;
    origin_area_id: string;
    origin_label: string | null;
    origin_contact_name: string | null;
    origin_contact_phone: string | null;
    origin_address: string | null;
    origin_postal_code: string | null;
    enabled_couriers: string;
    is_active: boolean;
    auto_book_shipping: boolean;
    is_configured: true;
}

export type ProductType = 'digital' | 'physical';

export type ProductStatus = 'draft' | 'published' | 'archived';

export interface ProductDigitalAsset {
    id: number;
    product_id: number;
    file_path: string | null;
    external_url: string | null;
    license_type: string;
    max_downloads: number | null;
}

export interface Product {
    id: number;
    created_by: number;
    name: string;
    slug: string;
    type: ProductType;
    description: string | null;
    price: string;
    thumbnail_path: string | null;
    sku: string | null;
    status: ProductStatus;
    weight_grams: number | null;
    length_cm: string | null;
    width_cm: string | null;
    height_cm: string | null;
    stock: number | null;
    digital_assets?: ProductDigitalAsset[];
    created_at: string;
    updated_at: string;
}

export type FunnelStatus = 'draft' | 'published' | 'archived';

export interface Funnel {
    id: number;
    created_by: number;
    product_id: number;
    name: string;
    slug: string;
    status: FunnelStatus;
    thank_you_message: string | null;
    fb_pixel_id: string | null;
    tiktok_pixel_id: string | null;
    ga4_id: string | null;
    google_ads_id: string | null;
    product?: Product;
    created_at: string;
    updated_at: string;
}

export type OfferStage = 'bump' | 'upsell' | 'downsell';

export type OfferTriggerCondition = 'initial' | 'accepted' | 'declined';

export type DiscountType = 'none' | 'percentage' | 'fixed';

export interface FunnelOffer {
    id: number;
    funnel_id: number;
    product_id: number;
    parent_offer_id: number | null;
    trigger_condition: OfferTriggerCondition;
    stage: OfferStage;
    sequence: number;
    headline: string;
    description: string | null;
    media_url: string | null;
    price_override: string | null;
    discount_type: DiscountType;
    discount_value: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export type SalespageStyle = 'minimal' | 'bold' | 'editorial' | 'ledger';

export interface Salespage {
    id: number;
    funnel_id: number;
    title: string;
    content: Array<{ type: string; data: Record<string, unknown> }>;
    style: SalespageStyle;
    seo_title: string | null;
    seo_description: string | null;
    og_image_path: string | null;
    generated_by_ai: boolean;
    published_at: string | null;
}

export interface Customer {
    id: number;
    name: string;
    email: string;
    phone: string | null;
}

export interface Address {
    id: number;
    customer_id: number;
    recipient_name: string;
    phone: string;
    province: string;
    city: string;
    district: string;
    postal_code: string;
    destination_area_id: string | null;
    destination_label: string | null;
    address_line: string;
    notes: string | null;
}

export type OrderStatus =
    | 'pending'
    | 'paid'
    | 'processing'
    | 'shipped'
    | 'completed'
    | 'cancelled'
    | 'expired';

export type OrderItemType = 'main' | 'bump' | 'upsell' | 'downsell';

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number;
    funnel_offer_id: number | null;
    offer_type: OrderItemType;
    quantity: number;
    unit_price: string;
    product?: Product;
}

export type PaymentStatus = 'pending' | 'paid' | 'expired' | 'failed';

export interface Payment {
    id: number;
    order_id: number;
    gateway: string;
    merchant_order_id: string;
    gateway_reference: string | null;
    payment_method: string | null;
    amount: string;
    status: PaymentStatus;
    paid_at: string | null;
}

export type ShipmentStatus =
    'pending' | 'processing' | 'shipped' | 'delivered' | 'failed';

export interface Shipment {
    id: number;
    order_id: number;
    courier: string;
    service: string;
    cost: string;
    tracking_number: string | null;
    status: ShipmentStatus;
    shipped_at: string | null;
    delivered_at: string | null;
}

export interface Order {
    id: number;
    funnel_id: number;
    customer_id: number;
    address_id: number | null;
    visitor_id: number | null;
    order_number: string;
    subtotal: string;
    discount_total: string;
    shipping_cost: string;
    total: string;
    status: OrderStatus;
    created_at: string;
    updated_at: string;
    customer?: Customer;
    address?: Address | null;
    funnel?: Funnel;
    items?: OrderItem[];
    payments?: Payment[];
    shipment?: Shipment | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

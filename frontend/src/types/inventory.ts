/** Inventory types aligned with Laravel API (snake_case, UUID ids, prices in cents). */

export type ProductType = 'stockable' | 'consumable' | 'service'
export type ProductStatus = 'active' | 'inactive' | 'archived'

export interface ProductCategory {
  id: string
  name: string
  slug?: string
  description?: string | null
  is_active?: boolean
  parent_id?: string | null
  children?: ProductCategory[]
}

export interface ProductBarcode {
  id?: string
  barcode: string
  type: string
  is_primary?: boolean
}

export interface ProductVariant {
  id: string
  sku: string
  name: string
  cost_price: number
  selling_price: number
  attribute_value_ids?: string[]
  is_active?: boolean
  stock?: number
}

export interface Product {
  id: string
  sku: string
  name: string
  description?: string | null
  type: ProductType
  status: ProductStatus
  cost_price: number
  selling_price: number
  has_variants: boolean
  track_serial_numbers?: boolean
  track_lots?: boolean
  primary_image_url?: string | null
  category?: ProductCategory | null
  variants?: ProductVariant[]
  barcodes?: ProductBarcode[]
  quantity_on_hand: number
  quantity_committed: number
  quantity_on_order: number
  available_quantity: number
  created_at?: string
  updated_at?: string
}

export interface StockLevelRow {
  location_id: string
  variant_id?: string | null
  location_name?: string | null
  location_code?: string | null
  quantity_on_hand: number
  quantity_committed: number
  quantity_on_order: number
  available_quantity: number
}

export interface StockMovement {
  id: string
  product_id: string
  product_name?: string | null
  product_sku?: string | null
  variant_id?: string | null
  type: string
  direction: 'in' | 'out' | 'transfer'
  quantity: number
  from_location_id?: string | null
  from_location_name?: string | null
  to_location_id?: string | null
  to_location_name?: string | null
  reference_type?: string | null
  reference_id?: string | null
  unit_cost: number
  notes?: string | null
  user_id?: string | null
  user_name?: string | null
  created_at?: string | null
}

export interface LowStockItem {
  product_id: string
  product_name: string
  variant_id?: string | null
  variant_name?: string | null
  sku: string
  warehouse_id?: string | null
  warehouse_name?: string | null
  location_id?: string | null
  location_name?: string
  min_quantity: number
  max_quantity?: number | null
  reorder_quantity?: number | null
  available_quantity: number
}

export interface StockLocation {
  id: string
  name: string
  code?: string
  type?: string
  is_active?: boolean
}

export interface ProductFilters {
  search?: string
  category_id?: string
  status?: ProductStatus | ''
  type?: ProductType | ''
}

export interface StockFilters {
  search?: string
  location_id?: string
  low_stock?: boolean
  category_id?: string
}

export interface MovementFilters {
  search?: string
  product_id?: string
  location_id?: string
  type?: string
  start_date?: string
  end_date?: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number | null
  to?: number | null
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
  links?: Record<string, string | null>
}

export interface CreateProductPayload {
  name: string
  sku?: string
  description?: string
  type: ProductType
  status?: ProductStatus
  category_id?: string | null
  cost_price: number
  selling_price: number
  has_variants?: boolean
  initial_stock?: number
  location_id?: string
  variants?: Array<{
    id?: string
    sku: string
    name: string
    cost_price: number
    selling_price: number
    is_active?: boolean
    stock?: number
    attribute_value_ids?: string[]
  }>
  barcodes?: Array<{
    barcode: string
    type: string
    is_primary?: boolean
  }>
}

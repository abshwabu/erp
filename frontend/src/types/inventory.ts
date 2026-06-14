export type ProductType = 'stockable' | 'consumable' | 'service'
export type ProductStatus = 'active' | 'inactive' | 'archived'

export interface ProductCategory {
  id: number
  name: string
  parentId?: number
  children?: ProductCategory[]
}

export interface ProductBarcode {
  type: 'EAN-13' | 'UPC-A' | 'Code 128' | 'QR'
  value: string
}

export interface ProductImage {
  id: string
  url: string
  isPrimary: boolean
  alt?: string
}

export interface ProductVariant {
  id: number
  sku: string
  barcode?: string
  attributes: Record<string, string> // e.g., { color: 'Red', size: 'XL' }
  price?: number // Override
  cost?: number // Override
  stock?: number
}

export interface Product {
  id: number
  name: string
  description?: string
  sku: string
  type: ProductType
  status: ProductStatus
  categoryId: number
  category?: ProductCategory
  costPrice: number
  sellingPrice: number
  minSellingPrice?: number
  maxSellingPrice?: number
  images: ProductImage[]
  barcodes: ProductBarcode[]
  hasVariants: boolean
  variants?: ProductVariant[]
  tags: string[]
  internalNotes?: string
  createdAt: string
  updatedAt: string
}

export interface StockLevel {
  id: number
  productId: number
  locationId: number
  locationName: string
  onHand: number
  committed: number
  available: number
  onOrder: number
  forecasted: number
}

export type MovementType = 
  | 'goods_received' 
  | 'sale' 
  | 'customer_return' 
  | 'supplier_return' 
  | 'internal_transfer' 
  | 'production_output' 
  | 'production_consumption' 
  | 'stock_adjustment' 
  | 'opening_balance'

export interface StockMovement {
  id: number
  productId: number
  productName: string
  variantId?: number
  type: MovementType
  direction: 'in' | 'out'
  quantity: number
  locationId: number
  locationName: string
  reference?: string // PO number, SO number, etc.
  unitCost: number
  userId: number
  userName: string
  createdAt: string
}

export interface StockAdjustment {
  productId: number
  locationId: number
  quantity: number
  type: 'add' | 'remove'
  reason: string
  notes?: string
}

export interface StockSummary {
  productId: number
  productName: string
  sku: string
  totalOnHand: number
  totalAvailable: number
  totalCommitted: number
  lowStock: boolean
  shortfall?: number
  value: number
}

export interface ProductFilters {
  search?: string
  categoryId?: number
  status?: ProductStatus
  type?: ProductType
}

export interface InventoryFilters {
  productId?: number
  locationId?: number
  type?: MovementType
  startDate?: string
  endDate?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
    hasNextPage: boolean
    hasPrevPage: boolean
  }
}

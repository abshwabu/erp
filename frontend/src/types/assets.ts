export interface AssetDepreciation {
  id: string
  asset_id: string
  fiscal_year: number
  depreciation_amount_cents: number
  accumulated_depreciation_cents: number
  book_value_cents: number
}

export type AssetCategory = 'equipment' | 'machinery' | 'vehicle' | 'electronics' | 'furniture' | 'building'
export type AssetStatus = 'active' | 'maintenance' | 'disposed' | 'retired'
export type DepreciationMethod = 'straight_line' | 'declining_balance'

export interface Asset {
  id: string
  asset_tag: string
  name: string
  category: AssetCategory
  serial_number: string | null
  purchase_date: string | null
  purchase_cost_cents: number
  salvage_value_cents: number
  useful_life_years: number
  depreciation_method: DepreciationMethod
  status: AssetStatus
  assigned_to: string | null
  assignee?: { id: string; name: string; email: string }
  notes?: string | null
  depreciations?: AssetDepreciation[]
  created_at?: string
  updated_at?: string
}

export interface AssetFormData {
  name: string
  category: AssetCategory
  serial_number: string
  purchase_date: string
  purchase_cost: number | null
  salvage_value: number | null
  useful_life_years: number | null
  depreciation_method: DepreciationMethod
  status: AssetStatus
  assigned_to: string | null
  notes: string
}

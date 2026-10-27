import { format } from 'date-fns'

export function formatCurrency(value: number, currency = 'USD', locale = 'en-US') {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(value)
}

export function formatDate(date: string | number | Date, pattern = 'PPP') {
  return format(new Date(date), pattern)
}

export function debounce<T extends (...args: any[]) => any>(
  fn: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout>
  return (...args: Parameters<T>) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}

/**
 * Resolves local, relative, proxy, and absolute image URLs safely across Docker, local, and production.
 */
export function resolveImageUrl(pathOrUrl?: string | null): string | null {
  if (!pathOrUrl) return null
  const trimmed = String(pathOrUrl).trim()
  if (!trimmed) return null

  // Data URIs or Blob URLs
  if (trimmed.startsWith('data:image') || trimmed.startsWith('blob:')) {
    return trimmed
  }

  // Normalize localhost / 127.0.0.1 / backend docker host storage URLs to relative /storage/ path
  // (e.g. http://localhost:8000/storage/products/xxx.jpg or http://backend/storage/products/xxx.jpg -> /storage/products/xxx.jpg)
  const localStorageMatch = trimmed.match(/^https?:\/\/(?:localhost|127\.0\.0\.1|backend)(?::\d+)?(\/storage\/.*)$/i)
  if (localStorageMatch) {
    return localStorageMatch[1]
  }

  // If already relative /storage/... path
  if (trimmed.startsWith('/storage/')) {
    return trimmed
  }

  // If starts with storage/... (without leading slash)
  if (trimmed.startsWith('storage/')) {
    return `/${trimmed}`
  }

  // External full remote URL (CDN/S3/external)
  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    return trimmed
  }

  // Relative storage path (e.g. 'products/xxx.jpg' or '/products/xxx.jpg')
  const cleanPath = trimmed.startsWith('/') ? trimmed : `/${trimmed}`
  return `/storage${cleanPath}`
}

/** "199080.00" + "BDT" -> "৳199,080.00" (amount kept as a string until display). */
export function formatMoney(amount: string, currency: string): string {
  const value = Number(amount)
  if (!Number.isFinite(value)) return `${amount} ${currency}`
  try {
    return new Intl.NumberFormat('en-BD', { style: 'currency', currency }).format(value)
  } catch {
    return `${amount} ${currency}`
  }
}

/** ISO 8601 UTC -> local date, e.g. "17 Nov 2026". */
export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium' }).format(new Date(iso))
}

export function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-US').format(value)
}

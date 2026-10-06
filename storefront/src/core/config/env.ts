const fallbackApiUrl = 'http://localhost:8000/api/v1'
const fallbackSiteUrl = 'http://localhost:3000'

function trimTrailingSlash(value: string) {
  return value.replace(/\/$/, '')
}

export const env = {
  apiUrl: trimTrailingSlash(process.env.NEXT_PUBLIC_API_URL || fallbackApiUrl),
  siteUrl: trimTrailingSlash(process.env.NEXT_PUBLIC_SITE_URL || fallbackSiteUrl),
} as const

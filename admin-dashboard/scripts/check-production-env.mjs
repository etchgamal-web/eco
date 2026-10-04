const apiUrl = process.env.VITE_API_URL ?? ''
const errors = []

if (!apiUrl) errors.push('VITE_API_URL is required')
else {
  try {
    const parsed = new URL(apiUrl)
    if (parsed.protocol !== 'https:') errors.push('VITE_API_URL must use HTTPS')
    if (['localhost', '127.0.0.1'].includes(parsed.hostname)) errors.push('VITE_API_URL cannot point to localhost')
  } catch {
    errors.push('VITE_API_URL must be a valid absolute URL')
  }
}

if (errors.length) {
  for (const error of errors) console.error(`FAIL ${error}`)
  process.exit(1)
}
console.log('PASS VITE_API_URL production endpoint configured')

const TOKEN_KEY = 'admin_token'
export const auth = { getToken: () => localStorage.getItem(TOKEN_KEY), setToken: (token: string) => localStorage.setItem(TOKEN_KEY, token), clear: () => localStorage.removeItem(TOKEN_KEY) }

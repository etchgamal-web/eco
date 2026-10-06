export type Customer = {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  roles?: string[]
  permissions?: string[]
  status?: string
}

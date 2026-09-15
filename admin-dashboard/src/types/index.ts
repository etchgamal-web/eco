export type Id = string | number

export interface User {
  id: Id
  name: string
  email: string
}

export interface Product {
  id: Id
  name: string
  price: number
  status: 'active' | 'draft' | 'archived'
}

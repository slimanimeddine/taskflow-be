export interface User {
  // columns
  id: string
  name: string
  email: string
  email_verified_at: string | null
  password?: string
  remember_token?: string | null
  created_at: string | null
  updated_at: string | null
  // relations
}

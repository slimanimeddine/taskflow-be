export interface Member {
  // columns
  id: string
  role: string
  user_id: string
  workspace_id: string
  created_at: string | null
  updated_at: string | null
}

export interface User {
  // columns
  id: string
  name: string
  email: string
  email_verified_at: string | null
  created_at: string | null
  updated_at: string | null
  // relations
  all_workspaces: Workspace[]
  admin_workspaces: Workspace[]
  member_workspaces: Workspace[]
  tokens: PersonalAccessToken[]
  notifications: DatabaseNotification[]
}

export interface Workspace {
  // columns
  id: string
  name: string
  image_path: string | null
  user_id: string
  invite_code: string
  created_at: string | null
  updated_at: string | null
  // relations
  creator: User
}

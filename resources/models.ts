export interface Member {
  // columns
  id: string
  role: string
  user_id: string
  workspace_id: string
  created_at: string | null
  updated_at: string | null
  // relations
  workspace: Workspace
  user: User
}

export interface Project {
  // columns
  id: string
  image_path: string | null
  name: string
  workspace_id: string
  created_at: string | null
  updated_at: string | null
  // relations
  workspace: Workspace
  tasks: Task[]
}

export interface Task {
  // columns
  id: string
  name: string
  description: string | null
  due_date: string
  status: string
  position: number
  workspace_id: string
  project_id: string
  assignee_id: string
  created_at: string | null
  updated_at: string | null
  // relations
  workspace: Workspace
  project: Project
  assignee: User
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
  users: User[]
  projects: Project[]
  tasks: Task[]
}

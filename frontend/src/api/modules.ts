import apiClient from './client'

export interface ModuleItem {
  key: string
  name: string
  category: string
  description: string
  icon: string
  dependencies: string[]
  direct_deps: string[]
  dependents: string[]
  allowed_by_plan: boolean
  is_enabled: boolean
  is_core: boolean
}

export interface ModulesResponse {
  data: {
    modules: ModuleItem[]
    enabled_modules: string[]
    plan: {
      name: string
      slug: string
      allowed_modules: string[]
    }
  }
}

export interface ToggleModuleResponse {
  message: string
  data: {
    enabled_modules: string[]
    activated: string[]
    deactivated: string[]
  }
}

export const modulesApi = {
  async getModules(): Promise<ModulesResponse> {
    const response = await apiClient.get<ModulesResponse>('/core/modules')
    return response.data
  },

  async toggleModule(module: string, enabled: boolean): Promise<ToggleModuleResponse> {
    const response = await apiClient.post<ToggleModuleResponse>('/core/modules/toggle', {
      module,
      enabled,
    })
    return response.data
  },
}

import { ref } from 'vue'

export interface Toast {
  id: string
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}

const toasts = ref<Toast[]>([])

let toastId = 0

export function useToast() {
  const addToast = (message: string, type: Toast['type'] = 'info', duration = 4000) => {
    const id = `toast-${++toastId}`
    const toast: Toast = { id, message, type, duration }
    
    toasts.value.push(toast)
    
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
    
    return id
  }

  const removeToast = (id: string) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const success = (message: string, duration?: number) => addToast(message, 'success', duration)
  const error = (message: string, duration?: number) => addToast(message, 'error', duration)
  const warning = (message: string, duration?: number) => addToast(message, 'warning', duration)
  const info = (message: string, duration?: number) => addToast(message, 'info', duration)

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info
  }
}

// Global toast instance
export const toast = {
  success: (message: string, duration?: number) => useToast().success(message, duration),
  error: (message: string, duration?: number) => useToast().error(message, duration),
  warning: (message: string, duration?: number) => useToast().warning(message, duration),
  info: (message: string, duration?: number) => useToast().info(message, duration),
}
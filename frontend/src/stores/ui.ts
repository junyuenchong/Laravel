import { defineStore } from 'pinia'

export type NotificationType = 'success' | 'error' | 'info'

export type Notification = {
  type: NotificationType
  message: string
}

export const useUiStore = defineStore('ui', {
  state: () => ({
    notification: null as Notification | null,
  }),
  actions: {
    show(type: NotificationType, message: string) {
      this.notification = { type, message }
    },
    showSuccess(message: string) {
      this.show('success', message)
    },
    showError(message: string) {
      this.show('error', message)
    },
    clear() {
      this.notification = null
    },
  },
})


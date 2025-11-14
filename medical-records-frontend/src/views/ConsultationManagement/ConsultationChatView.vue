<template>
  <section class="consultation-chat">
    <div class="chat-container">
      <!-- Left sidebar: Danh sách cuộc hội thoại -->
      <aside class="chat-sidebar">
        <div class="sidebar-header">
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-primary" @click="goHome" title="Quay lại trang chủ">
              🏠
            </button>
            <h2 class="h5 mb-0">Tin nhắn tư vấn</h2>
          </div>
          <button class="btn btn-sm btn-outline-secondary" @click="loadConsultations" :disabled="loading">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        </div>

        <div class="sidebar-filters">
          <select v-model="statusFilter" class="form-select form-select-sm" @change="loadConsultations">
            <option value="">Tất cả trạng thái</option>
            <option value="waiting">Chờ tiếp nhận</option>
            <option value="active">Đang chat</option>
            <option value="closed">Đã đóng</option>
          </select>
        </div>

        <div class="sidebar-list" v-if="!loading || consultations.length">
          <div
            v-for="c in consultations"
            :key="c._id"
            class="consultation-item"
            :class="{ active: selectedConsultation?._id === c._id, unread: hasUnread(c) }"
            @click="selectConsultation(c)"
          >
            <div class="consultation-avatar">
              <i class="bi bi-person-circle"></i>
            </div>
            <div class="consultation-info">
              <div class="consultation-name">{{ c.patient_info?.name || 'Bệnh nhân' }}</div>
              <div class="consultation-last-msg">{{ c.last_message || 'Chưa có tin nhắn' }}</div>
            </div>
            <div class="consultation-meta">
              <div class="consultation-time">{{ formatTime(c.last_message_at || c.created_at) }}</div>
              <span v-if="hasUnread(c)" class="badge bg-danger">{{ c.unread_count_staff || 1 }}</span>
              <span v-else-if="c.status === 'waiting'" class="badge bg-warning">Mới</span>
            </div>
          </div>

          <div v-if="!consultations.length" class="text-center text-muted py-4">
            Không có cuộc hội thoại nào
          </div>
        </div>

        <div v-if="loading && !consultations.length" class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
          </div>
        </div>
      </aside>

      <!-- Right panel: Chi tiết cuộc hội thoại -->
      <main class="chat-main">
        <div v-if="!selectedConsultation" class="chat-empty">
          <i class="bi bi-chat-dots"></i>
          <p>Chọn một cuộc hội thoại để bắt đầu</p>
        </div>

        <template v-else>
          <!-- Header cuộc hội thoại -->
          <div class="chat-header">
            <div class="chat-header-info">
              <div class="chat-avatar">
                <i class="bi bi-person-circle"></i>
              </div>
              <div>
                <div class="chat-title">{{ selectedConsultation.patient_info?.name || 'Bệnh nhân' }}</div>
                <div class="chat-subtitle">
                  <span v-if="selectedConsultation.patient_info?.phone">
                    {{ selectedConsultation.patient_info.phone }}
                  </span>
                  <span v-else class="text-muted">Không có SĐT</span>
                </div>
              </div>
            </div>
            <div class="chat-header-actions">
              <button
                v-if="selectedConsultation.status === 'waiting'"
                class="btn btn-sm btn-primary"
                @click="takeConsultation"
                :disabled="processing"
              >
                <i class="bi bi-check-circle"></i> Tiếp nhận
              </button>
              <button
                v-if="selectedConsultation.status === 'active'"
                class="btn btn-sm btn-outline-danger"
                @click="closeConsultation"
                :disabled="processing"
              >
                <i class="bi bi-x-circle"></i> Đóng
              </button>
              <span v-if="selectedConsultation.status === 'closed'" class="badge bg-secondary">
                Đã đóng
              </span>
            </div>
          </div>

          <!-- Messages area -->
          <div class="chat-messages" ref="messagesContainer">
            <div v-if="loadingMessages" class="text-center py-4">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Đang tải tin nhắn...</span>
              </div>
            </div>

            <div v-for="msg in messages" :key="msg._id" class="message" :class="{ 'message-staff': msg.sender_type === 'staff' }">
              <div class="message-bubble">
                <div class="message-sender">{{ msg.sender_name }}</div>
                <div class="message-text">{{ msg.message }}</div>
                <div class="message-time">{{ formatTime(msg.created_at) }}</div>
              </div>
            </div>

            <div v-if="!messages.length && !loadingMessages" class="text-center text-muted py-4">
              Chưa có tin nhắn
            </div>
          </div>

          <!-- Input area -->
          <div class="chat-input" v-if="selectedConsultation.status !== 'closed'">
            <form @submit.prevent="sendMessage">
              <div class="input-group">
                <input
                  v-model="messageText"
                  type="text"
                  class="form-control"
                  placeholder="Nhập tin nhắn..."
                  :disabled="sending || selectedConsultation.status === 'closed'"
                />
                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="!messageText.trim() || sending"
                >
                  <i class="bi bi-send"></i> Gửi
                </button>
              </div>
            </form>
          </div>
          <div v-else class="chat-input-closed">
            <p class="text-muted mb-0">Cuộc hội thoại đã đóng</p>
          </div>
        </template>
      </main>
    </div>
  </section>
</template>

<script>
import ConsultationService from '@/api/consultationService'

export default {
  name: 'ConsultationChatView',
  data () {
    return {
      loading: false,
      loadingMessages: false,
      processing: false,
      sending: false,

      statusFilter: '',
      consultations: [],
      selectedConsultation: null,
      messages: [],
      messageText: '',

      currentUser: null,
      pollingInterval: null
    }
  },
  async created () {
    this.currentUser = this.$store.state.user
    await this.loadConsultations()
    this.startPolling()
  },
  beforeUnmount () {
    this.stopPolling()
  },
  methods: {
    // ========== Load data ==========
    async loadConsultations () {
      this.loading = true
      try {
        const params = { limit: 100 }
        if (this.statusFilter) params.status = this.statusFilter

        const result = await ConsultationService.getConsultations(params)
        this.consultations = this.extractRows(result)

        // Sort: waiting first, then active, then by updated_at
        this.consultations.sort((a, b) => {
          if (a.status === 'waiting' && b.status !== 'waiting') return -1
          if (a.status !== 'waiting' && b.status === 'waiting') return 1
          if (a.status === 'active' && b.status !== 'active') return -1
          if (a.status !== 'active' && b.status === 'active') return 1
          return new Date(b.updated_at || b.created_at) - new Date(a.updated_at || a.created_at)
        })

        // Cập nhật selectedConsultation với data mới từ server
        if (this.selectedConsultation) {
          const updated = this.consultations.find(c => c._id === this.selectedConsultation._id)
          if (updated) {
            // Cập nhật data nhưng giữ nguyên reference để không trigger re-render
            Object.assign(this.selectedConsultation, updated)
          } else {
            // Nếu conversation bị xóa thì clear selection
            this.selectedConsultation = null
          }
        }
      } catch (e) {
        console.error('Load consultations failed:', e)
        alert(e?.response?.data?.message || e?.message || 'Không tải được danh sách')
      } finally {
        this.loading = false
      }
    },

    async selectConsultation (consultation) {
      this.selectedConsultation = consultation
      await this.loadMessages()
    },

    async loadMessages () {
      if (!this.selectedConsultation) return

      this.loadingMessages = true
      try {
        const result = await ConsultationService.getMessagesByConsultation(
          this.selectedConsultation._id,
          { limit: 200 }
        )
        this.messages = this.extractRows(result)

        // Sort by created_at ascending
        this.messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))

        // Lưu vị trí scroll hiện tại
        const container = this.$refs.messagesContainer
        const shouldScrollToBottom = !container || (container.scrollHeight - container.scrollTop - container.clientHeight < 100)

        // Mark unread messages as read (staff side)
        const unreadIds = this.messages
          .filter(m => !m.is_read && m.sender_type === 'patient')
          .map(m => m._id)

        if (unreadIds.length) {
          try {
            console.log('Marking as read:', unreadIds)
            await ConsultationService.markAsRead(unreadIds)
            // Update local - chỉ cập nhật is_read, không làm mất data khác
            this.messages.forEach(m => {
              if (unreadIds.includes(m._id)) {
                m.is_read = true
              }
            })
            // Reset unread count for staff
            if (this.selectedConsultation.unread_count_staff > 0) {
              this.selectedConsultation.unread_count_staff = 0
            }
          } catch (e) {
            console.error('Mark as read failed:', e)
          }
        }

        // Chỉ scroll xuống bottom nếu đang ở gần bottom (tránh làm gián đoạn khi đang đọc tin nhắn cũ)
        if (shouldScrollToBottom) {
          this.$nextTick(() => this.scrollToBottom())
        }
      } catch (e) {
        console.error('Load messages failed:', e)
        alert(e?.response?.data?.message || e?.message || 'Không tải được tin nhắn')
      } finally {
        this.loadingMessages = false
      }
    },

    // ========== Actions ==========
    async takeConsultation () {
      if (!this.selectedConsultation || !this.currentUser) return

      this.processing = true
      try {
        const staffId = this.currentUser._id || this.currentUser.id || this.currentUser.username
        const staffInfo = {
          name: this.currentUser.username || 'Nhân viên',
          email: this.currentUser.email
        }

        const result = await ConsultationService.assignStaff(this.selectedConsultation._id, {
          staff_id: staffId,
          staff_info: staffInfo
        })

        // Cập nhật từ response để đảm bảo data đồng bộ
        if (result && result.data) {
          this.selectedConsultation = result.data
        } else {
          // Fallback: update local
          this.selectedConsultation.status = 'active'
          this.selectedConsultation.staff_id = staffId
          this.selectedConsultation.staff_info = staffInfo
        }

        await this.loadConsultations()
      } catch (e) {
        console.error('Take consultation failed:', e)
        alert(e?.response?.data?.message || e?.message || 'Không thể tiếp nhận')
      } finally {
        this.processing = false
      }
    },

    async closeConsultation () {
      if (!this.selectedConsultation) return

      if (!confirm('Bạn chắc chắn muốn đóng cuộc hội thoại này?')) return

      this.processing = true
      try {
        await ConsultationService.closeConsultation(this.selectedConsultation._id)
        this.selectedConsultation.status = 'closed'
        await this.loadConsultations()

        // Thông báo thành công
        alert('Đã đóng cuộc hội thoại thành công!')
      } catch (e) {
        console.error('Close consultation failed:', e)
        alert(e?.response?.data?.message || e?.message || 'Không thể đóng')
      } finally {
        this.processing = false
      }
    },

    async sendMessage () {
      if (!this.messageText.trim() || !this.selectedConsultation || !this.currentUser) return

      this.sending = true
      try {
        const payload = {
          consultation_id: this.selectedConsultation._id,
          sender_id: this.currentUser._id || this.currentUser.id || this.currentUser.username,
          sender_type: 'staff',
          sender_name: this.currentUser.username || 'Nhân viên',
          message: this.messageText.trim()
        }

        await ConsultationService.sendMessage(payload)
        this.messageText = ''

        // Reset unread count về 0 sau khi staff gửi tin nhắn
        // Vì staff vừa nhắn nên đã đọc hết tin nhắn của patient
        if (this.selectedConsultation.unread_count_staff > 0) {
          this.selectedConsultation.unread_count_staff = 0
        }

        // Reload để cập nhật tin nhắn mới
        await this.loadMessages()
        await this.loadConsultations()
      } catch (e) {
        console.error('Send message failed:', e)
        alert(e?.response?.data?.message || e?.message || 'Không gửi được tin nhắn')
      } finally {
        this.sending = false
      }
    },

    // ========== Helpers ==========
    extractRows (res) {
      if (!res) return []
      if (Array.isArray(res)) return res
      if (Array.isArray(res.items)) return res.items
      if (Array.isArray(res.data)) return res.data
      if (res.rows && Array.isArray(res.rows)) {
        return res.rows.map(r => r.doc || r.value || r)
      }
      return []
    },

    hasUnread (consultation) {
      // Chỉ hiển thị badge khi có tin nhắn chưa đọc từ patient
      // Staff không cần thấy badge cho tin nhắn của chính mình
      return (consultation.unread_count_staff || 0) > 0
    },

    formatTime (isoString) {
      if (!isoString) return ''
      try {
        const d = new Date(isoString)
        const now = new Date()
        const diff = now - d

        // Nếu trong ngày hôm nay
        if (diff < 24 * 60 * 60 * 1000 && d.getDate() === now.getDate()) {
          return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
        }

        // Nếu trong tuần
        if (diff < 7 * 24 * 60 * 60 * 1000) {
          const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
          return days[d.getDay()]
        }

        // Ngày tháng
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
      } catch {
        return ''
      }
    },

    scrollToBottom () {
      const container = this.$refs.messagesContainer
      if (container) {
        container.scrollTop = container.scrollHeight
      }
    },

    // ========== Polling ==========
    startPolling () {
      this.pollingInterval = setInterval(() => {
        if (!document.hidden) {
          // Chỉ reload consultations, không reload messages để tránh flicker
          this.loadConsultations()
          // Chỉ reload messages khi có conversation được chọn
          if (this.selectedConsultation) {
            this.loadMessages()
          }
        }
      }, 10000) // Poll every 10 seconds (giảm tải server)
    },

    stopPolling () {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval)
        this.pollingInterval = null
      }
    },

    goHome () {
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
.consultation-chat {
  height: calc(100vh - 80px);
  padding: 0;
}

.chat-container {
  display: grid;
  grid-template-columns: 320px 1fr;
  height: 100%;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* ========== Sidebar ========== */
.chat-sidebar {
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
}

.sidebar-header {
  padding: 16px;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.sidebar-header h2 {
  color: white;
}

.sidebar-header .btn-outline-primary {
  border: 2px solid rgba(255, 255, 255, 0.5);
  color: white;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
}

.sidebar-header .btn-outline-primary:hover {
  background: white;
  color: #3b82f6;
  border-color: white;
}

.sidebar-header .btn-outline-secondary {
  border: 2px solid rgba(255, 255, 255, 0.5);
  color: white;
  background: rgba(255, 255, 255, 0.15);
}

.sidebar-header .btn-outline-secondary:hover {
  background: white;
  color: #3b82f6;
  border-color: white;
}

.sidebar-filters {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.sidebar-list {
  flex: 1;
  overflow-y: auto;
}

.consultation-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid #f3f4f6;
}

.consultation-item:hover {
  background: #f3f4f6;
}

.consultation-item.active {
  background: linear-gradient(90deg, #eff6ff 0%, #dbeafe 100%);
  border-left: 5px solid #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.consultation-item.unread {
  font-weight: 600;
}

.consultation-avatar {
  font-size: 36px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  flex-shrink: 0;
}

.consultation-item.active .consultation-avatar {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  filter: brightness(1.2);
}

.consultation-info {
  flex: 1;
  min-width: 0;
}

.consultation-name {
  font-weight: 600;
  font-size: 14px;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.consultation-last-msg {
  font-size: 13px;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

.consultation-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
  flex-shrink: 0;
}

.consultation-time {
  font-size: 11px;
  color: #9ca3af;
}

.consultation-meta .badge.bg-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.consultation-meta .badge.bg-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

/* ========== Main Chat ========== */
.chat-main {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.chat-empty i {
  font-size: 64px;
  margin-bottom: 16px;
}

.chat-header {
  padding: 16px 24px;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.chat-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.chat-avatar {
  font-size: 40px;
  color: white;
  opacity: 0.95;
}

.chat-title {
  font-weight: 600;
  font-size: 16px;
  color: white;
}

.chat-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.9);
}

.chat-header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.chat-header-actions .btn-primary {
  background: white;
  color: #3b82f6;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(255, 255, 255, 0.3);
}

.chat-header-actions .btn-primary:hover {
  background: #eff6ff;
  color: #1d4ed8;
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
}

.chat-header-actions .btn-outline-danger {
  border: 2px solid white;
  color: white;
  background: transparent;
}

.chat-header-actions .btn-outline-danger:hover {
  background: white;
  color: #dc2626;
  border-color: white;
}

.chat-header-actions .badge {
  background: rgba(255, 255, 255, 0.9) !important;
  color: #64748b !important;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  background: #f9fafb;
}

.message {
  display: flex;
  margin-bottom: 16px;
}

.message-bubble {
  max-width: 70%;
  padding: 10px 14px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  border: 1px solid #e5e7eb;
}

.message-bubble:hover {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
  transform: translateY(-1px);
  border-color: #bfdbfe;
}

.message-staff .message-bubble {
  margin-left: auto;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: #fff;
  box-shadow: 0 3px 12px rgba(59, 130, 246, 0.4);
}

.message-staff .message-bubble:hover {
  box-shadow: 0 5px 16px rgba(59, 130, 246, 0.5);
  transform: translateY(-2px);
}

.message-sender {
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 4px;
  opacity: 0.8;
}

.message-text {
  font-size: 14px;
  line-height: 1.5;
  word-wrap: break-word;
}

.message-time {
  font-size: 10px;
  margin-top: 4px;
  opacity: 0.7;
}

.chat-input {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  background: #fff;
}

.chat-input .btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border: none;
  box-shadow: 0 3px 10px rgba(59, 130, 246, 0.3);
}

.chat-input .btn-primary:hover {
  box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.chat-input .form-control:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.chat-input-closed {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-container {
    grid-template-columns: 1fr;
  }

  .chat-sidebar {
    display: none;
  }
}
</style>

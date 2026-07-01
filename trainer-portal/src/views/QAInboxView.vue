<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('qa.title') }}</h1>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="threads.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('qa.noQuestions') }}</h3>
    </div>

    <div v-else class="space-y-4">
      <div v-for="thread in threads" :key="thread.id" class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ thread.student?.name }}</h3>
            <p class="text-sm text-gray-500">
              {{ thread.chapter?.course?.title }} • {{ thread.chapter?.title_en }}
            </p>
            <p class="text-xs text-gray-400 mt-1">{{ formatDate(thread.created_at) }}</p>
          </div>
          <span :class="hasReply(thread) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" 
                class="px-2 py-1 text-xs rounded-full">
            {{ hasReply(thread) ? 'Answered' : 'Pending' }}
          </span>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('qa.question') }}</h4>
          <p class="text-gray-700 whitespace-pre-wrap">{{ thread.question }}</p>
        </div>

        <!-- Existing Reply -->
        <div v-if="thread.replies && thread.replies.length > 0" class="mb-4">
          <div v-for="reply in thread.replies" :key="reply.id" class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center mb-2">
              <span class="text-sm font-medium text-green-800">{{ reply.user?.name || 'Trainer' }}</span>
              <span class="ml-2 text-xs text-green-600">{{ formatDate(reply.created_at) }}</span>
            </div>
            <p class="text-gray-700 whitespace-pre-wrap">{{ reply.answer }}</p>
          </div>
        </div>

        <!-- Reply Form -->
        <div v-if="!hasReply(thread)" class="border-t pt-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('qa.reply') }}</h4>
          <textarea v-model="replyForms[thread.id]" rows="3" 
                    :placeholder="$t('qa.yourAnswer')"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
          <button @click="sendReply(thread.id)" :disabled="!replyForms[thread.id]?.trim() || submitting"
                  class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 text-sm">
            {{ submitting ? $t('common.loading') : $t('qa.sendReply') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const submitting = ref(false)
const threads = ref([])
const replyForms = ref({})

const hasReply = (thread) => {
  return thread.replies && thread.replies.length > 0
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const sendReply = async (threadId) => {
  if (!replyForms.value[threadId]?.trim()) return
  
  submitting.value = true
  
  try {
    const thread = threads.value.find(t => t.id === threadId)
    await axios.post(`/api/trainer/qa-threads/${threadId}/answer`, {
      answer: replyForms.value[threadId],
      answer_ar: null // Add Arabic support if needed
    })
    
    // Refresh the thread list
    await fetchThreads()
    replyForms.value[threadId] = ''
  } catch (error) {
    console.error('Failed to send reply:', error)
    alert(error.response?.data?.message || 'Failed to send reply')
  } finally {
    submitting.value = false
  }
}

const fetchThreads = async () => {
  try {
    const res = await axios.get('/api/trainer/qa-threads')
    threads.value = res.data.threads || []
  } catch (error) {
    console.error('Failed to fetch Q&A threads:', error)
  } finally {
    loading.value = false
  }
}

onMounted(fetchThreads)
</script>

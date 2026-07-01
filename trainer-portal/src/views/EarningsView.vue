<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('earnings.title') }}</h1>
    </div>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-500">{{ $t('common.loading') }}</p>
    </div>

    <div v-else class="space-y-8">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('earnings.summary.totalEarned') }}</dt>
                  <dd class="text-lg font-semibold text-gray-900">${{ earnings.summary?.total_earned || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('earnings.summary.totalPaid') }}</dt>
                  <dd class="text-lg font-semibold text-gray-900">${{ earnings.summary?.total_paid || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('earnings.summary.pending') }}</dt>
                  <dd class="text-lg font-semibold text-gray-900">${{ earnings.summary?.pending || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">{{ $t('earnings.summary.available') }}</dt>
                  <dd class="text-lg font-semibold text-green-600">${{ availableBalance }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Revenue Chart -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('earnings.chart.revenueTitle') }}</h2>
          <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
            <canvas ref="chartRef"></canvas>
          </div>
        </div>

        <!-- Payout Request Form -->
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-4">{{ $t('earnings.payoutRequest.title') }}</h2>
          <form @submit.prevent="requestPayout" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('earnings.payoutRequest.amount') }}</label>
              <input v-model.number="payoutForm.amount" type="number" step="0.01" :max="availableBalance" required
                     class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2" />
              <p class="mt-1 text-sm text-gray-500">{{ $t('earnings.payoutRequest.availableBalance', { amount: availableBalance }) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('earnings.payoutRequest.paymentMethod') }}</label>
              <select v-model="payoutForm.payment_method" required
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="paypal">PayPal</option>
                <option value="stripe">Stripe</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('earnings.payoutRequest.accountDetails') }}</label>
              <textarea v-model="payoutForm.account_details" rows="3" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"
                        placeholder="Account number, PayPal email, etc."></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('earnings.payoutRequest.notes') }}</label>
              <textarea v-model="payoutForm.notes" rows="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2"></textarea>
            </div>
            <button type="submit" :disabled="submitting"
                    class="w-full py-2 px-4 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
              {{ submitting ? $t('common.loading') : $t('earnings.payoutRequest.submitRequest') }}
            </button>
          </form>
        </div>
      </div>

      <!-- Payout History -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">{{ $t('earnings.payoutHistory.title') }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('earnings.payoutHistory.date') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('earnings.payoutHistory.amount') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('earnings.payoutHistory.method') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('earnings.payoutHistory.status') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="request in payoutRequests" :key="request.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(request.created_at) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${{ request.amount }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatMethod(request.payment_method) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusClass(request.status)" class="px-2 py-1 text-xs rounded-full">{{ request.status }}</span>
                </td>
              </tr>
              <tr v-if="payoutRequests.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">{{ $t('earnings.payoutHistory.noRequests') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const submitting = ref(false)
const earnings = ref({ summary: {}, recent_payouts: [] })
const payoutRequests = ref([])
const chartRef = ref(null)

const payoutForm = ref({
  amount: null,
  payment_method: 'bank_transfer',
  account_details: '',
  notes: ''
})

const availableBalance = computed(() => {
  return (earnings.value.summary?.pending || 0) - 
         (payoutRequests.value.filter(r => r.status === 'pending').reduce((sum, r) => sum + r.amount, 0) || 0)
})

const statusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    paid: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => new Date(dateString).toLocaleDateString()
const formatMethod = (method) => method.replace('_', ' ').toUpperCase()

const requestPayout = async () => {
  submitting.value = true
  
  try {
    await axios.post('/api/trainer/payout-requests', payoutForm.value)
    alert('Payout request submitted successfully!')
    payoutForm.value = { amount: null, payment_method: 'bank_transfer', account_details: '', notes: '' }
    fetchEarnings()
  } catch (error) {
    console.error('Failed to submit payout request:', error)
    alert(error.response?.data?.message || 'Failed to submit request')
  } finally {
    submitting.value = false
  }
}

const fetchEarnings = async () => {
  try {
    const res = await axios.get('/api/trainer/earnings')
    earnings.value = res.data
    payoutRequests.value = res.data.recent_payouts || []
  } catch (error) {
    console.error('Failed to fetch earnings:', error)
  } finally {
    loading.value = false
  }
}

// Simple chart rendering (you can enhance with Chart.js)
const renderChart = () => {
  // Placeholder for chart - integrate with Chart.js or vue-chartjs
  if (chartRef.value) {
    // Chart implementation would go here
  }
}

onMounted(() => {
  fetchEarnings()
  renderChart()
})
</script>

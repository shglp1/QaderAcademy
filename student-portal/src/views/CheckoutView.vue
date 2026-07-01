<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold mb-6">Checkout</h1>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="enrollment" class="bg-white rounded-lg shadow-sm p-6">
      <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">{{ enrollment.course?.title }}</h2>
        <p class="text-gray-600">Trainer: {{ enrollment.course?.trainer?.name }}</p>
      </div>

      <div class="border-t pt-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <span class="text-lg font-medium">Total Amount:</span>
          <span class="text-2xl font-bold text-indigo-600">${{ enrollment.course?.price }}</span>
        </div>
      </div>

      <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
        <p class="text-sm text-blue-800">
          You will be redirected to our secure payment provider (MyFatoorah) to complete your purchase. 
          After successful payment, you'll have immediate access to the course.
        </p>
      </div>

      <button 
        @click="proceedToPayment"
        :disabled="processing"
        class="w-full bg-indigo-600 text-white py-3 rounded-md font-semibold hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ processing ? $t('common.loading') : 'Proceed to Payment' }}
      </button>

      <router-link to="/courses" class="block text-center mt-4 text-gray-600 hover:text-gray-800">
        ← Back to Courses
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const enrollment = ref(null)
const loading = ref(true)
const processing = ref(false)

const fetchEnrollment = async () => {
  try {
    const response = await axios.get(`/api/student/enrollments/${route.params.enrollmentId}`)
    enrollment.value = response.data.enrollment || response.data.data || response.data
    
    // Check if already paid
    if (enrollment.value.status === 'active') {
      router.push(`/learn/${enrollment.value.id}`)
    }
  } catch (error) {
    console.error('Error fetching enrollment:', error)
    alert('Enrollment not found')
    router.push('/courses')
  } finally {
    loading.value = false
  }
}

const proceedToPayment = async () => {
  processing.value = true
  try {
    const paymentUrl = enrollment.value.payment?.checkout_url

    if (paymentUrl) {
      window.location.href = paymentUrl
    } else {
      throw new Error('No payment URL received')
    }
  } catch (error) {
    console.error('Payment initiation failed:', error)
    alert('Failed to initiate payment. Please try again.')
  } finally {
    processing.value = false
  }
}

onMounted(() => {
  fetchEnrollment()
})
</script>

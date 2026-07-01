<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold mb-6">{{ $t('profile.myProgress') }}</h1>

    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="enrollments.length > 0" class="space-y-6">
      <div v-for="enrollment in enrollments" :key="enrollment.id" class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="font-semibold text-lg">{{ enrollment.course?.title }}</h3>
            <p class="text-sm text-gray-600">{{ enrollment.course?.trainer?.name }}</p>
          </div>
          <span :class="statusClass(enrollment.status)" class="px-3 py-1 rounded-full text-sm">
            {{ statusText(enrollment.status) }}
          </span>
        </div>

        <!-- Progress Bar -->
        <div class="mb-4">
          <div class="flex justify-between text-sm mb-1">
            <span>Progress</span>
            <span>{{ enrollment.progress_percentage || 0 }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-indigo-600 h-2 rounded-full transition-all" 
              :style="{ width: (enrollment.progress_percentage || 0) + '%' }"
            ></div>
          </div>
        </div>

        <div class="flex gap-4">
          <router-link 
            v-if="enrollment.status === 'active'"
            :to="`/learn/${enrollment.id}`"
            class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700"
          >
            Continue Learning
          </router-link>
          <button 
            v-if="enrollment.certificate"
            @click="downloadCertificate(enrollment.certificate)"
            class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700"
          >
            📜 {{ $t('profile.downloadCertificate') }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12 bg-white rounded-lg shadow-sm">
      <p class="text-gray-600 mb-4">You haven't enrolled in any courses yet</p>
      <router-link to="/courses" class="text-indigo-600 hover:text-indigo-800 font-medium">
        Browse Courses →
      </router-link>
    </div>

    <!-- Certificates Section -->
    <div v-if="certificates.length > 0" class="mt-12">
      <h2 class="text-xl font-bold mb-6">{{ $t('profile.certificates') }}</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="cert in certificates" :key="cert.id" class="bg-white rounded-lg shadow-sm p-6 text-center">
          <div class="text-4xl mb-4">📜</div>
          <h3 class="font-semibold mb-2">{{ cert.course?.title }}</h3>
          <p class="text-sm text-gray-600 mb-4">Completed: {{ formatDate(cert.completed_at) }}</p>
          <button 
            @click="downloadCertificate(cert)"
            class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 w-full"
          >
            {{ $t('profile.downloadCertificate') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const enrollments = ref([])
const certificates = ref([])
const loading = ref(true)

const statusClass = (status) => {
  const classes = {
    pending_payment: 'bg-yellow-100 text-yellow-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    expired: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const statusText = (status) => {
  const texts = {
    pending_payment: 'Payment Pending',
    active: 'In Progress',
    completed: 'Completed',
    expired: 'Expired'
  }
  return texts[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString()
}

const fetchEnrollments = async () => {
  try {
    const response = await axios.get('/api/enrollments/me')
    enrollments.value = response.data.data || response.data
    
    // Extract certificates from completed enrollments
    certificates.value = enrollments.value.filter(e => e.certificate).map(e => e.certificate)
  } catch (error) {
    console.error('Error fetching enrollments:', error)
  } finally {
    loading.value = false
  }
}

const downloadCertificate = async (certificate) => {
  try {
    const response = await axios.get(`/api/certificates/${certificate.id}`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `certificate-${certificate.verification_code}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Error downloading certificate:', error)
    alert('Failed to download certificate')
  }
}

onMounted(() => {
  fetchEnrollments()
})
</script>

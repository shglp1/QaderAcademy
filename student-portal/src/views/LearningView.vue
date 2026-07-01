<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">{{ $t('common.loading') }}</p>
    </div>

    <div v-else-if="enrollment && enrollment.course" class="grid md:grid-cols-4 gap-6">
      <!-- Chapter List Sidebar -->
      <div class="md:col-span-1">
        <div class="bg-white rounded-lg shadow-sm p-4 sticky top-4">
          <h2 class="font-semibold mb-4">{{ enrollment.course.title }}</h2>
          
          <!-- Overall Progress -->
          <div class="mb-4 pb-4 border-b">
            <div class="flex justify-between text-sm mb-1">
              <span>Overall Progress</span>
              <span>{{ enrollment.progress_percentage || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-green-600 h-2 rounded-full transition-all" 
                :style="{ width: (enrollment.progress_percentage || 0) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Chapters -->
          <div class="space-y-2 max-h-96 overflow-y-auto">
            <div 
              v-for="chapter in enrollment.course.chapters" 
              :key="chapter.id"
              @click="selectChapter(chapter)"
              :class="['p-3 rounded-md cursor-pointer text-sm', currentChapter?.id === chapter.id ? 'bg-indigo-100 text-indigo-800' : 'hover:bg-gray-100']"
            >
              <div class="flex items-center gap-2">
                <span v-if="isChapterComplete(chapter)" class="text-green-600">✓</span>
                <span v-else class="text-gray-400">○</span>
                <span>{{ chapter.title }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Video Player Area -->
      <div class="md:col-span-3">
        <div v-if="currentChapter && currentVideo" class="bg-white rounded-lg shadow-sm overflow-hidden">
          <!-- Video -->
          <div class="video-container">
            <video 
              ref="videoPlayer"
              :src="currentVideo.url" 
              controls 
              class="w-full"
              @timeupdate="onTimeUpdate"
              @ended="onVideoEnded"
            ></video>
          </div>

          <!-- Chapter Info -->
          <div class="p-6">
            <h2 class="text-xl font-semibold mb-2">{{ currentChapter.title }}</h2>
            <h3 class="text-lg text-gray-700 mb-4">{{ currentVideo.title }}</h3>

            <!-- Mark Complete Button -->
            <button 
              @click="markVideoComplete"
              :disabled="videoMarkedComplete"
              :class="['px-6 py-2 rounded-md font-medium', videoMarkedComplete ? 'bg-green-100 text-green-800' : 'bg-indigo-600 text-white hover:bg-indigo-700']"
            >
              {{ videoMarkedComplete ? '✓ Completed' : $t('videoPlayer.markComplete') }}
            </button>

            <!-- Navigation -->
            <div class="flex justify-between mt-6 pt-6 border-t">
              <button 
                @click="previousVideo"
                :disabled="!hasPreviousVideo"
                class="text-gray-600 hover:text-gray-800 disabled:opacity-50"
              >
                ← {{ $t('videoPlayer.previousChapter') }}
              </button>
              <button 
                @click="nextVideo"
                :disabled="!hasNextVideo"
                class="text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
              >
                {{ $t('videoPlayer.nextChapter') }} →
              </button>
            </div>
          </div>
        </div>

        <!-- Quiz Modal (In-Video Question) -->
        <div v-if="showQuizModal" class="modal-overlay" @click.self="closeQuizModal">
          <div class="modal-content">
            <h3 class="text-xl font-semibold mb-4">Question</h3>
            <p class="text-gray-700 mb-4">{{ currentQuizQuestion.question_text }}</p>
            
            <div v-if="currentQuizQuestion.type === 'mcq'" class="space-y-2 mb-4">
              <label 
                v-for="option in currentQuizQuestion.options" 
                :key="option.id"
                class="flex items-center gap-2 p-3 border rounded-md cursor-pointer hover:bg-gray-50"
              >
                <input 
                  type="radio" 
                  :name="'quiz_' + currentQuizQuestion.id" 
                  :value="option.id"
                  v-model="selectedAnswer"
                  class="accent-indigo-600"
                />
                <span>{{ option.text }}</span>
              </label>
            </div>

            <textarea 
              v-else
              v-model="writtenAnswer"
              placeholder="Type your answer here..."
              class="w-full border rounded-md px-4 py-2 h-32 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4"
            ></textarea>

            <div class="flex justify-end gap-4">
              <button 
                @click="closeQuizModal"
                class="px-4 py-2 text-gray-600 hover:text-gray-800"
              >
                Cancel
              </button>
              <button 
                @click="submitQuizAnswer"
                :disabled="!canSubmitQuiz"
                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ $t('quiz.submit') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Quiz/Exam Section -->
        <div v-if="currentChapter.quiz" class="bg-white rounded-lg shadow-sm p-6 mt-6">
          <h3 class="text-lg font-semibold mb-4">Chapter Quiz</h3>
          <router-link 
            :to="`/quiz/${currentChapter.quiz.id}`"
            class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700"
          >
            Start Quiz
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const enrollment = ref(null)
const loading = ref(true)
const currentChapter = ref(null)
const currentVideo = ref(null)
const currentVideoIndex = ref(0)
const videoMarkedComplete = ref(false)
const showQuizModal = ref(false)
const currentQuizQuestion = ref(null)
const selectedAnswer = ref(null)
const writtenAnswer = ref('')
const videoPlayer = ref(null)

const hasPreviousVideo = computed(() => currentVideoIndex.value > 0)
const hasNextVideo = computed(() => {
  if (!currentChapter.value) return false
  return currentVideoIndex.value < currentChapter.value.videos.length - 1
})

const canSubmitQuiz = computed(() => {
  if (!currentQuizQuestion.value) return false
  if (currentQuizQuestion.value.type === 'mcq') {
    return selectedAnswer.value !== null
  }
  return writtenAnswer.value.trim().length > 0
})

const fetchEnrollment = async () => {
  try {
    const response = await axios.get(`/api/enrollments/${route.params.enrollmentId}`)
    enrollment.value = response.data.data || response.data
    
    if (enrollment.value.course?.chapters?.length > 0) {
      selectChapter(enrollment.value.course.chapters[0])
    }
  } catch (error) {
    console.error('Error fetching enrollment:', error)
    alert('Failed to load course')
    router.push('/profile')
  } finally {
    loading.value = false
  }
}

const selectChapter = (chapter) => {
  currentChapter.value = chapter
  currentVideoIndex.value = 0
  if (chapter.videos?.length > 0) {
    currentVideo.value = chapter.videos[0]
    videoMarkedComplete.value = false
  }
}

const previousVideo = () => {
  if (hasPreviousVideo.value) {
    currentVideoIndex.value--
    currentVideo.value = currentChapter.value.videos[currentVideoIndex.value]
    videoMarkedComplete.value = false
  }
}

const nextVideo = () => {
  if (hasNextVideo.value) {
    currentVideoIndex.value++
    currentVideo.value = currentChapter.value.videos[currentVideoIndex.value]
    videoMarkedComplete.value = false
  }
}

const onTimeUpdate = () => {
  if (!videoPlayer.value || !currentChapter.value.video_questions) return
  
  const currentTime = videoPlayer.value.currentTime
  currentChapter.value.video_questions.forEach(question => {
    if (Math.abs(currentTime - question.timestamp) < 1 && !question.answered) {
      videoPlayer.value.pause()
      currentQuizQuestion.value = question
      showQuizModal.value = true
    }
  })
}

const onVideoEnded = () => {
  markVideoComplete()
}

const markVideoComplete = async () => {
  if (videoMarkedComplete.value) return
  
  try {
    await axios.post(`/api/videos/${currentVideo.value.id}/complete`, {
      enrollment_id: enrollment.value.id
    })
    videoMarkedComplete.value = true
    
    // Refresh enrollment to get updated progress
    fetchEnrollment()
  } catch (error) {
    console.error('Error marking video complete:', error)
  }
}

const closeQuizModal = () => {
  showQuizModal.value = false
  currentQuizQuestion.value = null
  selectedAnswer.value = null
  writtenAnswer.value = ''
  if (videoPlayer.value) {
    videoPlayer.value.play()
  }
}

const submitQuizAnswer = async () => {
  try {
    await axios.post('/api/quiz-attempts', {
      quiz_question_id: currentQuizQuestion.value.id,
      answer: currentQuizQuestion.value.type === 'mcq' ? selectedAnswer.value : writtenAnswer.value,
      enrollment_id: enrollment.value.id
    })
    
    // Mark question as answered
    currentQuizQuestion.value.answered = true
    closeQuizModal()
  } catch (error) {
    console.error('Error submitting quiz answer:', error)
    alert('Failed to submit answer')
  }
}

const isChapterComplete = (chapter) => {
  return chapter.videos?.every(v => v.completed) || false
}

onMounted(() => {
  fetchEnrollment()
})
</script>
